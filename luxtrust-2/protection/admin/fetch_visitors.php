<?php
header('Content-Type: application/json');
include '../db.php';

try {
    // Get limit and page from GET parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
    $offset = ($page - 1) * $limit;

    // Fetch paginated visitors
    $stmt = $pdo->prepare("SELECT * FROM visitors ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count all visitors
    $totalVisitors = (int)$pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $totalBotsStmt = $pdo->query("SELECT COUNT(*) FROM visitors WHERE is_bot = 1");
    $totalBots = (int)$totalBotsStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => $visitors,
        'totalVisitors' => $totalVisitors,
        'totalBots' => $totalBots
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
