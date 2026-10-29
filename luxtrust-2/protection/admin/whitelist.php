<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

include '../db.php';

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $stmt = $pdo->prepare("DELETE FROM whitelisted_ips WHERE ip_address = ?");
    $stmt->execute([$_POST['ip']]);
    header('Location: whitelist.php');
    exit();
}

$stmt = $pdo->query("SELECT ip_address FROM whitelisted_ips ORDER BY ip_address ASC");
$whitelist = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Whitelist Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 {
            margin: 0;
            font-size: 24px;
        }
        .back-btn {
            background-color: #3498db;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        .back-btn:hover {
            background-color: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 15px;
        }
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-delete:hover {
            background-color: #c0392b;
        }
        @media (max-width: 600px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            h2 {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <h2>Whitelisted IPs</h2>
        <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>
    <table>
        <thead>
            <tr><th>IP Address</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($whitelist as $ip): ?>
            <tr>
                <td><?= htmlspecialchars($ip) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="ip" value="<?= htmlspecialchars($ip) ?>">
                        <button type="submit" class="btn-delete">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
