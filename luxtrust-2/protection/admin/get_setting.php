<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['admin_logged_in'])) { echo json_encode(['value'=>null]); exit; }
require __DIR__ . '/../db.php';
$name = $_GET['name'] ?? '';
if ($name !== 'captcha_domain') { echo json_encode(['value'=>null]); exit; }
$stmt = $pdo->query("SELECT value FROM settings WHERE name='captcha_domain' LIMIT 1");
echo json_encode(['value' => ($stmt->fetchColumn() ?: null)]);
