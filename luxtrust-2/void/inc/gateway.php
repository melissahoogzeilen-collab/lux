<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'void/inc/config.php';

function show404() {
    http_response_code(404);
    echo '<!DOCTYPE html>
<html>
<head>
<title>404 Not Found</title>
<style>
body{
    font-family: Arial;
    background:#fff;
    text-align:center;
    padding-top:80px;
}
h1{
    font-size:48px;
    margin-bottom:10px;
}
p{
    color:#666;
    font-size:18px;
}
</style>
</head>
<body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body>
</html>';
    exit;
}

if ($enable_gateway != "checked") {
    show404();
}

$current = basename($_SERVER['PHP_SELF']);

if ($current === 'index.php') {
    return;
}

if (
    empty($_SESSION['started']) ||
    $_SESSION['started'] !== 'true' ||
    empty($_SESSION['uniqueid'])
) {
    show404();
}
?>