<?php
header('Content-Type: application/json');
include '../db.php'; // Use DB directly, not check.php

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;
$id = $data['id'] ?? null;

if (!$action || !$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

function block_user($id) {
    global $pdo;

    // Mark as blocked
    $stmt = $pdo->prepare("UPDATE visitors SET blocked = 1 WHERE id = ?");
    $stmt->execute([$id]);

    // Get IP
    $stmt = $pdo->prepare("SELECT ip_address FROM visitors WHERE id = ?");
    $stmt->execute([$id]);
    $ip = $stmt->fetchColumn();

    if ($ip) {
        // Remove from whitelist
        $pdo->prepare("DELETE FROM whitelisted_ips WHERE ip_address = ?")->execute([$ip]);
        // Add to blocklist
        $pdo->prepare("INSERT IGNORE INTO blocked_ips (ip_address) VALUES (?)")->execute([$ip]);
    }

    return true;
}

function unblock_user($id) {
    global $pdo;

    // Mark as unblocked
    $stmt = $pdo->prepare("UPDATE visitors SET blocked = 0 WHERE id = ?");
    $stmt->execute([$id]);

    // Get IP
    $stmt = $pdo->prepare("SELECT ip_address FROM visitors WHERE id = ?");
    $stmt->execute([$id]);
    $ip = $stmt->fetchColumn();

    if ($ip) {
        // Remove from blocklist
        $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?")->execute([$ip]);
        // Add to whitelist
        $pdo->prepare("INSERT IGNORE INTO whitelisted_ips (ip_address) VALUES (?)")->execute([$ip]);
    }

    return true;
}

function delete_user($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM visitors WHERE id = ?");
    return $stmt->execute([$id]);
}

try {
    switch ($action) {
        case 'block':
            $result = block_user($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Visitor blocked successfully.' : 'Failed to block visitor.']);
            break;

        case 'unblock':
            $result = unblock_user($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Visitor unblocked successfully.' : 'Failed to unblock visitor.']);
            break;

        case 'delete':
            $result = delete_user($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Visitor deleted successfully.' : 'Failed to delete visitor.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
