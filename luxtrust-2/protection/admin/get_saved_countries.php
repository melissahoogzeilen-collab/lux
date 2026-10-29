<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query("SELECT country_code FROM allowed_countries ORDER BY country_code ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    echo json_encode(['success' => true, 'countries' => $countries]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
