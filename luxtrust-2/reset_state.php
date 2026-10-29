<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset(
    $_SESSION["captcha_passed"],
    $_SESSION["captcha_passed_at"],
    $_SESSION["remote_gate_ok"],
    $_SESSION["return_after_captcha"],
    $_SESSION["math_captcha_answer"],
    $_SESSION["math_captcha_time"]
);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

header("Location: index.php");
exit;