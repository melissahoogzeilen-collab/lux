<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
include '../db.php';

$baseDir = __DIR__ . '/reputation_lists';
if (!is_dir($baseDir)) {
    @mkdir($baseDir, 0755, true);
}

$lists = [
    'spamhaus_drop' => [
        'title' => 'Spamhaus DROP',
        'url' => 'https://www.spamhaus.org/drop/drop.txt',
        'file' => $baseDir . '/spamhaus_drop.txt',
        'type' => 'cidr',
        'setting' => 'block_spamhaus_drop_enabled',
        'description' => 'Do Not Route Or Peer ranges from Spamhaus.',
    ],
    'tor_exit_nodes' => [
        'title' => 'Tor Exit Nodes',
        'url' => 'https://check.torproject.org/torbulkexitlist',
        'file' => $baseDir . '/tor_exit_nodes.txt',
        'type' => 'ip',
        'setting' => 'block_tor_exit_nodes_enabled',
        'description' => 'Plain text list of Tor exit IPs.',
    ],
];

function rep_fetch_url(string $url): string {
    $data = false;
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'user_agent' => 'AntiBot-Reputation-Updater/1.0',
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $data = @file_get_contents($url, false, $context);
    }
    if (($data === false || $data === '') && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => 'AntiBot-Reputation-Updater/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $data = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($data === false || $data === '' || $code >= 400) {
            throw new RuntimeException('Download failed: HTTP ' . $code . ($err ? ' - ' . $err : ''));
        }
    }
    if ($data === false || $data === '') {
        throw new RuntimeException('Download failed. Enable allow_url_fopen or PHP cURL.');
    }
    return (string)$data;
}

function rep_parse_and_validate(string $raw, string $type): array {
    $lines = preg_split('/\R/', $raw) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '') continue;
        $entry = trim(preg_split('/[#;]/', $line, 2)[0]);
        if ($entry === '') continue;
        if ($type === 'cidr') {
            if (strpos($entry, '/') !== false) {
                [$ip, $bits] = array_pad(explode('/', $entry, 2), 2, '');
                if (filter_var(trim($ip), FILTER_VALIDATE_IP) && ctype_digit((string)$bits)) {
                    $out[] = trim($ip) . '/' . (int)$bits;
                }
            }
        } else {
            if (filter_var($entry, FILTER_VALIDATE_IP)) {
                $out[] = $entry;
            }
        }
    }
    $out = array_values(array_unique($out));
    if (count($out) < 1) {
        throw new RuntimeException('Downloaded list did not contain valid ' . ($type === 'cidr' ? 'CIDR ranges' : 'IP addresses') . '.');
    }
    sort($out, SORT_NATURAL);
    return $out;
}

function rep_atomic_write(string $file, array $entries, string $sourceUrl, string $title): void {
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('Cannot create folder: ' . $dir);
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('Folder is not writable: ' . $dir);
    }
    $body = [];
    $body[] = '# ' . $title;
    $body[] = '# Source: ' . $sourceUrl;
    $body[] = '# Updated: ' . date('Y-m-d H:i:s');
    $body[] = '# Count: ' . count($entries);
    foreach ($entries as $entry) $body[] = $entry;
    $content = implode("\n", $body) . "\n";
    $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        throw new RuntimeException('Cannot write temporary file. Check folder permission.');
    }
    @chmod($tmp, 0644);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException('Cannot replace list file. Check file permission.');
    }
}

function rep_file_stats(string $file): array {
    if (!is_file($file)) return ['exists'=>false, 'count'=>0, 'updated'=>'Never'];
    $count = 0;
    foreach ((file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) as $line) {
        $entry = trim(preg_split('/[#;]/', (string)$line, 2)[0]);
        if ($entry !== '') $count++;
    }
    return ['exists'=>true, 'count'=>$count, 'updated'=>date('Y-m-d H:i:s', filemtime($file))];
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $key = $_POST['list'] ?? '';
    try {
        if ($action === 'update_one' && isset($lists[$key])) {
            $cfg = $lists[$key];
            $raw = rep_fetch_url($cfg['url']);
            $entries = rep_parse_and_validate($raw, $cfg['type']);
            rep_atomic_write($cfg['file'], $entries, $cfg['url'], $cfg['title']);
            $message = $cfg['title'] . ' updated. ' . count($entries) . ' entries saved.';
        } elseif ($action === 'update_all') {
            $parts = [];
            foreach ($lists as $cfg) {
                $raw = rep_fetch_url($cfg['url']);
                $entries = rep_parse_and_validate($raw, $cfg['type']);
                rep_atomic_write($cfg['file'], $entries, $cfg['url'], $cfg['title']);
                $parts[] = $cfg['title'] . ': ' . count($entries);
            }
            $message = 'All reputation lists updated: ' . implode(' | ', $parts);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$settings = [];
try {
    $settings = $pdo->query("SELECT name,value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reputation Lists</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#1f2937}.container{max-width:1100px;margin:auto;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:20px}.top{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}a.btn,button{border:0;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}.btn{background:#2563eb;color:#fff}.btn.gray{background:#6b7280}.btn.green{background:#16a34a}.card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-top:16px}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;font-size:14px;vertical-align:top}.msg{background:#dcfce7;color:#166534;padding:10px;border-radius:10px;margin-top:12px}.err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;margin-top:12px}.muted{color:#6b7280;font-size:13px}.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:800}.on{background:#dcfce7;color:#166534}.off{background:#fee2e2;color:#991b1b}.actions{display:flex;gap:8px;flex-wrap:wrap}</style>
</head>
<body>
<div class="container">
  <div class="top">
    <div>
      <h2>Local Reputation Lists</h2>
      <div class="muted">Manual one-click updates for Spamhaus DROP and Tor exit node lists. No cron and no API key required.</div>
    </div>
    <a class="btn gray" href="dashboard.php">Back to Dashboard</a>
  </div>
  <?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <div class="card">
    <form method="post" class="actions">
      <input type="hidden" name="action" value="update_all">
      <button class="btn green" type="submit">Update All Lists Now</button>
    </form>
    <p class="muted">Folder must be writable by PHP: <code><?= htmlspecialchars($baseDir) ?></code>. Usually 755 works on cPanel when the file owner is correct.</p>
  </div>
  <table>
    <thead><tr><th>List</th><th>Status</th><th>Entries</th><th>Last Updated</th><th>Local File</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($lists as $key => $cfg): $st = rep_file_stats($cfg['file']); $enabled = ($settings[$cfg['setting']] ?? ($key === 'tor_exit_nodes' ? '0' : '1')) === '1'; ?>
      <tr>
        <td><strong><?= htmlspecialchars($cfg['title']) ?></strong><br><span class="muted"><?= htmlspecialchars($cfg['description']) ?></span></td>
        <td><span class="badge <?= $enabled ? 'on':'off' ?>"><?= $enabled ? 'Enabled':'Disabled' ?></span></td>
        <td><?= (int)$st['count'] ?></td>
        <td><?= htmlspecialchars($st['updated']) ?></td>
        <td><code><?= htmlspecialchars(str_replace(__DIR__ . '/', '', $cfg['file'])) ?></code></td>
        <td>
          <form method="post">
            <input type="hidden" name="action" value="update_one">
            <input type="hidden" name="list" value="<?= htmlspecialchars($key) ?>">
            <button class="btn" type="submit">Update</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
