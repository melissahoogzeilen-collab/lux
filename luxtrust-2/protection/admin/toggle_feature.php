<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../db.php';   // ✅ db.php is one level up
require_once __DIR__ . '/allowed_features.php';

// accept form POST or JSON
$raw = file_get_contents('php://input');
$js  = json_decode($raw, true) ?: [];
$feature = $_POST['feature'] ?? ($js['feature'] ?? null);

if (!$feature) {
    echo json_encode(['success' => false, 'message' => 'Missing feature']);
    exit;
}

if (!in_array($feature, $ALLOWED_FEATURE_TOGGLES, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid feature name']);
    exit;
}

try {
    // read current value
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = ? LIMIT 1");
    $stmt->execute([$feature]);
    $cur = $stmt->fetchColumn();

    $is_on = false;
    if ($cur !== false) {
        $v = strtolower((string)$cur);
        $is_on = ($v === '1' || $v === 'true' || $v === 'on' || $v === 'yes');
    }
    $new_on = !$is_on;
    $new_val = $new_on ? '1' : '0';

    // Keep CAPTCHA switches mutually exclusive without changing CAPTCHA files.
    if ($feature === 'captcha_enabled' && $new_val === '1') {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES ('math_captcha_enabled', '0') ON DUPLICATE KEY UPDATE value = '0'")->execute();
    }
    if ($feature === 'math_captcha_enabled' && $new_val === '1') {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES ('captcha_enabled', '0') ON DUPLICATE KEY UPDATE value = '0'")->execute();
    }

    // upsert
    $stmt = $pdo->prepare("
        INSERT INTO settings (name, value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)
    ");
    $stmt->execute([$feature, $new_val]);

    echo json_encode(['success' => true, 'status' => $new_on]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
