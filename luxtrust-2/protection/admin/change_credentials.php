<?php
session_start();
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (empty($_SESSION['username'])) {
    // Prefer using an admin_id in session; for now, guard against missing username.
    die('Session missing username. Please log in again.');
}

include '../db.php'; // must define $pdo (PDO)

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch the current hash for this admin
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $stored_hash = $stmt->fetchColumn();

    if (!$stored_hash) {
        $error = "Admin account not found. Please re-login.";
    } elseif (strlen($new_password) < 5) { // fix: enforce 6+
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Support both legacy sha256 and password_hash()-based hashes
        $matches =
            (password_get_info($stored_hash)['algo'] !== 0 && password_verify($current_password, $stored_hash))
            || hash('sha256', $current_password) === $stored_hash;

        if (!$matches) {
            $error = "Current password is incorrect.";
        } else {
            // Always rehash to a modern hash (bcrypt by default) to migrate legacy sha256
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
            $upd->execute([$new_hash, $_SESSION['username']]);
            $success = "Password updated successfully.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Admin Password</title>
  <style>
    body {
      background: #f2f2f2;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      width: 100%;
      max-width: 420px;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #222;
    }
    input {
      width: 100%;
      padding: 12px 14px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 15px;
      box-sizing: border-box;
    }
    button {
      width: 100%;
      padding: 12px;
      background-color: #007bff;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
    }
    button:hover {
      background-color: #0056b3;
    }
    .msg {
      text-align: center;
      margin-bottom: 15px;
      font-size: 14px;
    }
    .success { color: green; }
    .error { color: red; }
    .back {
      display: block;
      text-align: center;
      margin-top: 15px;
      text-decoration: none;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Change Admin Password</h2>

    <?php if ($success): ?>
      <div class="msg success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
      <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="password" name="current_password" placeholder="Current Password" required>
      <input type="password" name="new_password" placeholder="New Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit">Save Password</button>
    </form>

    <a class="back" href="dashboard.php">&larr; Back to Dashboard</a>
  </div>
</body>
</html>
