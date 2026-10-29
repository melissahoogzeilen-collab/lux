<?php
include '../db.php';
header('Content-Type: application/json');

try {
    // Fetch visitor counts grouped by country
    $stmt = $pdo->query("SELECT country, COUNT(*) as visits FROM visitors GROUP BY country");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalVisits = array_sum(array_column($stats, 'visits'));
    $chartData = [];

    foreach ($stats as $stat) {
        $chartData[] = [
            'country' => $stat['country'], // Ensure country codes/names are resolved properly
            'visits' => $stat['visits'],
            'percentage' => round(($stat['visits'] / $totalVisits) * 100, 2),
        ];
    }

    echo json_encode([
        'success' => true,
        'totalVisits' => $totalVisits,
        'chartData' => $chartData,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
