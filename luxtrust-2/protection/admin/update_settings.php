<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

require_once __DIR__ . '/../db.php'; // keep using $pdo (PDO)

$raw = file_get_contents('php://input');
$js  = json_decode($raw, true) ?: [];
$feature = $_POST['feature'] ?? ($js['feature'] ?? null);

// allow the UI to send an explicit state; if absent, we toggle
$enabled = $_POST['enabled'] ?? ($js['enabled'] ?? null); // true/false, "1"/"0"

if (!$feature) {
    echo json_encode(['success' => false, 'message' => 'Missing feature.']);
    exit();
}

$valid_features = [
    'blocked_user_agents',
    'static_range_blocking',
    'asn_blocking_enabled',
    'captcha_enabled',
    'behavioral_analysis_enabled',
    'session_analysis_enabled',
    'request_pattern_analysis_enabled',
    'browser_fingerprinting_enabled',
    'tls_fingerprinting_enabled',
    'device_fingerprinting_enabled',
    'rate_limiting_enabled',
    'honeypot_enabled',
    'challenge_response_enabled',
    'ip_reputation_enabled',
];

if (!in_array($feature, $valid_features, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid feature.']);
    exit();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // read current
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = ? LIMIT 1");
    $stmt->execute([$feature]);
    $current = $stmt->fetchColumn();
    $current = ($current === false) ? null : (string)$current;

    // decide new value
    if ($enabled !== null) {
        $val = (in_array($enabled, [true, 1, '1', 'true'], true)) ? '1' : '0';
    } else {
        $val = ($current === '1') ? '0' : '1';
    }

    // update then insert if missing (works even without UNIQUE(name))
    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE name = ?");
    $stmt->execute([$val, $feature]);

    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
        $stmt->execute([$feature, $val]);
    }

    echo json_encode([
        'success'   => true,
        'message'   => $feature . ' set to ' . ($val === '1' ? 'enabled' : 'disabled'),
        'new_state' => (int)($val === '1'),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
