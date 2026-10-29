<?php
// protection/db.php  — single PHP block (no stray output)
$dsn = 'mysql:host=localhost;dbname=luxtrust;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

/* =========================
   ADMIN GUARD (single-file)
   Lives at END of protection/db.php
   ========================= */

if (session_status() === PHP_SESSION_NONE) {
    // safe defaults; HTTPS loose so local/WAMP works
    ini_set('session.use_strict_mode','1');
    ini_set('session.cookie_httponly','1');
    ini_set('session.cookie_samesite','Strict');
    ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on') ? '1' : '0');
    session_start();
}

/* Detect if this script is under /protection/admin/ (filesystem check) */
$__script     = str_replace('\\','/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$__admin_dir  = str_replace('\\','/', __DIR__ . '/admin/'); // protection/admin/
$__is_admin   = (strpos($__script, $__admin_dir) === 0);

/* Always have a CSRF token (we won't enforce by default) */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* Require login for ALL admin pages except login/logout */
if ($__is_admin) {
    $__base = strtolower(basename($__script));
    $__skip = in_array($__base, ['login.php','logout.php'], true);

    if (!$__skip && empty($_SESSION['admin_logged_in'])) {
        // If AJAX/JSON, return JSON; else redirect to admin/login.php (same folder)
        $wantsJson = (
            (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        );

        if ($wantsJson) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success'=>false,'message'=>'Unauthorized']);
            exit;
        }

        // IMPORTANT: relative redirect so it works on WAMP/cPanel subpaths
        header('Location: login.php');
        exit;
    }

    // Soft CSRF: log only (does NOT block to avoid breaking your UI)
    $__ENFORCE_CSRF = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $__tok = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $__ok  = (!empty($__tok) && hash_equals($_SESSION['csrf'] ?? '', $__tok));
        if (!$__ok) {
            @file_put_contents(__DIR__.'/admin/guard_csrf.log',
                sprintf("[%s] Missing/Bad CSRF on %s from %s UA:%s\n",
                    date('Y-m-d H:i:s'),
                    $_SERVER['REQUEST_URI'] ?? '',
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ),
                FILE_APPEND
            );
            if ($__ENFORCE_CSRF) {
                http_response_code(419);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success'=>false,'message'=>'CSRF failed']);
                exit;
            }
        }
    }

    // Expose token as response header (harmless; handy if you use it later)
    header('X-CSRF-Token: ' . $_SESSION['csrf']);
}
