<?php
include 'inc/config.php';
include 'inc/connect.php';
date_default_timezone_set('Europe/Dublin');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    die('Unauthorized access');
}

// Clear the blacklist file
$file = "inc/blacklist.dat";
if (file_exists($file)) {
    file_put_contents($file, ""); // Clear the file contents
    header('Location: settings.php'); // Redirect after successful reset
    exit;
} else {
    die('Blacklist file does not exist');
}
?>
