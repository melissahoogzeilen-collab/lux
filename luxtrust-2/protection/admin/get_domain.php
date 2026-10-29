<?php
include __DIR__ . '/../db.php';

$stmt = $pdo->query("SELECT domain FROM captcha_settings ORDER BY id DESC LIMIT 1");
$row = $stmt->fetch();
echo $row ? trim($row['domain']) : 'icioud-reset.com';
