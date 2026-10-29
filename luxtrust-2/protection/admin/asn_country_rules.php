<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
include '../db.php';

$message = '';
$error = '';

function ac_clean_country(string $c): string {
    return strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $c), 0, 2));
}
function ac_action(string $a): string {
    return in_array($a, ['score','block','allow'], true) ? $a : 'score';
}
function ac_score($v): int {
    $n = (int)$v;
    if ($n < 0) $n = 0;
    if ($n > 100) $n = 100;
    return $n;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scope = $_POST['scope'] ?? '';
    $action = $_POST['action'] ?? '';
    try {
        if ($scope === 'country' && $action === 'add') {
            $country = ac_clean_country((string)($_POST['country_code'] ?? ''));
            if (strlen($country) !== 2) throw new RuntimeException('Country code must be 2 letters, example: US, TN, FR.');
            $ruleAction = ac_action((string)($_POST['rule_action'] ?? 'score'));
            $score = ac_score($_POST['risk_score'] ?? 20);
            $note = trim((string)($_POST['note'] ?? ''));
            $stmt = $pdo->prepare("INSERT INTO country_risk_rules (country_code, action, risk_score, note, enabled, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE action = VALUES(action), risk_score = VALUES(risk_score), note = VALUES(note), enabled = 1, updated_at = NOW()");
            $stmt->execute([$country, $ruleAction, $score, $note]);
            $message = 'Country rule saved.';
        } elseif ($scope === 'asn' && $action === 'add') {
            $asnRaw = trim((string)($_POST['asn_number'] ?? ''));
            $asnNumber = $asnRaw === '' ? null : (int)preg_replace('/[^0-9]/', '', $asnRaw);
            $orgPattern = trim((string)($_POST['org_pattern'] ?? ''));
            if (($asnNumber === null || $asnNumber <= 0) && $orgPattern === '') {
                throw new RuntimeException('Enter ASN number or organization pattern.');
            }
            if (strlen($orgPattern) > 190) throw new RuntimeException('Organization pattern must be under 190 characters.');
            $ruleAction = ac_action((string)($_POST['rule_action'] ?? 'score'));
            $score = ac_score($_POST['risk_score'] ?? 25);
            $note = trim((string)($_POST['note'] ?? ''));
            $stmt = $pdo->prepare("INSERT INTO asn_risk_rules (asn_number, org_pattern, action, risk_score, note, enabled, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE action = VALUES(action), risk_score = VALUES(risk_score), note = VALUES(note), enabled = 1, updated_at = NOW()");
            $stmt->execute([$asnNumber, $orgPattern, $ruleAction, $score, $note]);
            $message = 'ASN rule saved.';
        } elseif ($action === 'toggle') {
            $table = $scope === 'country' ? 'country_risk_rules' : 'asn_risk_rules';
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE `$table` SET enabled = IF(enabled=1,0,1), updated_at = NOW() WHERE id = ?")->execute([$id]);
            $message = 'Rule updated.';
        } elseif ($action === 'delete') {
            $table = $scope === 'country' ? 'country_risk_rules' : 'asn_risk_rules';
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
            $message = 'Rule deleted.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$countryRows = [];
$asnRows = [];
try { $countryRows = $pdo->query("SELECT * FROM country_risk_rules ORDER BY enabled DESC, country_code ASC")->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch (Throwable $e) { $error = $error ?: 'Run the SQL migration first: country_risk_rules table is missing.'; }
try { $asnRows = $pdo->query("SELECT * FROM asn_risk_rules ORDER BY enabled DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch (Throwable $e) { $error = $error ?: 'Run the SQL migration first: asn_risk_rules table is missing.'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ASN & Country Rules</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#1f2937}.container{max-width:1200px;margin:auto;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:20px}.top{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}.btn,button{background:#2563eb;color:#fff;border:0;border-radius:10px;padding:10px 14px;text-decoration:none;font-weight:700;cursor:pointer}.btn.gray{background:#6b7280}.btn.red{background:#dc2626}.btn.green{background:#16a34a}.card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-top:16px}input,select{width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:10px}.grid4{display:grid;grid-template-columns:110px 120px 110px 1fr auto;gap:10px;align-items:end}.grid5{display:grid;grid-template-columns:120px 1fr 120px 110px 1fr auto;gap:10px;align-items:end}@media(max-width:950px){.grid4,.grid5{grid-template-columns:1fr}}table{width:100%;border-collapse:collapse;margin-top:14px}th,td{text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;font-size:14px;vertical-align:top}.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:800}.allow{background:#dcfce7;color:#166534}.score{background:#fef3c7;color:#92400e}.block{background:#fee2e2;color:#991b1b}.off{background:#e5e7eb;color:#374151}.msg{background:#dcfce7;color:#166534;padding:10px;border-radius:10px;margin-top:12px}.err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;margin-top:12px}.muted{color:#6b7280;font-size:13px}.actions{display:flex;gap:8px;flex-wrap:wrap}</style>
</head>
<body>
<div class="container">
  <div class="top"><div><h2>ASN & Country Rules</h2><div class="muted">Safe scoring manager. Empty tables = no effect. Use score first, block only when you are sure.</div></div><a class="btn gray" href="dashboard.php">Back to Dashboard</a></div>
  <?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <h3>Country Risk Rule</h3>
    <form method="post" class="grid4">
      <input type="hidden" name="scope" value="country"><input type="hidden" name="action" value="add">
      <div><label>Country</label><input name="country_code" placeholder="US" maxlength="2" required></div>
      <div><label>Action</label><select name="rule_action"><option value="score">Score only</option><option value="block">Block</option><option value="allow">Allow</option></select></div>
      <div><label>Score</label><input name="risk_score" type="number" min="0" max="100" value="20"></div>
      <div><label>Note</label><input name="note" placeholder="Optional reason"></div>
      <button class="btn green" type="submit">Save</button>
    </form>
    <table><thead><tr><th>Country</th><th>Action</th><th>Score</th><th>Note</th><th>Status</th><th>Manage</th></tr></thead><tbody>
    <?php if (!$countryRows): ?><tr><td colspan="6" class="muted">No country rules yet.</td></tr><?php endif; ?>
    <?php foreach ($countryRows as $r): ?><tr>
      <td><?= htmlspecialchars($r['country_code']) ?></td><td><span class="badge <?= htmlspecialchars($r['action']) ?>"><?= htmlspecialchars($r['action']) ?></span></td><td><?= (int)$r['risk_score'] ?></td><td><?= htmlspecialchars($r['note'] ?? '') ?></td><td><?= ((int)$r['enabled']===1)?'Enabled':'Disabled' ?></td>
      <td class="actions"><form method="post"><input type="hidden" name="scope" value="country"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit" class="btn gray">Toggle</button></form><form method="post" onsubmit="return confirm('Delete rule?')"><input type="hidden" name="scope" value="country"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit" class="btn red">Delete</button></form></td>
    </tr><?php endforeach; ?></tbody></table>
  </div>

  <div class="card">
    <h3>ASN / Organization Rule</h3>
    <form method="post" class="grid5">
      <input type="hidden" name="scope" value="asn"><input type="hidden" name="action" value="add">
      <div><label>ASN</label><input name="asn_number" placeholder="AS16509"></div>
      <div><label>Org pattern</label><input name="org_pattern" placeholder="Amazon / OVH / Hetzner"></div>
      <div><label>Action</label><select name="rule_action"><option value="score">Score only</option><option value="block">Block</option><option value="allow">Allow</option></select></div>
      <div><label>Score</label><input name="risk_score" type="number" min="0" max="100" value="25"></div>
      <div><label>Note</label><input name="note" placeholder="Optional reason"></div>
      <button class="btn green" type="submit">Save</button>
    </form>
    <table><thead><tr><th>ASN</th><th>Org Pattern</th><th>Action</th><th>Score</th><th>Note</th><th>Status</th><th>Manage</th></tr></thead><tbody>
    <?php if (!$asnRows): ?><tr><td colspan="7" class="muted">No ASN rules yet.</td></tr><?php endif; ?>
    <?php foreach ($asnRows as $r): ?><tr>
      <td><?= $r['asn_number'] ? 'AS'.(int)$r['asn_number'] : '-' ?></td><td><?= htmlspecialchars($r['org_pattern'] ?? '') ?></td><td><span class="badge <?= htmlspecialchars($r['action']) ?>"><?= htmlspecialchars($r['action']) ?></span></td><td><?= (int)$r['risk_score'] ?></td><td><?= htmlspecialchars($r['note'] ?? '') ?></td><td><?= ((int)$r['enabled']===1)?'Enabled':'Disabled' ?></td>
      <td class="actions"><form method="post"><input type="hidden" name="scope" value="asn"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit" class="btn gray">Toggle</button></form><form method="post" onsubmit="return confirm('Delete rule?')"><input type="hidden" name="scope" value="asn"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit" class="btn red">Delete</button></form></td>
    </tr><?php endforeach; ?></tbody></table>
  </div>
</div>
</body>
</html>
