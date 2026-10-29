<?php
include 'db.php'; // Include database connection

// Get real IP address safely
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // First IP in chain
    }
    return $_SERVER['REMOTE_ADDR'];
}

$fingerprint_id = $_COOKIE['fingerprint_id'] ?? null;
$ip_address = get_client_ip();

try {
    // Check if the visitor is in the database
    $stmt = $pdo->prepare("SELECT * FROM visitors WHERE fingerprint_id = ? OR ip_address = ?");
    $stmt->execute([$fingerprint_id, $ip_address]);
    $visitor = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the visitor is blocked, send 403 response and exit
    if ($visitor && $visitor['blocked']) {
        header('HTTP/1.0 403 Forbidden');
        echo '<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>';
        exit;
    }
} catch (Exception $e) {
    file_put_contents('admin/error_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . "\n", FILE_APPEND);
    header('HTTP/1.0 500 Internal Server Error');
    echo '<h1>500 Internal Server Error</h1><p>An unexpected error occurred.</p>';
    exit;
}
?>
