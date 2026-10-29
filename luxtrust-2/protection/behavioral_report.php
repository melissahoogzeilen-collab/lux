<?php
declare(strict_types=1);

/* ------------ HEADERS ------------ */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://yourdomain.tld'); // set to your real origin or remove if same-origin
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'method_not_allowed']);
    exit;
}

/* ------------ BOOTSTRAP ---------- */
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ------------ HELPERS ------------ */
function client_ip(): string {
    // Cloudflare real client IP, else origin IP. (Do NOT trust X-Forwarded-For here)
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function body_json(): array {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}
function table_cols(PDO $pdo, string $t): array {
    static $c=[]; if (isset($c[$t])) return $c[$t];
    $q=$pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $q->execute([$t]);
    return $c[$t] = array_map('strval', $q->fetchAll(PDO::FETCH_COLUMN) ?: []);
}
function has_col(PDO $pdo, string $t, string $col): bool {
    return in_array($col, table_cols($pdo, $t), true);
}

/* ------------ INPUT -------------- */
$errors = [];
try {
    $in = body_json();
    if (!$in) throw new RuntimeException('invalid_json');

    $ip = client_ip();
    $session_id = session_id();

    $score = is_array($in['behavioral_score'] ?? null) ? $in['behavioral_score'] : [];
    $stats = is_array($in['stats'] ?? null)            ? $in['stats']            : [];
    $fp    = is_array($in['fingerprint'] ?? null)      ? $in['fingerprint']      : [];

    // sanitize defensively; if missing, treat as safe (100) so first reports don't look bad
    $human_score     = is_numeric($score['overall'] ?? null) ? max(0, min(100, (int)$score['overall'])) : 100;
    $mouseMovement   = (float)($score['mouseMovement'] ?? 0);
    $clickPattern    = (float)($score['clickPattern']  ?? 0);
    $timing          = (float)($score['timing']        ?? 0);

    $sessionDuration = (int)($stats['sessionDuration'] ?? 0);
    $mouseMoves      = (int)($stats['mouseMovements']  ?? 0);
    $clicks          = (int)($stats['clicks']          ?? 0);
    $keys            = (int)($stats['keystrokes']      ?? 0);
    $scrolls         = (int)($stats['scrolls']         ?? 0);

    /* ---------- SESSION ACCUMULATORS (for gates in check.php) ------------ */
    $interactions = $mouseMoves + $clicks + $keys + $scrolls;
    $_SESSION['ab_first_ts']     = $_SESSION['ab_first_ts']     ?? time();
    $_SESSION['ab_samples']      = (int)(($_SESSION['ab_samples'] ?? 0) + 1);
    $_SESSION['ab_interactions'] = (int)(($_SESSION['ab_interactions'] ?? 0) + $interactions);
    $_SESSION['ab_last_score']   = $human_score;

    /* ---------- 1) behavioral_patterns insert (kept) --------------------- */
    try {
        $bp = table_cols($pdo, 'behavioral_patterns');
        if (!empty($bp)) {
            $cols = ['ip_address','session_id','created_at']; $ph = ['?','?','NOW()']; $vals = [$ip, $session_id];
            if (in_array('mouse_velocity_avg',     $bp, true)) { $cols[]='mouse_velocity_avg';     $ph[]='?'; $vals[]=$mouseMovement; }
            if (in_array('mouse_acceleration_avg', $bp, true)) { $cols[]='mouse_acceleration_avg'; $ph[]='?'; $vals[]=$clickPattern; }
            if (in_array('page_dwell_time',        $bp, true)) { $cols[]='page_dwell_time';        $ph[]='?'; $vals[]=$sessionDuration; }
            if (in_array('human_score',            $bp, true)) { $cols[]='human_score';            $ph[]='?'; $vals[]=$human_score; }
            $sql = "INSERT INTO behavioral_patterns (".implode(',', $cols).") VALUES (".implode(',', $ph).")";
            $pdo->prepare($sql)->execute($vals);
        }
    } catch (Throwable $e) { $errors[] = 'behavioral_patterns: '.$e->getMessage(); }

    /* ---------- 2) Browser fingerprint (kept) ---------------------------- */
    try {
        $bf = table_cols($pdo, 'browser_fingerprints');
        if (!empty($bf) && in_array('fingerprint_hash', $bf, true)) {
            $canvas = (string)($fp['canvas'] ?? '');
            $webglJ = json_encode($fp['webgl']  ?? []);
            $audio  = (string)($fp['audio']    ?? '');
            $screen = (string)(($fp['screen']['width'] ?? 0).'x'.($fp['screen']['height'] ?? 0));
            $tz     = (string)($fp['timezone'] ?? '');
            $lang   = (string)($fp['navigator']['language'] ?? '');
            $plugins= json_encode($fp['plugins'] ?? []);
            $fonts  = json_encode($fp['fonts']   ?? []);
            $fp_hash= hash('sha256', json_encode([$canvas,$webglJ,$audio,$screen,$tz,$lang,$plugins,$fonts]));

            $exists = (bool)$pdo->query("SELECT 1 FROM browser_fingerprints WHERE fingerprint_hash=".$pdo->quote($fp_hash)." LIMIT 1")->fetchColumn();
            if ($exists) {
                $set=[];
                if (in_array('usage_count',$bf,true)) $set[]="usage_count=usage_count+1";
                if (in_array('last_seen',  $bf,true)) $set[]="last_seen=NOW()";
                if ($set) $pdo->exec("UPDATE browser_fingerprints SET ".implode(', ', $set)." WHERE fingerprint_hash=".$pdo->quote($fp_hash));
            } else {
                $cols=['fingerprint_hash']; $ph=['?']; $vals=[$fp_hash];
                if (in_array('canvas_fingerprint',$bf,true)) { $cols[]='canvas_fingerprint'; $ph[]='?'; $vals[]=$canvas; }
                if (in_array('webgl_fingerprint', $bf,true)) { $cols[]='webgl_fingerprint';  $ph[]='?'; $vals[]=$webglJ; }
                if (in_array('audio_fingerprint', $bf,true)) { $cols[]='audio_fingerprint';  $ph[]='?'; $vals[]=$audio; }
                if (in_array('screen_resolution',$bf,true))  { $cols[]='screen_resolution';  $ph[]='?'; $vals[]=$screen; }
                if (in_array('timezone',          $bf,true)) { $cols[]='timezone';           $ph[]='?'; $vals[]=$tz; }
                if (in_array('language',          $bf,true)) { $cols[]='language';           $ph[]='?'; $vals[]=$lang; }
                if (in_array('plugins',           $bf,true)) { $cols[]='plugins';            $ph[]='?'; $vals[]=$plugins; }
                if (in_array('fonts',             $bf,true)) { $cols[]='fonts';              $ph[]='?'; $vals[]=$fonts; }
                if (in_array('first_seen',        $bf,true)) { $cols[]='first_seen';         $ph[]='NOW()'; }
                if (in_array('last_seen',         $bf,true)) { $cols[]='last_seen';          $ph[]='NOW()'; }
                if (in_array('usage_count',       $bf,true)) { $cols[]='usage_count';        $ph[]='1'; }
                if (in_array('is_suspicious',     $bf,true)) { $cols[]='is_suspicious';      $ph[]='0'; }
                $pdo->prepare("INSERT INTO browser_fingerprints (".implode(',', $cols).") VALUES (".implode(',', $ph).")")->execute($vals);
            }

            if (has_col($pdo,'visitors','browser_fingerprint')) {
                $pdo->prepare("UPDATE visitors SET browser_fingerprint=?, last_activity=NOW()
                               WHERE ip_address=? ORDER BY created_at DESC, id DESC LIMIT 1")
                    ->execute([$fp_hash, $ip]);
            }
        }
    } catch (Throwable $e) { $errors[] = 'browser_fingerprints: '.$e->getMessage(); }

    /* ---------- 3) Device fingerprint (kept minimal) --------------------- */
    try {
        $webgl  = $fp['webgl']  ?? [];
        $screenA= $fp['screen'] ?? [];
        $dev_payload = [
            'wg_vendor'   => strtolower(trim((string)($webgl['vendor']   ?? ''))),
            'wg_renderer' => strtolower(trim((string)($webgl['renderer'] ?? ''))),
            'screen'      => (string)(($screenA['width'] ?? 0).'x'.($screenA['height'] ?? 0)),
            'tz'          => (string)($fp['timezone'] ?? ''),
            'lang'        => strtolower((string)($fp['navigator']['language'] ?? '')),
            'plugins'     => array_values(array_map('strval', $fp['plugins'] ?? [])),
            'fonts'       => array_values(array_map('strval', $fp['fonts']   ?? [])),
        ];
        $device_id = hash('sha256', json_encode($dev_payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

        $df = table_cols($pdo, 'device_fingerprints');
        if (!empty($df)) {
            $exists = (bool)$pdo->query("SELECT 1 FROM device_fingerprints WHERE device_id=".$pdo->quote($device_id)." LIMIT 1")->fetchColumn();
            if ($exists) {
                $pdo->exec("UPDATE device_fingerprints SET usage_count=usage_count+1, last_seen=NOW() WHERE device_id=".$pdo->quote($device_id));
            } else {
                $stmt = $pdo->prepare("INSERT INTO device_fingerprints (device_id, usage_count, last_seen, is_suspicious)
                                       VALUES (?, 1, NOW(), 0)");
                $stmt->execute([$device_id]);
            }
        }
        if (has_col($pdo,'visitors','device_id')) {
            $vid = $pdo->prepare("SELECT id FROM visitors WHERE ip_address=? ORDER BY created_at DESC, id DESC LIMIT 1");
            $vid->execute([$ip]);
            if ($visitor_id = $vid->fetchColumn()) {
                $pdo->prepare("UPDATE visitors SET device_id=? WHERE id=?")->execute([$device_id, $visitor_id]);
            }
        }
    } catch (Throwable $e) { $errors[] = 'device_fingerprints: '.$e->getMessage(); }

    /* ---------- 4) Update latest visitors stats (kept) ------------------- */
    try {
        $vid = $pdo->prepare("SELECT id FROM visitors WHERE ip_address=? ORDER BY created_at DESC, id DESC LIMIT 1");
        $vid->execute([$ip]);
        $visitor_id = $vid->fetchColumn();

        if ($visitor_id) {
            $vcols = table_cols($pdo, 'visitors');
            $set=[]; $vals=[];
            if (in_array('behavioral_score',$vcols,true)) { $set[]='behavioral_score=?'; $vals[]=$human_score; }
            if (in_array('mouse_movements',$vcols,true))  { $set[]='mouse_movements=?';  $vals[]=$mouseMoves; }
            if (in_array('click_events',$vcols,true))     { $set[]='click_events=?';     $vals[]=$clicks; }
            if (in_array('keyboard_events',$vcols,true))  { $set[]='keyboard_events=?';  $vals[]=$keys; }
            if (in_array('scroll_events',$vcols,true))    { $set[]='scroll_events=?';    $vals[]=$scrolls; }
            if (in_array('session_duration',$vcols,true)) { $set[]='session_duration=?'; $vals[]=$sessionDuration; }
            if (in_array('last_activity',$vcols,true))    { $set[]='last_activity=NOW()'; }
            if (in_array('updated_at',$vcols,true))       { $set[]='updated_at=NOW()'; }

            if ($set) {
                $vals[] = $visitor_id;
                $pdo->prepare(
                    "UPDATE visitors SET ".implode(', ', $set)." WHERE id=? 
                     AND COALESCE(is_bot,0)=0 
                     AND (block_reason IS NULL OR block_reason='OK')"
                )->execute($vals);
            }
        }
    } catch (Throwable $e) { $errors[] = 'visitors_update: '.$e->getMessage(); }

    /* ---------- 5) No decision here (check.php handles gating & block) --- */
    echo json_encode([
        'success'=>true,
        'message'=>'Recorded',
        'score'=>$human_score,
        'errors'=>$errors
    ]);
} catch (Throwable $e) {
    error_log('behavioral_report fatal: '.$e->getMessage());
    echo json_encode(['success'=>false,'error'=>'server_error']);
}
