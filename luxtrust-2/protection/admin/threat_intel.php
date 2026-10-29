<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit(); }
include '../db.php';

function ti_rows(PDO $pdo, string $sql): array {
    try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch (Throwable $e) { return []; }
}
function ti_table_exists(PDO $pdo, string $table): bool {
    try { $stmt = $pdo->prepare("SHOW TABLES LIKE ?"); $stmt->execute([$table]); return (bool)$stmt->fetchColumn(); } catch (Throwable $e) { return false; }
}

$topCountries    = ti_rows($pdo, "SELECT COALESCE(NULLIF(country,''),'unknown') AS label, COUNT(*) AS total FROM visitors WHERE is_bot = 1 GROUP BY label ORDER BY total DESC LIMIT 10");
$topReasons      = ti_rows($pdo, "SELECT COALESCE(NULLIF(block_reason,''),'N/A') AS label, COUNT(*) AS total FROM visitors WHERE is_bot = 1 GROUP BY label ORDER BY total DESC LIMIT 10");
$topUserAgents   = ti_rows($pdo, "SELECT LEFT(COALESCE(NULLIF(user_agent,''),'unknown'),120) AS label, COUNT(*) AS total FROM visitors WHERE is_bot = 1 GROUP BY label ORDER BY total DESC LIMIT 10");
$topIps          = ti_rows($pdo, "SELECT ip_address AS label, COUNT(*) AS total FROM visitors WHERE is_bot = 1 GROUP BY ip_address ORDER BY total DESC LIMIT 10");
$topRiskActions  = ti_table_exists($pdo, 'risk_events') ? ti_rows($pdo, "SELECT action_taken AS label, COUNT(*) AS total FROM risk_events GROUP BY action_taken ORDER BY total DESC LIMIT 10") : [];
$topRoutes       = ti_table_exists($pdo, 'risk_events') ? ti_rows($pdo, "SELECT route AS label, COUNT(*) AS total FROM risk_events GROUP BY route ORDER BY total DESC LIMIT 10") : [];
$topRiskReasons  = ti_table_exists($pdo, 'risk_events') ? ti_rows($pdo, "SELECT LEFT(COALESCE(NULLIF(reasons,''),'N/A'),160) AS label, COUNT(*) AS total FROM risk_events GROUP BY label ORDER BY total DESC LIMIT 10") : [];
$recentBlocks    = ti_rows($pdo, "SELECT ip_address, country, block_reason, created_at FROM visitors WHERE is_bot = 1 ORDER BY id DESC LIMIT 50");
$recentRisk      = ti_table_exists($pdo, 'risk_events') ? ti_rows($pdo, "SELECT created_at, ip_address, route, risk_score, action_taken, reasons FROM risk_events ORDER BY id DESC LIMIT 50") : [];
$summary = [
    'Total Visitors' => ti_rows($pdo, "SELECT COUNT(*) AS total FROM visitors")[0]['total'] ?? 0,
    'Blocked Visitors' => ti_rows($pdo, "SELECT COUNT(*) AS total FROM visitors WHERE is_bot = 1 OR blocked = 1")[0]['total'] ?? 0,
    'Risk Events' => ti_table_exists($pdo, 'risk_events') ? (ti_rows($pdo, "SELECT COUNT(*) AS total FROM risk_events")[0]['total'] ?? 0) : 0,
    'Temporary Blocks' => ti_table_exists($pdo, 'temporary_blocks') ? (ti_rows($pdo, "SELECT COUNT(*) AS total FROM temporary_blocks WHERE expires_at > NOW()")[0]['total'] ?? 0) : 0,
];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Threat Intelligence</title>
<style>body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#1f2937}.container{max-width:1400px;margin:auto;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:20px}.top{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}.btn{background:#6b7280;color:white;border-radius:10px;padding:10px 14px;text-decoration:none;font-weight:700}.summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}.stat{background:#eef2ff;border:1px solid #dbe4ff;border-radius:12px;padding:14px}.stat b{font-size:24px;color:#1d4ed8}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}@media(max-width:900px){.grid,.summary{grid-template-columns:1fr}}.card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-top:16px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:9px;border-bottom:1px solid #e5e7eb;font-size:13px;vertical-align:top}code{white-space:normal;word-break:break-word}.muted{color:#6b7280;font-size:13px}.count{font-weight:800;color:#dc2626}</style></head>
<body><div class="container">
<div class="top"><div><h2>Threat Intelligence</h2><div class="muted">Visibility from visitors and risk_events logs. This page does not block anyone.</div></div><a class="btn" href="dashboard.php">Back to Dashboard</a></div>
<div class="summary"><?php foreach ($summary as $k=>$v): ?><div class="stat"><div class="muted"><?= htmlspecialchars($k) ?></div><b><?= (int)$v ?></b></div><?php endforeach; ?></div>
<div class="grid">
<?php foreach ([['Top Blocked Countries',$topCountries],['Top Block Reasons',$topReasons],['Top Blocked User-Agents',$topUserAgents],['Top Blocked IPs',$topIps],['Risk Actions',$topRiskActions],['Top Risk Routes',$topRoutes],['Top Risk Reasons',$topRiskReasons]] as $box): ?>
<div class="card"><h3><?= htmlspecialchars($box[0]) ?></h3><table><thead><tr><th>Item</th><th>Count</th></tr></thead><tbody><?php if (!$box[1]): ?><tr><td colspan="2" class="muted">No data yet.</td></tr><?php endif; ?><?php foreach ($box[1] as $r): ?><tr><td><code><?= htmlspecialchars((string)$r['label']) ?></code></td><td class="count"><?= (int)$r['total'] ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php endforeach; ?>
</div>
<div class="card"><h3>Recent Risk Events</h3><table><thead><tr><th>Time</th><th>IP</th><th>Route</th><th>Score</th><th>Action</th><th>Reasons</th></tr></thead><tbody><?php if (!$recentRisk): ?><tr><td colspan="6" class="muted">No risk events yet.</td></tr><?php endif; ?><?php foreach ($recentRisk as $r): ?><tr><td><?= htmlspecialchars($r['created_at']) ?></td><td><?= htmlspecialchars($r['ip_address']) ?></td><td><code><?= htmlspecialchars($r['route']) ?></code></td><td class="count"><?= (int)$r['risk_score'] ?></td><td><?= htmlspecialchars($r['action_taken']) ?></td><td><code><?= htmlspecialchars($r['reasons'] ?? '') ?></code></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h3>Recent Blocked Visitors</h3><table><thead><tr><th>Time</th><th>IP</th><th>Country</th><th>Reason</th></tr></thead><tbody><?php if (!$recentBlocks): ?><tr><td colspan="4" class="muted">No blocked visitors yet.</td></tr><?php endif; ?><?php foreach ($recentBlocks as $r): ?><tr><td><?= htmlspecialchars($r['created_at']) ?></td><td><?= htmlspecialchars($r['ip_address']) ?></td><td><?= htmlspecialchars($r['country'] ?? '') ?></td><td><code><?= htmlspecialchars($r['block_reason'] ?? '') ?></code></td></tr><?php endforeach; ?></tbody></table></div>
</div></body></html>
