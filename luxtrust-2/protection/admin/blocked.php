<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
include '../db.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ip = $_POST['ip'];

    // Remove from blocked_ips
    $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?");
    $stmt->execute([$ip]);

    // Optionally, unmark from visitors table
    $stmt = $pdo->prepare("UPDATE visitors SET blocked = 0 WHERE ip_address = ?");
    $stmt->execute([$ip]);

    header("Location: blocked.php");
    exit();
}

// Fetch blocked IPs and reasons
$stmt = $pdo->query("
    SELECT bi.ip_address, 
           IFNULL((
               SELECT MAX(block_reason) 
               FROM visitors 
               WHERE ip_address = bi.ip_address AND blocked = 1
           ), bi.block_reason) AS block_reason
    FROM blocked_ips bi
    ORDER BY bi.ip_address ASC
");



$blocked = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Blocked IPs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: system-ui, sans-serif;
      margin: 0;
      background: #f4f6f8;
      padding: 1rem;
    }
    .container {
      max-width: 900px;
      margin: auto;
      background: white;
      padding: 1rem;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    h2 {
      margin-top: 0;
      font-size: 1.6rem;
      color: #222;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    th, td {
      text-align: left;
      padding: 12px 10px;
      font-size: 15px;
      border-bottom: 1px solid #eee;
    }
    th {
      background: #f2f2f2;
    }
    .btn {
      padding: 6px 12px;
      font-size: 14px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .btn-remove {
      background-color: #e74c3c;
      color: white;
    }
    .btn-remove:hover {
      background-color: #c0392b;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 1rem;
      text-decoration: none;
      background: #3498db;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
    }
    .back-link:hover {
      background: #2980b9;
    }
    @media (max-width: 600px) {
      table, thead, tbody, th, td, tr {
        display: block;
        width: 100%;
      }
      th {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }
      td {
        padding-left: 50%;
        position: relative;
        border: none;
        border-bottom: 1px solid #eee;
      }
      td::before {
        position: absolute;
        top: 12px;
        left: 10px;
        width: 45%;
        white-space: nowrap;
        font-weight: bold;
        color: #777;
      }
      td:nth-of-type(1)::before { content: "IP Address"; }
      td:nth-of-type(2)::before { content: "Reason"; }
      td:nth-of-type(3)::before { content: "Action"; }
    }
  </style>
</head>
<body>
  <div class="container">
    <a class="back-link" href="dashboard.php">← Back to Dashboard</a>
    <h2>Blocked IP Addresses</h2>
    <table>
      <thead>
        <tr>
          <th>IP Address</th>
          <th>Block Reason</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($blocked)): ?>
        <tr><td colspan="3">No blocked IPs found.</td></tr>
      <?php else: ?>
        <?php foreach ($blocked as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['ip_address']) ?></td>
            <td><?= htmlspecialchars($row['block_reason'] ?? 'Unknown') ?></td>
            <td>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="ip" value="<?= htmlspecialchars($row['ip_address']) ?>">
                <button class="btn btn-remove" type="submit">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
