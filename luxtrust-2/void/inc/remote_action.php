<?php
// remote_action.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'connect.php';

// Validate required parameters.
if (!isset($_GET['type']) || !isset($_GET['uniqueid'])) {
    echo "Invalid request.";
    exit;
}

$type = $_GET['type'];
$uniqueid = mysqli_real_escape_string($conn, $_GET['uniqueid']);

if ($type == 'change_status') {
    if (!isset($_GET['status'])) {
        echo "Status not provided.";
        exit;
    }
    $status = intval($_GET['status']);
    $query = mysqli_query($conn, "UPDATE clients SET status = $status, buzzed = 0 WHERE uniqueid = '$uniqueid'");
    if ($query) {
        echo "Status updated to $status for UniqueID: $uniqueid.";
    } else {
        echo "Failed to update status for UniqueID: $uniqueid.";
    }
} elseif ($type == 'custom_redirect') {
    // Implement your custom redirect logic here.
    echo "Custom redirect selected for UniqueID: $uniqueid (Not implemented).";
} else {
    echo "Invalid action type.";
}
?>
