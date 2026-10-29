<?php
include 'db.php'; // Include database connection

function check_visitor() {
    $fingerprint_id = $_COOKIE['fingerprint_id'] ?? null; // Fingerprint from cookies
    $ip_address = $_SERVER['REMOTE_ADDR'];

    try {
        global $pdo; // Use the global database connection

        // Check if the visitor exists in the database
        $stmt = $pdo->prepare("SELECT * FROM visitors WHERE fingerprint_id = ? OR ip_address = ?");
        $stmt->execute([$fingerprint_id, $ip_address]);
        $visitor = $stmt->fetch(PDO::FETCH_ASSOC);

        // If visitor is blocked, terminate immediately
        if ($visitor && $visitor['blocked']) {
            terminate_request('You are blocked.');
        }
    } catch (Exception $e) {
        // Log errors and block by default
        file_put_contents('admin/error_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . "\n", FILE_APPEND);
        terminate_request('An error occurred.');
    }
}

// Function to terminate the request with a 404
function terminate_request($message) {
    header_remove();
    header("Connection: close\r\n");
    http_response_code(404);
    die("<h1>404 Not Found</h1><p>$message</p>");
}
