<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
include '../db.php';

$rows = [];
$error = '';
try {
    $rows = $pdo->query("SELECT ip_address, route, risk_score, reasons, action_taken, created_at FROM risk_events ORDER BY id DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $error = 'Run the SQL migration first: risk_events table is missing.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Risk Events</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#1f2937}.container{max-width:1200px;margin:auto;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:20px}.top{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}.btn{background:#6b7280;color:white;border-radius:10px;padding:10px 14px;text-decoration:none;font-weight:700}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;font-size:13px;vertical-align:top}code{white-space:normal;word-break:break-word}.score{font-weight:800}.bad{color:#dc2626}.mid{color:#d97706}.ok{color:#16a34a}.err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;margin-top:12px}.muted{color:#6b7280}</style>
</head>
<body>
<div class="container">
  <div class="top">
    <div><h2>Risk Events</h2><div class="muted">Latest adaptive risk and route-limit events.</div></div>
    <a class="btn" href="dashboard.php">Back to Dashboard</a>
  </div>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <table>
    <thead><tr><th>Time</th><th>IP</th><th>Score</th><th>Action</th><th>Route</th><th>Reasons</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted">No risk events yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): $score=(int)$r['risk_score']; ?>
      <tr>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td><?= htmlspecialchars($r['ip_address']) ?></td>
        <td class="score <?= $score>=75?'bad':($score>=35?'mid':'ok') ?>"><?= $score ?></td>
        <td><?= htmlspecialchars($r['action_taken']) ?></td>
        <td><code><?= htmlspecialchars($r['route']) ?></code></td>
        <td><code><?= htmlspecialchars($r['reasons'] ?? '') ?></code></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
