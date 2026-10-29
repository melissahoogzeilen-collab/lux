<?php
// status.php — unchanged logic; single endpoint at project root used by script-pages.js

$__dir = __DIR__;
$roots = [
  $__dir,
  dirname($__dir),
  dirname(dirname($__dir)),
  dirname(dirname(dirname($__dir)))
];

$bootstrapped = false;
foreach ($roots as $root) {
  $cfg = $root . '/void/inc/config.php';
  $cnn = $root . '/void/inc/connect.php';
  if (is_file($cfg) && is_file($cnn)) {
    require_once $cfg;
    require_once $cnn; // should create $conn (mysqli)
    $bootstrapped = true;
    break;
  }
}

if (!$bootstrapped) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'bootstrap not found'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
$uniqueid = $_SESSION['uniqueid'] ?? '';
@session_write_close();

$type = $_GET['type'] ?? 'getstatus';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($type === 'health') {
  $ok = false;
  try { $ok = $GLOBALS['conn'] && $GLOBALS['conn']->ping(); } catch(Throwable $e) {}
  echo json_encode(['status'=>$ok?'ok':'degraded','timestamp'=>time()], JSON_UNESCAPED_SLASHES);
  exit;
}

if ($type !== 'getstatus') {
  http_response_code(400);
  echo json_encode(['error'=>'invalid type'], JSON_UNESCAPED_SLASHES);
  exit;
}

if ($uniqueid === '') {
  echo json_encode(['status'=>'','redirect_url'=>'','timestamp'=>time(),'last_updated'=>null], JSON_UNESCAPED_SLASHES);
  exit;
}

try {
  $stmt = $conn->prepare("SELECT status, custom_redirect, last_updated FROM clients WHERE uniqueid = ? LIMIT 1");
  if (!$stmt) throw new Exception('prepare failed');
  $stmt->bind_param('s', $uniqueid);
  if (!$stmt->execute()) throw new Exception('execute failed');
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  if (!$row) $row = ['status'=>'','custom_redirect'=>'','last_updated'=>null];
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'db error'], JSON_UNESCAPED_SLASHES);
  exit;
}

echo json_encode([
  'status'       => $row['status'],
  'redirect_url' => $row['custom_redirect'] ?? '',
  'timestamp'    => time(),
  'last_updated' => $row['last_updated']
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
