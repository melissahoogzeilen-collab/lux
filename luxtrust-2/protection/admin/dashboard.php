<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

include '../db.php';
require_once __DIR__ . '/allowed_features.php';

// Handle AJAX actions
// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $csrf_token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($csrf_token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Security token expired. Refresh dashboard and try again.']);
        exit();
    }

    $action  = $_POST['action']  ?? null;
    $id      = $_POST['id']      ?? null;

    if (!$action) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit();
    }

    try {
        switch ($action) {
case 'toggle_feature': {
    $feature = $_POST['feature'] ?? null;
    $enabled = $_POST['enabled'] ?? '0';
    if (!$feature || !in_array($feature, $ALLOWED_FEATURE_TOGGLES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid feature name.']);
        exit();
    }

    // Keep CAPTCHA switches mutually exclusive without changing CAPTCHA files.
    if ($feature === 'captcha_enabled' && $enabled === '1') {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES ('math_captcha_enabled', '0') ON DUPLICATE KEY UPDATE value = '0'")->execute();
    }
    if ($feature === 'math_captcha_enabled' && $enabled === '1') {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES ('captcha_enabled', '0') ON DUPLICATE KEY UPDATE value = '0'")->execute();
    }

    // 1) Try update existing row
    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE name = ?");
    $stmt->execute([$enabled, $feature]);

    // 2) If nothing updated, insert a new row
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
        $stmt->execute([$feature, $enabled]);
    }

    echo json_encode(['success' => true, 'message' => 'Feature updated successfully.']);
    exit();
}


            case 'block': {
                if (!$id) { echo json_encode(['success'=>false,'message'=>'Missing id.']); exit(); }

                $stmt = $pdo->prepare("UPDATE visitors SET blocked = 1 WHERE id = ?");
                $stmt->execute([$id]);

                $stmt = $pdo->prepare("SELECT ip_address, user_agent FROM visitors WHERE id = ?");
                $stmt->execute([$id]);
                $visitor = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($visitor) {
                    $pdo->prepare("DELETE FROM whitelisted_ips WHERE ip_address = ?")->execute([$visitor['ip_address']]);
                    $pdo->prepare("INSERT IGNORE INTO blocked_ips (ip_address) VALUES (?)")->execute([$visitor['ip_address']]);
                    $pdo->prepare("INSERT IGNORE INTO blocked_user_agents (pattern) VALUES (?)")->execute([$visitor['user_agent']]);
                }

                echo json_encode(['success' => true, 'message' => 'Visitor blocked successfully.']);
                exit();
            }

            case 'unblock': {
                if (!$id) { echo json_encode(['success'=>false,'message'=>'Missing id.']); exit(); }

                $stmt = $pdo->prepare("UPDATE visitors SET blocked = 0 WHERE id = ?");
                $stmt->execute([$id]);

                $stmt = $pdo->prepare("SELECT ip_address, user_agent FROM visitors WHERE id = ?");
                $stmt->execute([$id]);
                $visitor = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($visitor) {
                    $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?")->execute([$visitor['ip_address']]);
                    $pdo->prepare("INSERT IGNORE INTO whitelisted_ips (ip_address) VALUES (?)")->execute([$visitor['ip_address']]);
                    $pdo->prepare("DELETE FROM blocked_user_agents WHERE pattern = ?")->execute([$visitor['user_agent']]);
                }

                echo json_encode(['success' => true, 'message' => 'Visitor unblocked successfully.']);
                exit();
            }

case 'delete': {
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Missing id.']); exit(); }

    // Get IP + session(s) for precision cleanup
    $stmt = $pdo->prepare("SELECT ip_address FROM visitors WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $ip = $row['ip_address'];

        // 1) Behavior logs
        $pdo->prepare("DELETE FROM behavioral_patterns WHERE ip_address = ?")->execute([$ip]);

        // 2) Honeypot interactions
        $pdo->prepare("DELETE FROM honeypot_interactions WHERE ip_address = ?")->execute([$ip]);

        // 3) Rate limiting windows
        $pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ?")->execute([$ip]);

        // 4) IP reputation cache
        $pdo->prepare("DELETE FROM ip_reputation WHERE ip_address = ?")->execute([$ip]);

        // 5) Block/whitelist entries for this IP (optional; keep if you want history)
        $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?")->execute([$ip]);
        $pdo->prepare("DELETE FROM whitelisted_ips WHERE ip_address = ?")->execute([$ip]);

        // 6) If you store fingerprint_hash on visitors, you can prune orphaned prints:
        // $pdo->prepare("DELETE bf FROM browser_fingerprints bf
        //                LEFT JOIN visitors v ON v.browser_fingerprint = bf.fingerprint_hash
        //                WHERE v.id IS NULL")->execute();
    }

    // Finally remove the visitor
    $stmt = $pdo->prepare("DELETE FROM visitors WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Visitor and related logs deleted.']);
    exit();
}


            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}


// Pagination
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], [50, 100]) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total visitors
$totalStmt = $pdo->query("SELECT COUNT(*) FROM visitors");
$totalVisitors = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalVisitors / $limit));

// Fetch paginated visitors
$stmt = $pdo->prepare("SELECT * FROM visitors ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count bots in current page
$totalBots = array_reduce($visitors, fn($count, $v) => $count + ($v['is_bot'] ? 1 : 0), 0);

// Settings
$stmt = $pdo->query("SELECT name, value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Advanced statistics
$stats = [];

// Behavioral analysis stats
$stmt = $pdo->query("SELECT AVG(human_score) as avg_score, COUNT(*) as total_patterns FROM behavioral_patterns WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$behavioral_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['behavioral'] = $behavioral_stats;

// Honeypot stats
$stmt = $pdo->query("SELECT COUNT(*) as total_interactions, COUNT(DISTINCT ip_address) as unique_ips FROM honeypot_interactions WHERE interaction_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$honeypot_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['honeypot'] = $honeypot_stats;

// Rate limiting stats
$stmt = $pdo->query("SELECT COUNT(*) as blocked_requests, COUNT(DISTINCT ip_address) as blocked_ips FROM rate_limits WHERE blocked_until > NOW()");
$rate_limit_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['rate_limit'] = $rate_limit_stats;

// Browser fingerprint stats
$stmt = $pdo->query("SELECT COUNT(*) as total_fingerprints, COUNT(CASE WHEN is_suspicious = 1 THEN 1 END) as suspicious_fingerprints FROM browser_fingerprints");
$fingerprint_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['fingerprint'] = $fingerprint_stats;

// Challenge response stats
$total_challenges = 0;
$successful_challenges = 0;
$challenge_success_rate = 0.0;

// only run if the table exists (prevents 1146)
$exists = $pdo->prepare(
    "SELECT 1 FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'challenge_responses'"
);
$exists->execute();
if ($exists->fetchColumn()) {
    $stmt = $pdo->query(
        "SELECT COUNT(*) as total_challenges,
                COUNT(CASE WHEN is_correct = 1 THEN 1 END) as successful_challenges
         FROM challenge_responses
         WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_challenges'=>0,'successful_challenges'=>0];
    $total_challenges      = (int)$row['total_challenges'];
    $successful_challenges = (int)$row['successful_challenges'];
    if ($total_challenges > 0) {
        $challenge_success_rate = round(($successful_challenges / $total_challenges) * 100, 2);
    }
}


// IP reputation stats
$stmt = $pdo->query("SELECT COUNT(*) as total_checked, AVG(reputation_score) as avg_score FROM ip_reputation WHERE last_updated > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$reputation_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['reputation'] = $reputation_stats;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Anti-Bot Center - Professional Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Enhanced Professional Dark Theme */
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-orange: #f59e0b;
            --accent-purple: #8b5cf6;
            --accent-cyan: #06b6d4;
            --accent-pink: #ec4899;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: #475569;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-medium: rgba(0, 0, 0, 0.25);
            --shadow-heavy: rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, #1a202c 100%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px var(--shadow-medium);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            font-size: 1.5rem;
            color: var(--accent-blue);
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .header-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: grid;
            gap: 2rem;
        }

        /* Enhanced Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px var(--shadow-medium);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.visitors::before {
            background: linear-gradient(90deg, var(--accent-blue), var(--accent-purple));
        }

        .stat-card.bots::before {
            background: linear-gradient(90deg, var(--accent-red), var(--accent-orange));
        }

        .stat-card.behavioral::before {
            background: linear-gradient(90deg, var(--accent-green), var(--accent-cyan));
        }

        .stat-card.honeypot::before {
            background: linear-gradient(90deg, var(--accent-orange), var(--accent-red));
        }

        .stat-card.fingerprint::before {
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-pink));
        }

        .stat-card.challenges::before {
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-blue));
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stat-card.visitors .stat-icon { color: var(--accent-blue); }
        .stat-card.bots .stat-icon { color: var(--accent-red); }
        .stat-card.behavioral .stat-icon { color: var(--accent-green); }
        .stat-card.honeypot .stat-icon { color: var(--accent-orange); }
        .stat-card.fingerprint .stat-icon { color: var(--accent-purple); }
        .stat-card.challenges .stat-icon { color: var(--accent-cyan); }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-subvalue {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        /* Enhanced Control Panel */
        .control-panel {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px var(--shadow-medium);
            border: 1px solid var(--border-color);
        }

        .panel-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .panel-header i {
            font-size: 1.5rem;
            color: var(--accent-blue);
        }

        .panel-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .feature-group {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .feature-group h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feature-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .feature-toggle:last-child {
            border-bottom: none;
        }

        .feature-info {
            display: flex;
            flex-direction: column;
        }

        .feature-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .feature-description {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: var(--accent-green);
        }

        .toggle-switch::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .toggle-switch.active::before {
            transform: translateX(26px);
        }



        /* Keep feature switches aligned and notifications fully visible */
        .feature-toggle {
            gap: 1rem;
        }

        .feature-info {
            flex: 1;
            min-width: 0;
            padding-right: 1rem;
        }

        .toggle-switch {
            flex: 0 0 50px;
        }

        .notification {
            max-width: calc(100vw - 40px);
            box-sizing: border-box;
            transform: translateX(calc(100% + 40px));
            z-index: 99999;
        }

        /* Action buttons */
/* Container for the buttons (add this if not already present) */
.button-container {
    display: flex;
    gap: 10px; /* Small space between buttons */
}

/* Updated control-btn class */
.control-btn {
    padding: 8px 8px;
    border: none;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: white;
    text-align: center;
    display: inline-flex; /* Changed from flex to inline-flex for individual button */
    align-items: center;
    justify-content: center;
    gap:5px;
    position: relative;
    overflow: hidden;
    background: green;
}

.control-btn.action {
  margin-top: 5px;

}

        .control-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .control-btn:hover::before {
            left: 100%;
        }

        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-medium);
        }

        .control-btn.action {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
        }

        .control-btn.warning {
            background: linear-gradient(135deg, var(--accent-orange), #d97706);
        }

        .control-btn.danger {
            background: linear-gradient(135deg, var(--accent-red), #dc2626);
        }

        /* Country Selection (existing styles preserved) */
        .country-panel {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px var(--shadow-medium);
            border: 1px solid var(--border-color);
        }

        .country-controls {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .country-select {
            width: 100%;
            min-height: 120px;
            padding: 1rem;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .country-select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .country-select option {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 0.5rem;
        }

        .country-btn {
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .country-btn.save {
            background: linear-gradient(135deg, var(--accent-green), #059669);
        }

        .country-btn.clear {
            background: linear-gradient(135deg, var(--accent-red), #dc2626);
        }

        .country-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-medium);
        }

        .country-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Selected Countries Display */
        .selected-countries {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--bg-primary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .selected-countries h4 {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .country-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .country-tag {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .country-tag:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .country-tag::after {
            content: '×';
            font-size: 0.875rem;
            opacity: 0.7;
        }

        /* Visitor Table (existing styles preserved but enhanced) */
        .table-container {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px var(--shadow-medium);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table-title i {
            font-size: 1.5rem;
            color: var(--accent-blue);
        }

        .table-title h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .table-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .table-controls select {
            padding: 0.5rem 1rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .visitor-table {
            width: 100%;
            border-collapse: collapse;
        }

        .visitor-table th {
            background: var(--bg-primary);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }

        .visitor-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .visitor-table tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.bot {
            background: rgba(239, 68, 68, 0.2);
            color: var(--accent-red);
        }

        .status-badge.human {
            background: rgba(16, 185, 129, 0.2);
            color: var(--accent-green);
        }

        .status-badge.blocked {
            background: rgba(239, 68, 68, 0.2);
            color: var(--accent-red);
        }

        .action-btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .action-btn.block {
            background: var(--accent-red);
            color: white;
        }

        .action-btn.unblock {
            background: var(--accent-green);
            color: white;
        }

        .action-btn.delete {
            background: var(--accent-orange);
            color: white;
        }

        .action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Pagination */
        .pagination {
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
        }

        .pagination-info {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
            color: white;
        }

        .pagination-btn.active {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
            color: white;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 0 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .controls-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-controls {
                width: 100%;
                justify-content: space-between;
            }
        }

        /* Loading and notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: var(--accent-green);
        }

        .notification.error {
            background: var(--accent-red);
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <h1>Enhanced Anti-Bot Center</h1>
            </div>
            <div class="header-actions">
			               <a href="../../void/index.php" class="header-btn">
                    <i class="fas fa-home"></i>
                   Panel
                </a> 
               <a href="change_credentials.php" class="header-btn">
                    <i class="fas fa-list"></i>
                    Change Login Info
                </a> 
                <a href="logout.php" class="header-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Enhanced Statistics -->
        <div class="stats-grid">
            <div class="stat-card visitors">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-label">Total Visitors</div>
                <div class="stat-value"><?= number_format($totalVisitors) ?></div>
                <div class="stat-subvalue">Page <?= $page ?> of <?= $totalPages ?></div>
            </div>

            <div class="stat-card bots">
                <i class="fas fa-robot stat-icon"></i>
                <div class="stat-label">Bots Detected</div>
                <div class="stat-value"><?= number_format($totalBots) ?></div>
                <div class="stat-subvalue">Current page</div>
            </div>

            <div class="stat-card behavioral">
                <i class="fas fa-brain stat-icon"></i>
                <div class="stat-label">Behavioral Analysis</div>
                <div class="stat-value"><?= number_format($stats['behavioral']['total_patterns'] ?? 0) ?></div>
                <div class="stat-subvalue">Avg Score: <?= number_format($stats['behavioral']['avg_score'] ?? 0, 1) ?>%</div>
            </div>

            <div class="stat-card honeypot">
                <i class="fas fa-bug stat-icon"></i>
                <div class="stat-label">Honeypot Triggers</div>
                <div class="stat-value"><?= number_format($stats['honeypot']['total_interactions'] ?? 0) ?></div>
                <div class="stat-subvalue"><?= number_format($stats['honeypot']['unique_ips'] ?? 0) ?> unique IPs</div>
            </div>

            <div class="stat-card fingerprint">
                <i class="fas fa-fingerprint stat-icon"></i>
                <div class="stat-label">Browser Fingerprints</div>
                <div class="stat-value"><?= number_format($stats['fingerprint']['total_fingerprints'] ?? 0) ?></div>
                <div class="stat-subvalue"><?= number_format($stats['fingerprint']['suspicious_fingerprints'] ?? 0) ?> suspicious</div>
            </div>

            <div class="stat-card challenges">
                <i class="fas fa-puzzle-piece stat-icon"></i>
                <div class="stat-label">Challenges Issued</div>
                <div class="stat-value"><?= number_format($stats['challenge']['total_challenges'] ?? 0) ?></div>
                <div class="stat-subvalue"><?= number_format($stats['challenge']['successful_challenges'] ?? 0) ?> solved</div>
            </div>
        </div>

        <!-- Enhanced Feature Controls -->
        <div class="control-panel">
            <div class="panel-header">
                <i class="fas fa-cogs"></i>
                <h2>Advanced Anti-Bot Features</h2>
            </div>
            <div class="controls-grid">
                <!-- Core Features -->
                <div class="feature-group">
                    <h3><i class="fas fa-shield-alt"></i> Core Protection</h3>
                    
                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">User Agent Blocking</div>
                            <div class="feature-description">Block known bot user agents</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['blocked_user_agents'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="blocked_user_agents"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">IP Range Blocking</div>
                            <div class="feature-description">Block static IP ranges and datacenters</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['static_range_blocking'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="static_range_blocking"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">ASN Blocking</div>
                            <div class="feature-description">Block VPN/hosting providers by ASN</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['asn_blocking_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="asn_blocking_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">CAPTCHA Protection</div>
                            <div class="feature-description">Show CAPTCHA to suspicious visitors</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['captcha_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="captcha_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Math CAPTCHA Protection</div>
                            <div class="feature-description">Use math question captcha</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['math_captcha_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="math_captcha_enabled"></div>
                    </div>
                </div>

                <!-- Advanced Behavioral Analysis -->
                <div class="feature-group">
                    <h3><i class="fas fa-brain"></i> Behavioral Analysis</h3>
                    
                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Mouse & Keyboard Tracking</div>
                            <div class="feature-description">Analyze human-like interaction patterns</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['behavioral_analysis_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="behavioral_analysis_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Session Analysis</div>
                            <div class="feature-description">Monitor session duration and activity</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['session_analysis_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="session_analysis_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Request Pattern Analysis</div>
                            <div class="feature-description">Detect automated request patterns</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['request_pattern_analysis_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="request_pattern_analysis_enabled"></div>
                    </div>
                </div>

                <!-- Fingerprinting -->
                <div class="feature-group">
                    <h3><i class="fas fa-fingerprint"></i> Device Fingerprinting</h3>
                    
                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Browser Fingerprinting</div>
                            <div class="feature-description">Canvas, WebGL, and audio fingerprinting</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['browser_fingerprinting_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="browser_fingerprinting_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">TLS Fingerprinting</div>
                            <div class="feature-description">Analyze TLS handshake patterns</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['tls_fingerprinting_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="tls_fingerprinting_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Device Fingerprinting</div>
                            <div class="feature-description">Hardware and software profiling</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['device_fingerprinting_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="device_fingerprinting_enabled"></div>
                    </div>
                </div>

                <!-- Active Defense -->
                <div class="feature-group">
                    <h3><i class="fas fa-shield-virus"></i> Active Defense</h3>
                    
                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Rate Limiting</div>
                            <div class="feature-description">Limit requests per IP address</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['rate_limiting_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="rate_limiting_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Honeypot Traps</div>
                            <div class="feature-description">Hidden fields to catch bots</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['honeypot_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="honeypot_enabled"></div>
                    </div>

                    

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">IP Reputation Check</div>
                            <div class="feature-description">Check IPs against threat databases</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['ip_reputation_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="ip_reputation_enabled"></div>
                    </div>
                </div>

                <!-- Professional Server Layer -->
                <div class="feature-group">
                    <h3><i class="fas fa-server"></i> Professional Server Layer</h3>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Adaptive Risk Engine</div>
                            <div class="feature-description">Score suspicious requests before using heavier checks</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['adaptive_risk_engine_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="adaptive_risk_engine_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Temporary Block TTL</div>
                            <div class="feature-description">Use automatic expiring blocks for medium/high risk traffic</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['temporary_block_ttl_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="temporary_block_ttl_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Route Rate Limits</div>
                            <div class="feature-description">Apply stricter limits per endpoint and request method</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['route_rate_limits_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="route_rate_limits_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Fast Protection Cache</div>
                            <div class="feature-description">Cache rDNS and GeoIP results to reduce server load during floods</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['advanced_cache_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="advanced_cache_enabled"></div>
                    </div>
                </div>

                <!-- Bot Category Control -->
                <div class="feature-group">
                    <h3><i class="fas fa-robot"></i> Bot Category Control</h3>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Allow Search Engines</div>
                            <div class="feature-description">Allow Google, Bing, DuckDuckGo, Yandex and other SEO crawlers</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['allow_search_engines'] ?? '1') === '1' ? 'active' : '' ?>" 
                             data-feature="allow_search_engines"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Allow Social Crawlers</div>
                            <div class="feature-description">Allow Facebook, X/Twitter, LinkedIn, WhatsApp, Telegram and Discord previews</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['allow_social_crawlers'] ?? '1') === '1' ? 'active' : '' ?>" 
                             data-feature="allow_social_crawlers"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Block AI Crawlers</div>
                            <div class="feature-description">Block GPTBot, Claude, CCBot, Perplexity and similar AI/data crawlers</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['block_ai_crawlers'] ?? '1') === '1' ? 'active' : '' ?>" 
                             data-feature="block_ai_crawlers"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Block Security Scanners</div>
                            <div class="feature-description">Block obvious scanner tools like Nmap, Nikto, SQLmap, Burp, nuclei and masscan</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['block_security_scanners'] ?? '1') === '1' ? 'active' : '' ?>" 
                             data-feature="block_security_scanners"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Block Scraping Tools</div>
                            <div class="feature-description">Block curl, wget, Scrapy, Selenium, Puppeteer, Playwright and common libraries</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['block_scraping_tools'] ?? '1') === '1' ? 'active' : '' ?>" 
                             data-feature="block_scraping_tools"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Custom UA Whitelist</div>
                            <div class="feature-description">Allow User-Agent patterns you manage from the custom pattern page</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['custom_ua_whitelist_enabled'] ?? '1') === '1' ? 'active' : '' ?>" 
                             data-feature="custom_ua_whitelist_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Custom UA Blocklist</div>
                            <div class="feature-description">Block User-Agent patterns you manage from the custom pattern page</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['custom_ua_blocklist_enabled'] ?? '1') === '1' ? 'active' : '' ?>" 
                             data-feature="custom_ua_blocklist_enabled"></div>
                    </div>
                </div>


                <!-- Local Reputation Lists -->
                <div class="feature-group">
                    <h3><i class="fas fa-shield-alt"></i> Local Reputation Lists</h3>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Block Spamhaus DROP</div>
                            <div class="feature-description">Block IP ranges from your locally saved Spamhaus DROP list</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['block_spamhaus_drop_enabled'] ?? '1') === '1' ? 'active' : '' ?>" 
                             data-feature="block_spamhaus_drop_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Block Tor Exit Nodes</div>
                            <div class="feature-description">Block IPs from your locally saved Tor exit node list</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['block_tor_exit_nodes_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="block_tor_exit_nodes_enabled"></div>
                    </div>
                </div>

                <!-- ASN & Country Policy -->
                <div class="feature-group">
                    <h3><i class="fas fa-globe"></i> ASN & Country Policy</h3>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">Country Risk Scoring</div>
                            <div class="feature-description">Apply custom country score/block/allow rules without changing your GeoLite2 lookup mechanism</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['country_risk_scoring_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="country_risk_scoring_enabled"></div>
                    </div>

                    <div class="feature-toggle">
                        <div class="feature-info">
                            <div class="feature-name">ASN Rule Manager</div>
                            <div class="feature-description">Apply custom ASN/provider score/block/allow rules from the ASN & Country Rules page</div>
                        </div>
                        <div class="toggle-switch <?= ($settings['asn_rule_manager_enabled'] ?? '0') === '1' ? 'active' : '' ?>" 
                             data-feature="asn_rule_manager_enabled"></div>
                    </div>
                </div>

            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
<button id="clearDataBtn" class="control-btn warning">
    <i class="fas fa-trash-alt"></i>
    Clear All Data
</button>
<a href="custom_patterns.php" class="control-btn action" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
    <i class="fas fa-list-check"></i>
    Custom UA Patterns
</a>
<a href="risk_events.php" class="control-btn action" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
    <i class="fas fa-chart-line"></i>
    Risk Events
</a>
<a href="reputation_lists.php" class="control-btn action" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
    <i class="fas fa-download"></i>
    Reputation Lists
</a>
<a href="asn_country_rules.php" class="control-btn action" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
    <i class="fas fa-network-wired"></i>
    ASN & Country Rules
</a>
<a href="threat_intel.php" class="control-btn action" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
    <i class="fas fa-chart-pie"></i>
    Threat Intelligence
</a>
<a href="geoip_status.php" class="control-btn action" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
    <i class="fas fa-database"></i>
    GeoIP Status
</a>

<script>
function showToast(message, success = true) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '6px';
    toast.style.color = '#fff';
    toast.style.fontSize = '14px';
    toast.style.zIndex = '9999';
    toast.style.background = success ? '#4CAF50' : '#F44336'; // green or red
    toast.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
    
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s ease';
        setTimeout(() => toast.remove(), 500);
    }, 2500);
}

document.getElementById('clearDataBtn').addEventListener('click', function(e) {
    e.preventDefault();

    fetch('clear_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast("✅ Data cleared successfully!", true);
        } else {
            showToast("❌ Failed: " + data.message, false);
        }
    })
    .catch(() => {
        showToast("❌ Network error while clearing data", false);
    });
});
</script>


                <a href="blocked.php" class="control-btn action">
                    <i class="fas fa-ban"></i>
                    Manage Blocked IPs
                </a>
                <a href="whitelist.php" class="control-btn action">
                    <i class="fas fa-check-circle"></i>
                    Manage Whitelist
                </a>
<button id="btn-edit-captcha" class="control-btn action">
  <i class="fas fa-edit"></i> Edit CAPTCHA Domain
</button>

<a href="../../reset_state.php" target="_blank" class="control-btn warning">
    <i class="fas fa-sync-alt"></i> Reset CAPTCHA Session
</a>		
				
				
            </div>
        </div>

        <!-- Country Selection (preserved from original) -->
        <div class="country-panel">
            <div class="panel-header">
                <i class="fas fa-globe"></i>
                <h2>Allowed Countries</h2>
            </div>
            <div class="country-controls">
                <select id="countrySelect" multiple class="country-select">
                    <option value="US">United States</option>
                    <option value="CA">Canada</option>
                    <option value="GB">United Kingdom</option>
                    <option value="DE">Germany</option>
                    <option value="FR">France</option>
                    <option value="IT">Italy</option>
                    <option value="ES">Spain</option>
                    <option value="NL">Netherlands</option>
                    <option value="SE">Sweden</option>
                    <option value="NO">Norway</option>
                    <option value="DK">Denmark</option>
                    <option value="FI">Finland</option>
                    <option value="AU">Australia</option>
                    <option value="NZ">New Zealand</option>
                    <option value="JP">Japan</option>
                    <option value="KR">South Korea</option>
                    <option value="SG">Singapore</option>
                    <option value="CH">Switzerland</option>
                    <option value="AT">Austria</option>
                    <option value="BE">Belgium</option>
                </select>
                <div class="country-buttons">
                    <button onclick="saveCountries()" class="country-btn save">
                        <i class="fas fa-save"></i>
                        Save Selection
                    </button>
                    <button onclick="clearCountries()" class="country-btn clear">
                        <i class="fas fa-times"></i>
                        Clear All
                    </button>
                </div>
                <div class="selected-countries">
                    <h4>Currently Selected Countries</h4>
                    <div id="selectedCountriesDisplay" class="country-tags"></div>
                </div>
            </div>
        </div>

        <!-- Enhanced Visitor Table -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-table"></i>
                    <h2>Recent Visitors</h2>
                </div>
                <div class="table-controls">
                    <button type="button" class="control-btn action" onclick="refreshVisitorTable(true)">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <span id="visitorLastUpdated" style="color:#9fb0c3; font-size:13px; white-space:nowrap;"></span>
                    <select id="visitorLimitSelect" onchange="changeLimit(this.value)">
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 per page</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 per page</option>
                    </select>
                </div>
            </div>
            <div class="table-wrapper">
			<br>
			<?php include __DIR__ . '/partials.search_bar.html'; ?>
			<br>
                <table class="visitor-table">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Country</th>
                            <th>User Agent</th>
                            <th>Status</th>
                            <th>Behavioral Score</th>
                            <th>Risk Score</th>
                            <th>Block Reason</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="visitorTableBody">
                        <?php foreach ($visitors as $visitor): ?>
                        <tr>
                            <td><?= htmlspecialchars($visitor['ip_address']) ?></td>
                            <td><?= htmlspecialchars($visitor['country'] ?? 'Unknown') ?></td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($visitor['user_agent']) ?>">
                                <?= htmlspecialchars(substr($visitor['user_agent'], 0, 50)) ?>...
                            </td>
                            <td>
                                <?php if ($visitor['is_bot']): ?>
                                    <span class="status-badge bot">Bot</span>
                                <?php else: ?>
                                    <span class="status-badge human">Human</span>
                                <?php endif; ?>
                                
                                <?php if ($visitor['blocked']): ?>
                                    <span class="status-badge blocked">Blocked</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $visitor['behavioral_score'] ?? 'N/A' ?></td>
                            <td><?= $visitor['risk_score'] ?? '0' ?></td>
                            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($visitor['block_reason'] ?? '') ?>">
                                <?= htmlspecialchars($visitor['block_reason'] ?? 'N/A') ?>
                            </td>
                            <td><?= date('M j, H:i', strtotime($visitor['created_at'])) ?></td>
                            <td>
							<div class="button-container">
                                <?php if ($visitor['blocked']): ?>
                                    <button onclick="unblockVisitor(<?= $visitor['id'] ?>)" class="action-btn unblock">Unblock</button>
                                <?php else: ?>
                                    <button onclick="blockVisitor(<?= $visitor['id'] ?>)" class="action-btn block">Block</button>
                                <?php endif; ?>
                                <button onclick="deleteVisitor(<?= $visitor['id'] ?>)" class="action-btn delete">Delete</button>
								</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <div class="pagination-info" id="visitorPaginationInfo">
                    Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalVisitors) ?> of <?= $totalVisitors ?> visitors
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" class="pagination-btn">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>&limit=<?= $limit ?>" class="pagination-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" class="pagination-btn">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Element -->
    <div id="notification" class="notification"></div>

<style>


/* Modal shell */
.kp-modal{position:fixed;inset:0;display:none;z-index:9999}
.kp-modal.show{display:block}
.kp-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.55)}
.kp-dialog{
  position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);
  width:min(560px,92vw);background:#1e2633;border-radius:14px;
  box-shadow:0 20px 60px rgba(0,0,0,.45);overflow:hidden
}
.kp-header{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;background:#0e1522}
.kp-header h3{margin:0;color:#fff;font-size:18px}
.kp-close{background:transparent;border:0;color:#9fb3c8;font-size:20px;cursor:pointer}
.kp-body{padding:18px}
.kp-label{display:block;margin-bottom:6px;color:#cbd5e1}
.kp-input{
  width:100%;padding:10px 12px;border-radius:10px;border:1px solid #314055;
  background:#0f1724;color:#e5eef7;outline:none
}
.kp-input:focus{border-color:#6aa3ff;box-shadow:0 0 0 2px rgba(106,163,255,.2)}
.kp-help{display:block;margin-top:8px;color:#8aa0b8}
.kp-footer{display:flex;gap:10px;justify-content:flex-end;padding:14px 18px;background:#0e1522}

</style>

<!-- CAPTCHA Domain Modal -->
<div id="captchaModal" class="kp-modal" aria-hidden="true">
  <div class="kp-backdrop"></div>
  <div class="kp-dialog" role="dialog" aria-modal="true" aria-labelledby="capTitle">
    <div class="kp-header">
      <h3 id="capTitle">Edit CAPTCHA Domain</h3>
      <button type="button" class="kp-close" id="capClose" aria-label="Close">×</button>
    </div>

    <div class="kp-body">
      <label class="kp-label">Domain</label>
      <input id="capDomainInput" type="text" class="kp-input" placeholder="secure.yourdomain.com">
      <small class="kp-help">Shown on the challenge page header.</small>
    </div>

    <div class="kp-footer">
      <button type="button" class="control-btn" id="capCancel">Cancel</button>
      <button type="button" class="control-btn action" id="capSave">
        <i class="fas fa-save"></i> Save
      </button>
    </div>
  </div>
</div>
<script>
// tiny toast popup
function toast(msg, ok=true){
  const t=document.createElement('div');
  t.textContent=msg;
  Object.assign(t.style,{
    position:'fixed',top:'20px',right:'20px',padding:'10px 14px',
    borderRadius:'8px',color:'#fff',background: ok?'#16a34a':'#dc2626',
    zIndex:10000,boxShadow:'0 8px 24px rgba(0,0,0,.25)'
  });
  document.body.appendChild(t);
  setTimeout(()=>{t.style.opacity='0';t.style.transition='opacity .35s';
    setTimeout(()=>t.remove(),350)},2000);
}

const modal   = document.getElementById('captchaModal');
const openBtn = document.getElementById('btn-edit-captcha');
const closeBtn= document.getElementById('capClose');
const cancel  = document.getElementById('capCancel');
const saveBtn = document.getElementById('capSave');
const input   = document.getElementById('capDomainInput');

function openModal(){ modal.classList.add('show'); }
function closeModal(){ modal.classList.remove('show'); }

// open + prefill
openBtn.addEventListener('click', async () => {
  try {
    const r = await fetch('get_setting.php?name=captcha_domain',{credentials:'include'});
    const j = await r.json();
    input.value = j.value || '';
  } catch {}
  openModal();
});

closeBtn.addEventListener('click', closeModal);
cancel.addEventListener('click', closeModal);
modal.querySelector('.kp-backdrop').addEventListener('click', closeModal);

// save
saveBtn.addEventListener('click', async () => {
  const v = input.value.trim();
  if (!v){ toast('Enter a domain', false); return; }
  try {
    const r = await fetch('save_setting.php', {
      method:'POST',
      credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ value: v })
    });
    const j = await r.json();
    if (j.success){ toast('Domain saved'); closeModal(); }
    else { toast(j.message || 'Save failed', false); }
  } catch{ toast('Network error', false); }
});
</script>

    <script>
        const CSRF_TOKEN = <?= json_encode($_SESSION['csrf'] ?? '') ?>;

        // Feature toggle functionality
        document.querySelectorAll('.toggle-switch').forEach(toggle => {
            toggle.addEventListener('click', function() {
                if (this.dataset.loading === '1') return;
                this.dataset.loading = '1';
                const feature = this.dataset.feature;
                const isActive = this.classList.contains('active');
                const newState = isActive ? '0' : '1';
                
                // Show loading state
                this.style.opacity = '0.6';
                this.style.pointerEvents = 'none';
                
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_feature&feature=${encodeURIComponent(feature)}&enabled=${encodeURIComponent(newState)}&csrf=${encodeURIComponent(CSRF_TOKEN)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.classList.toggle('active');
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error updating feature', 'error');
                })
                .finally(() => {
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                    this.dataset.loading = '0';
                });
            });
        });

        // Visitor management functions
        function blockVisitor(id) {
            if (!confirm('Are you sure you want to block this visitor?')) return;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=block&id=${encodeURIComponent(id)}&csrf=${encodeURIComponent(CSRF_TOKEN)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    refreshVisitorTable(true);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error blocking visitor', 'error');
            });
        }

        function unblockVisitor(id) {
            if (!confirm('Are you sure you want to unblock this visitor?')) return;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=unblock&id=${encodeURIComponent(id)}&csrf=${encodeURIComponent(CSRF_TOKEN)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    refreshVisitorTable(true);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error unblocking visitor', 'error');
            });
        }

        function deleteVisitor(id) {
            if (!confirm('Are you sure you want to delete this visitor record?')) return;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&id=${encodeURIComponent(id)}&csrf=${encodeURIComponent(CSRF_TOKEN)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    refreshVisitorTable(true);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error deleting visitor', 'error');
            });
        }

        // Country management functions (preserved from original)
        function saveCountries() {
            const select = document.getElementById('countrySelect');
            const selectedCountries = Array.from(select.selectedOptions).map(option => option.value);
            
            fetch('save_countries.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ countries: selectedCountries })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Countries saved successfully', 'success');
                    updateSelectedCountriesDisplay();
                } else {
                    showNotification('Error saving countries', 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving countries', 'error');
            });
        }

        function clearCountries() {
            if (!confirm('Are you sure you want to clear all allowed countries?')) return;
            
            fetch('save_countries.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ countries: [] })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('All countries cleared', 'success');
                    document.getElementById('countrySelect').selectedIndex = -1;
                    updateSelectedCountriesDisplay();
                } else {
                    showNotification('Error clearing countries', 'error');
                }
            })
            .catch(error => {
                showNotification('Error clearing countries', 'error');
            });
        }

function updateSelectedCountriesDisplay() {
    fetch('get_saved_countries.php')
    .then(response => response.json())
    .then(data => {
        const display = document.getElementById('selectedCountriesDisplay');
        display.innerHTML = '';
        
        if (data.countries && data.countries.length > 0) {
            data.countries.forEach(country => {
                const tag = document.createElement('span');
                tag.className = 'country-tag';
                tag.textContent = country;
                tag.onclick = () => removeCountry(country);
                display.appendChild(tag);
            });
        } else {
            display.innerHTML = '<span style="color: var(--text-muted);">No countries selected</span>';
        }

        // Sync the <select> options to match the saved countries
        const select = document.getElementById('countrySelect');
        Array.from(select.options).forEach(option => {
            option.selected = data.countries.includes(option.value);
        });
    })
    .catch(error => {
        console.error('Error loading countries:', error);
    });
}

        function removeCountry(countryCode) {
            // Implementation for removing individual country
            const select = document.getElementById('countrySelect');
            const option = select.querySelector(`option[value="${countryCode}"]`);
            if (option) {
                option.selected = false;
            }
            saveCountries();
        }

        // Utility functions
        function changeLimit(newLimit) {
            const url = new URL(window.location);
            url.searchParams.set('limit', newLimit);
            url.searchParams.set('page', '1');
            window.location = url;
        }

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function exportData() {
            showNotification('Exporting data...', 'success');
            // Implementation for data export
            window.open('export_data.php', '_blank');
        }

        let visitorRefreshBusy = false;
        let visitorActionBusy = false;
        let visitorRefreshTimer = null;

        function visitorSearchIsActive() {
            const form = document.getElementById('visitorSearchForm');
            if (!form) return false;
            const fields = form.querySelectorAll('input, select');
            for (const field of fields) {
                if (field.name === 'op') continue;
                if ((field.value || '').trim() !== '') return true;
            }
            return false;
        }

        async function refreshVisitorTable(manual = false) {
            if (visitorRefreshBusy || visitorActionBusy) return;
            if (!manual && document.hidden) return;
            if (!manual && visitorSearchIsActive()) return;

            const tbody = document.getElementById('visitorTableBody');
            if (!tbody) return;

            visitorRefreshBusy = true;
            try {
                const limitSelect = document.getElementById('visitorLimitSelect');
                const limit = limitSelect ? limitSelect.value : <?= json_encode((string)$limit) ?>;
                const page = <?= json_encode((int)$page) ?>;
                const response = await fetch(`fetch_visitor_rows.php?page=${encodeURIComponent(page)}&limit=${encodeURIComponent(limit)}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Refresh failed');

                tbody.innerHTML = data.rows_html || '';

                const info = document.getElementById('visitorPaginationInfo');
                if (info) {
                    info.textContent = `Showing ${data.from} to ${data.to} of ${data.total_visitors} visitors`;
                }

                const updated = document.getElementById('visitorLastUpdated');
                if (updated) {
                    updated.textContent = `Updated ${data.updated_at}`;
                }
            } catch (error) {
                if (manual) showNotification('Visitor refresh failed', 'error');
                console.error('Visitor refresh failed:', error);
            } finally {
                visitorRefreshBusy = false;
            }
        }

        function startVisitorAutoRefresh() {
            if (visitorRefreshTimer) clearInterval(visitorRefreshTimer);
            visitorRefreshTimer = setInterval(() => refreshVisitorTable(false), 7000);
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCountriesDisplay();
            startVisitorAutoRefresh();
            refreshVisitorTable(false);
            
            // Auto-refresh stats every 30 seconds
            setInterval(() => {
                // Refresh stats without full page reload
                fetch('get_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update stat values
                    // Implementation would update the displayed numbers
                })
                .catch(error => {
                    console.error('Error refreshing stats:', error);
                });
            }, 30000);
        });
    </script>
</body>
</html>

