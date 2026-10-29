<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$is_html = (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false);
if (empty($_SESSION['admin_logged_in'])) {
  if ($is_html) { header('Location: login.php'); exit; }
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}
