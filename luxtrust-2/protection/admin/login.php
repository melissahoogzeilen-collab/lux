<?php
declare(strict_types=1);
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin_logged_in'])) {
  header('Location: dashboard.php'); exit;
}

require_once __DIR__ . '/../db.php';      // MUST define $pdo (PDO connection)

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
$token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if ($token !== ($_SESSION['csrf'] ?? '')) {
    $error = 'Security check failed. Refresh the page and try again.';
} else {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
      $error = 'Please enter username and password.';
    } else {
      try {
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
      } catch (Throwable $e) {
        $admin = null;
      }

      $ok = false;
      if ($admin) {
        $stored = (string)($admin['password'] ?? '');
        // Legacy SHA-256 → upgrade on first successful login
        if (strlen($stored) === 64 && hash('sha256', $password) === $stored) {
          $new = password_hash($password, PASSWORD_DEFAULT);
          try {
            $pdo->prepare("UPDATE admins SET password=? WHERE id=?")->execute([$new, (int)$admin['id']]);
          } catch (Throwable $e) { /* ignore upgrade failure; still allow login */ }
          $ok = true;
        } else {
          // Modern hash
          $ok = password_verify($password, $stored);
        }
      }

      if ($ok) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $admin['username'] ?? $username;
        header('Location: dashboard.php'); exit;
      } else {
        $error = 'Invalid username or password.';
      }
    }
  }
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

ob_end_flush();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- If your dashboard already loads its CSS, point to it instead. This inline style mimics the dark design. -->
  <style>
    :root {
      --bg: #0f1b2b;
      --panel: #1c2a3a;
      --text: #e6edf3;
      --muted: #9fb0c3;
      --accent: #22c55e; /* green */
      --accent-hover: #16a34a;
      --danger: #ef4444;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      background: linear-gradient(180deg, #0b1420, #0f1b2b 40%, #0b1420);
      color: var(--text);
      min-height: 100vh; display: grid; place-items: center;
    }
    .card {
      width: 100%; max-width: 420px; background: var(--panel);
      border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.35);
      padding: 28px; border: 1px solid rgba(255,255,255,.06);
      animation: pop .2s ease-out;
    }
    @keyframes pop { from { transform: scale(.98); opacity: .6 } to { transform: scale(1); opacity: 1 } }
    h1 {
      margin: 0 0 18px; text-align: center; font-weight: 700; letter-spacing: .3px;
    }
    .field { margin-bottom: 14px; }
    label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px; }
    input[type="text"], input[type="password"] {
      width: 100%; padding: 12px 12px;
      background: #0f1b2b; border: 1px solid rgba(255,255,255,.08);
      color: var(--text); border-radius: 10px; outline: none;
    }
    input::placeholder { color: #7f8ea3; }
    .btn {
      width: 100%; padding: 12px 14px; border: 0; border-radius: 10px;
      background: var(--accent); color: #041019; font-weight: 700; cursor: pointer;
      transition: transform .05s ease, background .2s ease;
    }
    .btn:hover { background: var(--accent-hover); }
    .btn:active { transform: translateY(1px); }
    .error {
      background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.35);
      color: #fecaca; padding: 10px 12px; border-radius: 10px; margin-bottom: 14px; font-size: 14px;
    }
    .footer {
      margin-top: 12px; text-align: center; color: var(--muted); font-size: 12px;
    }
  </style>
</head>
<body>
  <form class="card" method="post" action="">
    <h1>Admin Login</h1>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="field">
      <label for="username">Username</label>
      <input id="username" name="username" type="text" placeholder="Username" autocomplete="username" required>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" placeholder="Password" autocomplete="current-password" required>
    </div>

    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <button class="btn" type="submit">Login</button>

    <div class="footer">Protected Area • Please sign in</div>
  </form>
</body>
</html>
