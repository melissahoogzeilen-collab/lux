<?php
// admin/search_visitors.php
// JSON API: search visitors with filters + pagination.
//
// GET params:
//   q, ip, ua, country, bot, blocked, score_min, score_max, date_from, date_to, op=and|or
//   page (1-based), limit (default 50), sort=(created_desc|created_asc|score_desc|score_asc|ip_asc|ip_desc)

declare(strict_types=1);
require __DIR__ . '/_auth.php';
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';
require __DIR__ . '/_search_query.php';

try {
    $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
    $page  = isset($_GET['page'])  ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    $sort = build_sort($_GET['sort'] ?? 'created_desc');

    // WHERE
    [$whereSql, $params] = build_visitor_where($_GET);

    // Count first
    $countSql = "SELECT COUNT(*) AS cnt FROM visitors" . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k=>$v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // Page data
    $sql = "SELECT id, ip_address, country, user_agent, is_bot, blocked, block_reason, created_at, behavioral_score
            FROM visitors " . $whereSql . " ORDER BY {$sort} LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $botsOnPage = 0;
    foreach ($rows as $r) if (!empty($r['is_bot'])) $botsOnPage++;

    echo json_encode([
        'success'      => true,
        'data'         => $rows,
        'page'         => $page,
        'limit'        => $limit,
        'total'        => $total,
        'total_pages'  => max(1, (int)ceil($total / $limit)),
        'bots_on_page' => $botsOnPage,
    ], JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Error: ' . $e->getMessage()]);
}
