<?php
// admin/csrf.php  — single source for session, CSRF and admin guard
declare(strict_types=1);

// --- session (minimal, safe defaults) ---
if (PHP_SESSION_NONE === session_status()) {
    ini_set('session.use_strict_mode','1');
    ini_set('session.cookie_httponly','1');
    // Secure only if HTTPS (keeps local dev working)
    ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? '1' : '0');
    ini_set('session.cookie_samesite','Strict');
    session_name('admin_sid');
    session_start();
}

// --- CSRF token helpers ---
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_meta_tag(): string {
    return '<meta name="csrf-token" content="'.htmlspecialchars(csrf_token(), ENT_QUOTES).'">';
}
function csrf_input(): string {
    return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES).'">';
}
function verify_csrf_or_fail(): void {
    $t = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419); exit('CSRF failed');
    }
}

// --- admin session helpers ---
function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}
function require_admin(): void {
    if (!is_admin_logged_in()) {
        // For JSON endpoints prefer 401, for pages redirect:
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            http_response_code(401); header('Content-Type: application/json');
            echo json_encode(['ok'=>false,'error'=>'auth_required']); exit;
        }
        header('Location: /admin/login.php'); exit;
    }
}

// --- tiny one-liners for endpoints ---
function require_admin_post(): void {
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Use POST'); }
    verify_csrf_or_fail();
}
