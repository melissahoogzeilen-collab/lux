<?php
include __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_domain'])) {
    $newDomain = trim($_POST['new_domain']);

    // Update existing row or insert if not exists
    $stmt = $pdo->prepare("SELECT id FROM captcha_settings LIMIT 1");
    $stmt->execute();
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE captcha_settings SET domain = ? WHERE id = ?");
        $stmt->execute([$newDomain, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO captcha_settings (domain) VALUES (?)");
        $stmt->execute([$newDomain]);
    }

    echo json_encode(['status' => 'ok']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
