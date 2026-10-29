<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit(); }
$files = [
    ['title'=>'GeoLite2 Country', 'file'=>__DIR__ . '/GeoLite2-Country.mmdb', 'purpose'=>'Country lookup and country allow/risk rules'],
    ['title'=>'GeoLite2 ASN', 'file'=>__DIR__ . '/GeoLite2-ASN.mmdb', 'purpose'=>'ASN/provider lookup and ASN rules'],
];
function gs_age_text($mtime): string {
    if (!$mtime) return 'missing';
    $days = floor((time() - $mtime) / 86400);
    if ($days < 0) $days = 0;
    return $days . ' day(s) old';
}
function gs_size($bytes): string {
    if (!$bytes) return '-';
    if ($bytes > 1048576) return round($bytes / 1048576, 2) . ' MB';
    return round($bytes / 1024, 2) . ' KB';
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>GeoIP Database Status</title>
<style>body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#1f2937}.container{max-width:1100px;margin:auto;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:20px}.top{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}.btn{background:#6b7280;color:white;border-radius:10px;padding:10px 14px;text-decoration:none;font-weight:700}.card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-top:16px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;font-size:14px}.ok{color:#16a34a;font-weight:800}.warn{color:#d97706;font-weight:800}.bad{color:#dc2626;font-weight:800}.muted{color:#6b7280;font-size:13px}code{word-break:break-word}</style></head>
<body><div class="container"><div class="top"><div><h2>GeoIP Database Status</h2><div class="muted">No API is used. This page only shows local database freshness. Replace files manually when needed.</div></div><a class="btn" href="dashboard.php">Back to Dashboard</a></div>
<div class="card"><table><thead><tr><th>Database</th><th>Status</th><th>Age</th><th>Modified</th><th>Size</th><th>Purpose</th></tr></thead><tbody>
<?php foreach ($files as $f): $exists=is_file($f['file']); $mtime=$exists?filemtime($f['file']):0; $days=$mtime?floor((time()-$mtime)/86400):9999; $cls=$exists?($days>45?'warn':'ok'):'bad'; ?>
<tr><td><code><?= htmlspecialchars(basename($f['file'])) ?></code></td><td class="<?= $cls ?>"><?= $exists?($days>45?'Old':'OK'):'Missing' ?></td><td><?= htmlspecialchars(gs_age_text($mtime)) ?></td><td><?= $mtime?date('Y-m-d H:i:s',$mtime):'-' ?></td><td><?= $exists?gs_size(filesize($f['file'])):'-' ?></td><td><?= htmlspecialchars($f['purpose']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="card"><h3>Best practice</h3><p>Keep the MMDB files. They are fast and do not slow visitors. Update them manually when they become old, for example every 2-4 weeks. Do not replace this with live API calls on every visit.</p><p class="muted">Upload fresh files with the same names into <code>admin/</code>: <code>GeoLite2-Country.mmdb</code> and <code>GeoLite2-ASN.mmdb</code>.</p></div>
</div></body></html>
