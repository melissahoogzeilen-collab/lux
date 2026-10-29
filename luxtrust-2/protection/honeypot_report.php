<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

include 'db.php';
session_start();

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'];
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $ip_address = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $field_name = $input['field_name'] ?? '';
    $interaction_type = $input['interaction_type'] ?? '';
    $field_value = $input['field_value'] ?? '';
    $session_id = $input['session_id'] ?? session_id();
    
    // Log honeypot interaction
    $stmt = $pdo->prepare("INSERT INTO honeypot_interactions 
        (ip_address, user_agent, honeypot_type, field_name, field_value, session_id, interaction_time) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $ip_address,
        $user_agent,
        $interaction_type,
        $field_name,
        $field_value,
        $session_id
    ]);
    
    // Immediately mark this IP as suspicious
    $stmt = $pdo->prepare("UPDATE visitors 
        SET is_bot = 1, 
            block_reason = ?, 
            suspicious_behavior = 1,
            updated_at = NOW() 
        WHERE ip_address = ?");
    
    $block_reason = "Honeypot interaction: {$interaction_type} on field {$field_name}";
    $stmt->execute([$block_reason, $ip_address]);
    
    // Add to blocked IPs
    $stmt = $pdo->prepare("INSERT IGNORE INTO blocked_ips (ip_address, blocked_at, block_reason) 
        VALUES (?, NOW(), ?)");
    $stmt->execute([$ip_address, $block_reason]);
    
    // Log the incident
    error_log("HONEYPOT TRIGGERED: IP $ip_address interacted with honeypot field '$field_name' (type: $interaction_type)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Honeypot interaction recorded',
        'action' => 'blocked'
    ]);
    
} catch (Exception $e) {
    error_log("Honeypot report error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>

