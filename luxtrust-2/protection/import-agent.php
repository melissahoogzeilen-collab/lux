<?php
include 'db.php'; // db.php is one level up

$file = 'admin/blocked_user_agents.txt'; // relative path to the .txt file

if (!file_exists($file)) {
    die("File not found at: $file");
}

$lines = array_map('trim', file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
$imported = 0;

foreach ($lines as $pattern) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO blocked_user_agents (pattern) VALUES (?)");
    if ($stmt->execute([$pattern])) {
        $imported++;
    }
}

echo "✅ Imported $imported user-agent patterns from file.";
