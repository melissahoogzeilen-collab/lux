<?php
/**
 * Safe Anti-Bot Professional Upgrade Layer
 * - Server-side only
 * - Does not touch CAPTCHA flow
 * - Adds temporary TTL blocks, adaptive risk scoring, route-aware limits,
 *   request-size scoring, IPv4/IPv6 CIDR helpers, small DB-backed caches,
 *   and safe automatic cleanup when migration is installed.
 */

if (!function_exists('ab_bool_setting')) {
    function ab_bool_setting(array $settings, array $keys, bool $default = false): bool {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $settings)) continue;
            $v = strtolower(trim((string)$settings[$k]));
            return in_array($v, ['1','true','on','yes','enabled'], true);
        }
        return $default;
    }
}

if (!function_exists('ab_int_setting')) {
    function ab_int_setting(array $settings, string $key, int $default, int $min = 0, int $max = 1000000): int {
        $v = isset($settings[$key]) ? (int)$settings[$key] : $default;
        if ($v < $min) $v = $min;
        if ($v > $max) $v = $max;
        return $v;
    }
}

if (!function_exists('ab_load_settings')) {
    function ab_load_settings(PDO $pdo): array {
        static $settings_cache = null;
        if (is_array($settings_cache)) return $settings_cache;

        $use_apcu = function_exists('apcu_fetch') && function_exists('apcu_store');
        $cache_key = 'ab_settings_v2_' . md5(__DIR__);
        if ($use_apcu) {
            $cached = apcu_fetch($cache_key, $ok);
            if ($ok && is_array($cached)) {
                $settings_cache = $cached;
                return $settings_cache;
            }
        }

        try {
            $stmt = $pdo->query("SELECT name, value FROM settings");
            $settings_cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Throwable $e) {
            $settings_cache = [];
        }

        if ($use_apcu) {
            // Short cache keeps dashboard toggle changes responsive.
            apcu_store($cache_key, $settings_cache, 5);
        }
        return $settings_cache;
    }
}

if (!function_exists('ab_ip_in_cidr')) {
    function ab_ip_in_cidr(string $ip, string $cidr): bool {
        $cidr = trim($cidr);
        if ($cidr === '' || $ip === '') return false;

        if (strpos($cidr, '/') === false) {
            return filter_var($cidr, FILTER_VALIDATE_IP) && strcasecmp($ip, $cidr) === 0;
        }

        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
        $subnet = trim((string)$subnet);
        $bits = (int)$bits;

        $ip_bin = @inet_pton($ip);
        $subnet_bin = @inet_pton($subnet);
        if ($ip_bin === false || $subnet_bin === false) return false;
        if (strlen($ip_bin) !== strlen($subnet_bin)) return false;

        $max_bits = strlen($ip_bin) * 8;
        if ($bits < 0 || $bits > $max_bits) return false;

        $full_bytes = intdiv($bits, 8);
        $remaining_bits = $bits % 8;

        if ($full_bytes > 0 && substr($ip_bin, 0, $full_bytes) !== substr($subnet_bin, 0, $full_bytes)) {
            return false;
        }

        if ($remaining_bits === 0) return true;

        $mask = (0xFF << (8 - $remaining_bits)) & 0xFF;
        return ((ord($ip_bin[$full_bytes]) & $mask) === (ord($subnet_bin[$full_bytes]) & $mask));
    }
}

if (!function_exists('ab_load_cidr_lines')) {
    function ab_load_cidr_lines(string $file): array {
        if (!is_readable($file)) return [];
        $out = [];
        foreach ((file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) as $line) {
            $line = trim(preg_split('/[#;]/', $line, 2)[0]);
            if ($line !== '') $out[] = $line;
        }
        return $out;
    }
}

if (!function_exists('ab_ip_in_any_cidr')) {
    function ab_ip_in_any_cidr(string $ip, array $cidrs): bool {
        foreach ($cidrs as $cidr) {
            if (ab_ip_in_cidr($ip, $cidr)) return true;
        }
        return false;
    }
}


if (!function_exists('ab_cache_get')) {
    function ab_cache_get(PDO $pdo, string $key): ?string {
        try {
            $stmt = $pdo->prepare("SELECT cache_value FROM protection_cache WHERE cache_key = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$key]);
            $v = $stmt->fetchColumn();
            return $v === false ? null : (string)$v;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('ab_cache_set')) {
    function ab_cache_set(PDO $pdo, string $key, string $value, int $ttl_seconds): void {
        try {
            $stmt = $pdo->prepare("INSERT INTO protection_cache (cache_key, cache_value, expires_at, created_at, updated_at)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW(), NOW())
                ON DUPLICATE KEY UPDATE cache_value = VALUES(cache_value), expires_at = VALUES(expires_at), updated_at = NOW()");
            $stmt->execute([$key, $value, $ttl_seconds]);
        } catch (Throwable $e) {
            // Migration may not be installed yet; fail open.
        }
    }
}

if (!function_exists('ab_cached_rdns')) {
    function ab_cached_rdns(PDO $pdo, string $ip, array $settings): string {
        if (!ab_bool_setting($settings, ['advanced_cache_enabled'], true)) {
            return @gethostbyaddr($ip) ?: '';
        }
        $key = 'rdns:' . $ip;
        $cached = ab_cache_get($pdo, $key);
        if ($cached !== null) return $cached;
        $rdns = @gethostbyaddr($ip) ?: '';
        ab_cache_set($pdo, $key, $rdns, ab_int_setting($settings, 'rdns_cache_ttl_seconds', 86400, 60, 604800));
        return $rdns;
    }
}

if (!function_exists('ab_temp_block_check')) {
    function ab_temp_block_check(PDO $pdo, string $ip): array {
        try {
            $pdo->prepare("DELETE FROM temporary_blocks WHERE expires_at <= NOW()")->execute();
            $stmt = $pdo->prepare("SELECT reason, risk_score, expires_at FROM temporary_blocks WHERE ip_address = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return ['blocked'=>true, 'reason'=>(string)$row['reason'], 'score'=>(int)$row['risk_score'], 'expires_at'=>(string)$row['expires_at']];
            }
        } catch (Throwable $e) {
            // Migration may not be installed yet; fail open.
        }
        return ['blocked'=>false, 'reason'=>''];
    }
}

if (!function_exists('ab_temp_block_add')) {
    function ab_temp_block_add(PDO $pdo, string $ip, string $reason, int $score, int $ttl_minutes): void {
        $ttl_minutes = max(1, min($ttl_minutes, 1440));
        try {
            $stmt = $pdo->prepare("INSERT INTO temporary_blocks (ip_address, reason, risk_score, expires_at, created_at, updated_at)
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW(), NOW())
                ON DUPLICATE KEY UPDATE reason = VALUES(reason), risk_score = VALUES(risk_score), expires_at = VALUES(expires_at), updated_at = NOW()");
            $stmt->execute([$ip, $reason, $score, $ttl_minutes]);
        } catch (Throwable $e) {
            // Migration may not be installed yet; fail open.
        }
    }
}

if (!function_exists('ab_record_risk_event')) {
    function ab_record_risk_event(PDO $pdo, string $ip, string $route, int $score, array $reasons, string $action): void {
        try {
            $stmt = $pdo->prepare("INSERT INTO risk_events (ip_address, route, risk_score, reasons, action_taken, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$ip, $route, $score, implode(' | ', $reasons), $action]);
        } catch (Throwable $e) {
            // Migration may not be installed yet; fail open.
        }
    }
}

if (!function_exists('ab_route_key')) {
    function ab_route_key(): string {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $script = basename($_SERVER['SCRIPT_FILENAME'] ?? 'index.php');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = preg_replace('/[^a-zA-Z0-9_\.\/-]/', '', $path);
        $key = strtolower($method . ':' . $script . ':' . $path);
        return substr('route:' . md5($key), 0, 70);
    }
}

if (!function_exists('ab_check_route_rate_limit')) {
    function ab_check_route_rate_limit(PDO $pdo, string $ip, array $settings): array {
        if (!ab_bool_setting($settings, ['route_rate_limits_enabled'], false)) {
            return ['blocked'=>false, 'reason'=>''];
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $script = strtolower(basename($_SERVER['SCRIPT_FILENAME'] ?? 'index.php'));
        $endpoint = ab_route_key();
        $sensitive = in_array($script, ['login.php','captcha_passed.php','behavioral_report.php','honeypot_report.php','check_visitor.php'], true)
                     || strpos(strtolower($_SERVER['REQUEST_URI'] ?? ''), '/admin/') !== false;

        $limit = ($method === 'POST')
            ? ab_int_setting($settings, $sensitive ? 'route_rate_limit_sensitive_post_per_minute' : 'route_rate_limit_post_per_minute', $sensitive ? 8 : 20, 1, 10000)
            : ab_int_setting($settings, 'route_rate_limit_get_per_minute', 120, 1, 10000);

        try {
            $stmt = $pdo->prepare("SELECT blocked_until FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND blocked_until > NOW() LIMIT 1");
            $stmt->execute([$ip, $endpoint]);
            if ($stmt->fetchColumn()) {
                return ['blocked'=>true, 'reason'=>'Route rate limit active'];
            }

            $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, request_count, window_start)
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    request_count = CASE WHEN window_start < DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 1 ELSE request_count + 1 END,
                    window_start  = CASE WHEN window_start < DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN NOW() ELSE window_start END");
            $stmt->execute([$ip, $endpoint]);

            $stmt = $pdo->prepare("SELECT request_count FROM rate_limits WHERE ip_address = ? AND endpoint = ? LIMIT 1");
            $stmt->execute([$ip, $endpoint]);
            $count = (int)$stmt->fetchColumn();
            if ($count > $limit) {
                $block_minutes = ab_int_setting($settings, 'route_rate_limit_block_minutes', 10, 1, 1440);
                $stmt = $pdo->prepare("UPDATE rate_limits SET blocked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE ip_address = ? AND endpoint = ?");
                $stmt->execute([$block_minutes, $ip, $endpoint]);
                return ['blocked'=>true, 'reason'=>"Route rate limit exceeded: $count / $limit"];
            }
        } catch (Throwable $e) {
            return ['blocked'=>false, 'reason'=>''];
        }

        return ['blocked'=>false, 'reason'=>''];
    }
}

if (!function_exists('ab_adaptive_risk')) {
    function ab_adaptive_risk(PDO $pdo, string $ip, string $country, string $ua, array $settings): array {
        if (!ab_bool_setting($settings, ['adaptive_risk_engine_enabled'], false)) {
            return ['score'=>0, 'reasons'=>[]];
        }

        $score = 0;
        $reasons = [];
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = strtolower(parse_url($uri, PHP_URL_PATH) ?? '/');
        $query = parse_url($uri, PHP_URL_QUERY) ?? '';
        $ua_lc = strtolower($ua ?? '');
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $accept_encoding = strtolower($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '');
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

        $add = function(int $points, string $reason) use (&$score, &$reasons): void {
            $score += $points;
            $reasons[] = $reason . ' +' . $points;
        };

        if ($ua_lc === '' || $ua_lc === 'unknown') $add(30, 'empty user agent');
        elseif (strlen($ua_lc) < 18) $add(15, 'very short user agent');

        if (!in_array($method, ['GET','POST','HEAD','OPTIONS'], true)) {
            $add(70, 'unusual HTTP method ' . $method);
        }

        foreach (['curl','wget','python','scrapy','go-http-client','java/','libwww','httpclient','axios'] as $token) {
            if ($token !== '' && strpos($ua_lc, $token) !== false) {
                $add(70, 'automation client token ' . $token);
                break;
            }
        }

        foreach (['/.env','/.git','/wp-admin','/wp-login.php','/xmlrpc.php','/phpmyadmin','/server-status','/vendor/phpunit','/composer.json','/shell.php','/config.php~','/.aws','/.ssh','/actuator','/cgi-bin/'] as $probe) {
            if (strpos($path, $probe) !== false) {
                $add(90, 'scanner/probe path ' . $probe);
                break;
            }
        }

        if (strlen($query) > 1500) $add(25, 'very long query string');
        if (substr_count($query, '&') > 30) $add(25, 'too many query parameters');

        // Safe flood signal: normal forms are small; very large POST bodies add risk without hard-blocking alone.
        $content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
        $max_post_body = ab_int_setting($settings, 'max_post_body_bytes', 1048576, 1024, 10485760);
        if ($method === 'POST' && $content_length > $max_post_body) {
            $add(40, 'large POST body ' . $content_length . ' bytes');
        }

        if ($method === 'POST' && empty($_SERVER['CONTENT_TYPE'])) $add(15, 'POST without content type');
        if ($accept === '') $add(10, 'missing Accept header');
        if ($accept_language === '' && preg_match('/chrome|firefox|safari|edge/i', $ua_lc)) $add(10, 'browser UA missing Accept-Language');
        if (preg_match('/chrome|firefox|edge/i', $ua_lc) && strpos($accept_encoding, 'gzip') === false) $add(8, 'browser UA missing gzip support');

        $score = min(100, $score);
        return ['score'=>$score, 'reasons'=>$reasons];
    }
}

if (!function_exists('ab_housekeeping')) {
    function ab_housekeeping(PDO $pdo, array $settings): void {
        // Very light cleanup: runs randomly so normal requests stay fast.
        if (!ab_bool_setting($settings, ['advanced_cache_enabled'], true)) return;
        if (mt_rand(1, 100) !== 1) return;

        $retention_days = ab_int_setting($settings, 'risk_events_retention_days', 14, 1, 365);
        try {
            $pdo->prepare("DELETE FROM protection_cache WHERE expires_at <= NOW()")->execute();
            $pdo->prepare("DELETE FROM temporary_blocks WHERE expires_at <= NOW()")->execute();
            $stmt = $pdo->prepare("DELETE FROM risk_events WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->execute([$retention_days]);
        } catch (Throwable $e) {
            // Migration may not be installed yet; fail open.
        }
    }
}



if (!function_exists('ab_match_ua_tokens')) {
    function ab_match_ua_tokens(string $ua, array $tokens): ?string {
        $ua_lc = strtolower($ua);
        foreach ($tokens as $token) {
            $token = strtolower(trim((string)$token));
            if ($token !== '' && strpos($ua_lc, $token) !== false) {
                return $token;
            }
        }
        return null;
    }
}

if (!function_exists('ab_load_custom_ua_patterns')) {
    function ab_load_custom_ua_patterns(PDO $pdo, string $type): array {
        static $cache = [];
        $type = ($type === 'whitelist') ? 'whitelist' : 'blocklist';
        if (isset($cache[$type])) return $cache[$type];
        try {
            $stmt = $pdo->prepare("SELECT pattern FROM custom_ua_patterns WHERE type = ? AND enabled = 1 ORDER BY id ASC");
            $stmt->execute([$type]);
            $cache[$type] = array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
        } catch (Throwable $e) {
            $cache[$type] = [];
        }
        return $cache[$type];
    }
}

if (!function_exists('ab_match_custom_ua_pattern')) {
    function ab_match_custom_ua_pattern(PDO $pdo, string $ua, string $type): ?string {
        $patterns = ab_load_custom_ua_patterns($pdo, $type);
        return ab_match_ua_tokens($ua, $patterns);
    }
}

if (!function_exists('ab_ua_category_decision')) {
    function ab_ua_category_decision(PDO $pdo, string $ua, array $settings): array {
        $ua = (string)$ua;

        if (ab_bool_setting($settings, ['custom_ua_whitelist_enabled'], true)) {
            $hit = ab_match_custom_ua_pattern($pdo, $ua, 'whitelist');
            if ($hit !== null) {
                return ['action' => 'allow', 'reason' => 'Custom UA whitelist: ' . $hit];
            }
        }

        $search_engines = [
            'googlebot','bingbot','slurp','duckduckbot','baiduspider','yandexbot','applebot',
            'sogou','exabot','facebot' // facebot is also social but safe when search/social allowed
        ];
        $social_crawlers = [
            'facebookexternalhit','facebot','twitterbot','linkedinbot','pinterestbot','slackbot',
            'discordbot','telegrambot','whatsapp','skypeuripreview','embedly','quora link preview'
        ];
        $ai_crawlers = [
            'gptbot','chatgpt-user','oai-searchbot','claude-web','claude-user','anthropic-ai',
            'ccbot','perplexitybot','bytespider','cohere-ai','facebookbot','diffbot','omgili'
        ];
        $security_scanners = [
            'nmap','masscan','nikto','sqlmap','burp','nuclei','gobuster','ffuf','acunetix','nessus',
            'netsparker','zgrab','shodan','censys','zoomeye','binaryedge','fofa','quake','httpx','dirbuster',
            'openvas','qualys','wappalyzer'
        ];
        $scraping_tools = [
            'scrapy','beautifulsoup','bs4','httrack','selenium','puppeteer','playwright','phantomjs',
            'mechanize','curl','wget','python-requests','go-http-client','java/','libwww','httpclient','axios'
        ];

        if (ab_bool_setting($settings, ['allow_search_engines'], true)) {
            $hit = ab_match_ua_tokens($ua, $search_engines);
            if ($hit !== null) {
                return ['action' => 'allow', 'reason' => 'Allowed search engine crawler: ' . $hit];
            }
        }

        if (ab_bool_setting($settings, ['allow_social_crawlers'], true)) {
            $hit = ab_match_ua_tokens($ua, $social_crawlers);
            if ($hit !== null) {
                return ['action' => 'allow', 'reason' => 'Allowed social preview crawler: ' . $hit];
            }
        }

        if (ab_bool_setting($settings, ['custom_ua_blocklist_enabled'], true)) {
            $hit = ab_match_custom_ua_pattern($pdo, $ua, 'blocklist');
            if ($hit !== null) {
                return ['action' => 'block', 'reason' => 'Custom UA blocklist: ' . $hit];
            }
        }

        if (ab_bool_setting($settings, ['block_ai_crawlers'], true)) {
            $hit = ab_match_ua_tokens($ua, $ai_crawlers);
            if ($hit !== null) return ['action' => 'block', 'reason' => 'AI crawler blocked: ' . $hit];
        }
        if (ab_bool_setting($settings, ['block_security_scanners'], true)) {
            $hit = ab_match_ua_tokens($ua, $security_scanners);
            if ($hit !== null) return ['action' => 'block', 'reason' => 'Security scanner blocked: ' . $hit];
        }
        if (ab_bool_setting($settings, ['block_scraping_tools'], true)) {
            $hit = ab_match_ua_tokens($ua, $scraping_tools);
            if ($hit !== null) return ['action' => 'block', 'reason' => 'Scraping tool blocked: ' . $hit];
        }

        return ['action' => 'none', 'reason' => ''];
    }
}

if (!function_exists('ab_lookup_asn_info')) {
    function ab_lookup_asn_info(PDO $pdo, string $ip, array $settings): array {
        if ($ip === '' || !class_exists('GeoIp2\\Database\\Reader')) {
            return ['asn'=>null, 'org'=>''];
        }
        $cache_key = 'asninfo:' . $ip;
        if (ab_bool_setting($settings, ['advanced_cache_enabled'], true)) {
            $cached = ab_cache_get($pdo, $cache_key);
            if ($cached !== null && $cached !== '') {
                $data = json_decode($cached, true);
                if (is_array($data)) return ['asn'=>$data['asn'] ?? null, 'org'=>(string)($data['org'] ?? '')];
            }
        }
        $file = __DIR__ . '/admin/GeoLite2-ASN.mmdb';
        if (!is_file($file) || !is_readable($file)) return ['asn'=>null, 'org'=>''];
        try {
            $reader = new \GeoIp2\Database\Reader($file);
            $data = $reader->get($ip);
            $info = [
                'asn' => isset($data['autonomous_system_number']) ? (int)$data['autonomous_system_number'] : null,
                'org' => (string)($data['autonomous_system_organization'] ?? ''),
            ];
            if (ab_bool_setting($settings, ['advanced_cache_enabled'], true)) {
                ab_cache_set($pdo, $cache_key, json_encode($info), 604800);
            }
            return $info;
        } catch (Throwable $e) {
            return ['asn'=>null, 'org'=>''];
        }
    }
}

if (!function_exists('ab_policy_risk')) {
    function ab_policy_risk(PDO $pdo, string $ip, string $country, array $settings): array {
        $score = 0;
        $reasons = [];
        $hard = false;
        $allow = false;
        $add = function(int $points, string $reason) use (&$score, &$reasons): void {
            $points = max(0, min($points, 100));
            if ($points > 0) {
                $score += $points;
                $reasons[] = $reason . ' +' . $points;
            } else {
                $reasons[] = $reason;
            }
        };

        if (ab_bool_setting($settings, ['country_risk_scoring_enabled'], false) && $country !== '') {
            try {
                $stmt = $pdo->prepare("SELECT action, risk_score, note FROM country_risk_rules WHERE enabled = 1 AND country_code = ? LIMIT 1");
                $stmt->execute([strtoupper($country)]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $action = (string)$row['action'];
                    if ($action === 'allow') {
                        $allow = true;
                        $reasons[] = 'Country allow rule: ' . strtoupper($country);
                    } elseif ($action === 'block') {
                        $hard = true;
                        $add(max(85, (int)$row['risk_score']), 'Country block rule: ' . strtoupper($country));
                    } else {
                        $add((int)$row['risk_score'], 'Country risk rule: ' . strtoupper($country));
                    }
                }
            } catch (Throwable $e) {}
        }

        if (ab_bool_setting($settings, ['asn_rule_manager_enabled'], false)) {
            $asn = ab_lookup_asn_info($pdo, $ip, $settings);
            $asn_number = $asn['asn'];
            $org = (string)$asn['org'];
            try {
                $rows = $pdo->query("SELECT asn_number, org_pattern, action, risk_score, note FROM asn_risk_rules WHERE enabled = 1 ORDER BY action = 'allow' DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $match = false;
                    if ($row['asn_number'] !== null && $row['asn_number'] !== '' && $asn_number !== null && (int)$row['asn_number'] === (int)$asn_number) {
                        $match = true;
                    }
                    $pat = trim((string)($row['org_pattern'] ?? ''));
                    if (!$match && $pat !== '' && $org !== '' && stripos($org, $pat) !== false) {
                        $match = true;
                    }
                    if (!$match) continue;
                    $label = $asn_number ? ('AS' . $asn_number . ' ' . $org) : $org;
                    $action = (string)$row['action'];
                    if ($action === 'allow') {
                        $allow = true;
                        $reasons[] = 'ASN allow rule: ' . $label;
                        break;
                    }
                    if ($action === 'block') {
                        $hard = true;
                        $add(max(85, (int)$row['risk_score']), 'ASN block rule: ' . $label);
                        break;
                    }
                    $add((int)$row['risk_score'], 'ASN risk rule: ' . $label);
                    break;
                }
            } catch (Throwable $e) {}
        }

        return ['score'=>min(100, $score), 'reasons'=>$reasons, 'hard'=>$hard, 'allow'=>$allow];
    }
}

if (!function_exists('ab_apply_professional_layer')) {
    function ab_apply_professional_layer(PDO $pdo, string $ip, string $country, string $ua, array $settings): array {
        ab_housekeeping($pdo, $settings);

        $temp = ab_temp_block_check($pdo, $ip);
        if (!empty($temp['blocked'])) {
            return ['blocked'=>true, 'hard'=>false, 'score'=>(int)($temp['score'] ?? 0), 'reason'=>'Temporary block active: ' . $temp['reason']];
        }

        $route = ab_check_route_rate_limit($pdo, $ip, $settings);
        if (!empty($route['blocked'])) {
            $score = 75;
            $ttl = ab_int_setting($settings, 'temporary_block_ttl_minutes', 15, 1, 1440);
            if (ab_bool_setting($settings, ['temporary_block_ttl_enabled'], true)) {
                ab_temp_block_add($pdo, $ip, $route['reason'], $score, $ttl);
            }
            ab_record_risk_event($pdo, $ip, ab_route_key(), $score, [$route['reason']], 'temporary_block');
            return ['blocked'=>true, 'hard'=>false, 'score'=>$score, 'reason'=>$route['reason']];
        }

        $risk = ab_adaptive_risk($pdo, $ip, $country, $ua, $settings);
        $policy = ab_policy_risk($pdo, $ip, $country, $settings);

        if (!empty($policy['allow'])) {
            ab_record_risk_event($pdo, $ip, ab_route_key(), 0, $policy['reasons'] ?? ['policy allow'], 'policy_allow');
            return ['blocked'=>false, 'score'=>0, 'reason'=>'Policy allow'];
        }

        $score = min(100, (int)($risk['score'] ?? 0) + (int)($policy['score'] ?? 0));
        $reasons = array_merge($risk['reasons'] ?? [], $policy['reasons'] ?? []);
        if ($score > 0) {
            ab_record_risk_event($pdo, $ip, ab_route_key(), $score, $reasons, !empty($policy['hard']) ? 'policy_score' : 'score_only');
        }

        $threshold = ab_int_setting($settings, 'adaptive_risk_block_threshold', 75, 1, 100);
        if (!empty($policy['hard']) || $score >= $threshold) {
            $ttl = ab_int_setting($settings, 'temporary_block_ttl_minutes', 15, 1, 1440);
            $reason = 'Adaptive risk score ' . $score . ': ' . implode(' | ', $reasons);
            if (ab_bool_setting($settings, ['temporary_block_ttl_enabled'], true)) {
                ab_temp_block_add($pdo, $ip, $reason, $score, $ttl);
                ab_record_risk_event($pdo, $ip, ab_route_key(), $score, $reasons, 'temporary_block');
                return ['blocked'=>true, 'hard'=>false, 'score'=>$score, 'reason'=>$reason];
            }
            ab_record_risk_event($pdo, $ip, ab_route_key(), $score, $reasons, 'hard_block');
            return ['blocked'=>true, 'hard'=>true, 'score'=>$score, 'reason'=>$reason];
        }

        return ['blocked'=>false, 'score'=>$score, 'reason'=>''];
    }
}
