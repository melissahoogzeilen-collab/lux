<?php
// protection/admin/clear_data.php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../db.php'; // $pdo (ERRMODE_EXCEPTION)

$type = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : 'all';

$groups = [
  'visitors'     => ['visitors'],
  'fingerprints' => ['browser_fingerprints','device_fingerprints','tls_fingerprints'],
  'behavior'     => ['behavioral_patterns','honeypot_interactions','challenge_responses','rate_limits'],
  'blocks'       => ['blocked_ips','blocked_patterns'],
  'whitelist'    => ['whitelisted_ips'],
  'reputation'   => ['ip_reputation'],
  'all' => [
    'visitors',
    'browser_fingerprints','device_fingerprints','tls_fingerprints',
    'behavioral_patterns','honeypot_interactions','challenge_responses','rate_limits',
    'ip_reputation','blocked_ips','blocked_patterns','whitelisted_ips'
  ],
];
$tables = $groups[$type] ?? null;
if (!$tables) { echo json_encode(['success'=>false,'message'=>'Invalid type']); exit; }

function table_exists(PDO $pdo, string $t): bool {
    static $cache;
    if ($cache === null) {
        $q = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
        $cache = array_flip($q->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }
    return isset($cache[$t]);
}

function clear_table(PDO $pdo, string $t): void {
    // try TRUNCATE (fast) – it auto-commits in MySQL
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec("TRUNCATE TABLE `{$t}`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        return;
    } catch (Throwable $e) {
        // fall back to DELETE + reset AI (transaction not required)
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec("DELETE FROM `{$t}`");
        // reset AUTO_INCREMENT if supported
        try { $pdo->exec("ALTER TABLE `{$t}` AUTO_INCREMENT=1"); } catch (Throwable $ignore) {}
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    }
}

try {
    $cleared = [];
    foreach ($tables as $t) {
        if (table_exists($pdo, $t)) {
            clear_table($pdo, $t);
            $cleared[] = $t;
        }
    }
    echo json_encode(['success'=>true,'message'=>$cleared ? 'Cleared: '.implode(', ',$cleared) : 'Nothing to clear','cleared'=>$cleared]);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
