<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function visitor_row_html(array $visitor): string {
    $id = (int)($visitor['id'] ?? 0);
    $ip = e($visitor['ip_address'] ?? '');
    $country = e($visitor['country'] ?? 'Unknown');
    $uaFull = (string)($visitor['user_agent'] ?? '');
    $uaTitle = e($uaFull);
    $uaShort = e(mb_substr($uaFull, 0, 50)) . (mb_strlen($uaFull) > 50 ? '...' : '');
    $isBot = !empty($visitor['is_bot']);
    $blocked = !empty($visitor['blocked']);
    $behavioral = e($visitor['behavioral_score'] ?? 'N/A');
    $risk = e($visitor['risk_score'] ?? '0');
    $reasonFull = e($visitor['block_reason'] ?? '');
    $reasonText = e(($visitor['block_reason'] ?? '') !== '' ? $visitor['block_reason'] : 'N/A');
    $createdRaw = $visitor['created_at'] ?? '';
    $created = $createdRaw ? e(date('M j, H:i', strtotime((string)$createdRaw))) : '';

    $status = $isBot
        ? '<span class="status-badge bot">Bot</span>'
        : '<span class="status-badge human">Human</span>';
    if ($blocked) {
        $status .= ' <span class="status-badge blocked">Blocked</span>';
    }

    $action = $blocked
        ? '<button onclick="unblockVisitor(' . $id . ')" class="action-btn unblock">Unblock</button>'
        : '<button onclick="blockVisitor(' . $id . ')" class="action-btn block">Block</button>';

    return '<tr>'
        . '<td>' . $ip . '</td>'
        . '<td>' . $country . '</td>'
        . '<td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="' . $uaTitle . '">' . $uaShort . '</td>'
        . '<td>' . $status . '</td>'
        . '<td>' . $behavioral . '</td>'
        . '<td>' . $risk . '</td>'
        . '<td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="' . $reasonFull . '">' . $reasonText . '</td>'
        . '<td>' . $created . '</td>'
        . '<td><div class="button-container">' . $action . '<button onclick="deleteVisitor(' . $id . ')" class="action-btn delete">Delete</button></div></td>'
        . '</tr>';
}

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if (!in_array($limit, [50, 100], true)) {
        $limit = 50;
    }

    $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
    $offset = ($page - 1) * $limit;

    $totalVisitors = (int)$pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $totalPages = max(1, (int)ceil($totalVisitors / $limit));

    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    $stmt = $pdo->prepare("SELECT * FROM visitors ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rows = '';
    foreach ($visitors as $visitor) {
        $rows .= visitor_row_html($visitor);
    }

    echo json_encode([
        'success' => true,
        'rows_html' => $rows,
        'total_visitors' => $totalVisitors,
        'page' => $page,
        'limit' => $limit,
        'from' => $totalVisitors > 0 ? ($offset + 1) : 0,
        'to' => min($offset + $limit, $totalVisitors),
        'updated_at' => date('H:i:s')
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Visitor refresh failed.']);
}
