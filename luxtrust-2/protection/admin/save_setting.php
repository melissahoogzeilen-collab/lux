<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['admin_logged_in'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require __DIR__ . '/../db.php';

$raw = $_POST ?: json_decode(file_get_contents('php://input'), true);
$val = isset($raw['value']) ? trim((string)$raw['value']) : '';
if ($val === '') { echo json_encode(['success'=>false,'message'=>'Empty domain']); exit; }

$stmt = $pdo->prepare("
  INSERT INTO settings (name,value) VALUES ('captcha_domain',?)
  ON DUPLICATE KEY UPDATE value=VALUES(value)
");
$stmt->execute([$val]);

echo json_encode(['success'=>true,'message'=>'Saved']);
