<?php
// protection/admin/domain_config.php
require __DIR__ . '/../db.php';

$captcha_domain = 'secure.yourdomain.com'; // fallback

try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'captcha_domain' LIMIT 1");
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $captcha_domain = trim($row['value']) ?: $captcha_domain;
    }
} catch (Throwable $e) {
    // fallback remains
}
