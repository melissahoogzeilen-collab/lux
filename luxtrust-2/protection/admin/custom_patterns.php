<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
include '../db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $type = ($_POST['type'] ?? '') === 'whitelist' ? 'whitelist' : 'blocklist';
            $pattern = trim((string)($_POST['pattern'] ?? ''));
            $note = trim((string)($_POST['note'] ?? ''));
            if ($pattern === '' || strlen($pattern) > 190) {
                throw new RuntimeException('Pattern is required and must be under 190 characters.');
            }
            $stmt = $pdo->prepare("INSERT INTO custom_ua_patterns (type, pattern, note, enabled, created_at, updated_at)
                                   VALUES (?, ?, ?, 1, NOW(), NOW())
                                   ON DUPLICATE KEY UPDATE note = VALUES(note), enabled = 1, updated_at = NOW()");
            $stmt->execute([$type, $pattern, $note]);
            $message = 'Pattern saved.';
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE custom_ua_patterns SET enabled = IF(enabled=1,0,1), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Pattern updated.';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM custom_ua_patterns WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Pattern deleted.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$rows = [];
try {
    $rows = $pdo->query("SELECT id, type, pattern, note, enabled, created_at FROM custom_ua_patterns ORDER BY type DESC, enabled DESC, pattern ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $error = 'Run the SQL migration first: custom_ua_patterns table is missing.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Custom UA Patterns</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#1f2937}.container{max-width:1100px;margin:auto;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:20px}.top{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}a.btn,button{border:0;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}.btn{background:#2563eb;color:#fff}.btn.gray{background:#6b7280}.btn.red{background:#dc2626}.btn.green{background:#16a34a}.card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-top:16px}input,select{width:100%;box-sizing:border-box;padding:12px;border:1px solid #d1d5db;border-radius:10px;font-size:15px}.grid{display:grid;grid-template-columns:160px 1fr 1fr auto;gap:10px;align-items:end}@media(max-width:850px){.grid{grid-template-columns:1fr}}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;font-size:14px;vertical-align:top}.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:800}.badge.white{background:#dcfce7;color:#166534}.badge.block{background:#fee2e2;color:#991b1b}.msg{background:#dcfce7;color:#166534;padding:10px;border-radius:10px;margin-top:12px}.err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;margin-top:12px}.muted{color:#6b7280;font-size:13px}.actions{display:flex;gap:8px;flex-wrap:wrap}</style>
</head>
<body>
<div class="container">
  <div class="top">
    <div>
      <h2>Custom User-Agent Patterns</h2>
      <div class="muted">Whitelist or block exact words found inside the User-Agent. Case-insensitive.</div>
    </div>
    <a class="btn gray" href="dashboard.php">Back to Dashboard</a>
  </div>
  <?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="post" class="grid">
      <input type="hidden" name="action" value="add">
      <div><label>Type</label><select name="type"><option value="whitelist">Whitelist / allow</option><option value="blocklist">Blocklist / block</option></select></div>
      <div><label>Pattern</label><input name="pattern" placeholder="Example: UptimeRobot or MyApp/1.0" maxlength="190" required></div>
      <div><label>Note</label><input name="note" placeholder="Optional reason"></div>
      <button class="btn green" type="submit">Add Pattern</button>
    </form>
  </div>

  <table>
    <thead><tr><th>Type</th><th>Pattern</th><th>Note</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="5" class="muted">No custom patterns yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><span class="badge <?= $r['type']==='whitelist'?'white':'block' ?>"><?= htmlspecialchars($r['type']) ?></span></td>
        <td><strong><?= htmlspecialchars($r['pattern']) ?></strong></td>
        <td><?= htmlspecialchars($r['note'] ?? '') ?></td>
        <td><?= ((int)$r['enabled']===1) ? 'Enabled' : 'Disabled' ?></td>
        <td class="actions">
          <form method="post"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn" type="submit"><?= ((int)$r['enabled']===1) ? 'Disable' : 'Enable' ?></button></form>
          <form method="post" onsubmit="return confirm('Delete this pattern?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn red" type="submit">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
