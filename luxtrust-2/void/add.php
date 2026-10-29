<?php



include 'inc/config.php';
include 'inc/connect.php';
date_default_timezone_set('Europe/Dublin');
session_start();
if (!isset($_SESSION['loggedin']) OR ($_SESSION['role'] != "Admin")) {
	die();
}
$huser = $_POST['huser'];
$hpass = $_POST['hpass'];
$query = mysqli_query($conn, "SELECT * from handlers WHERE username='$huser'");
$not_available = mysqli_num_rows($query);
if($not_available == "0"){
    mysqli_query($conn, "INSERT INTO handlers (username, password) VALUES ('$huser', '$hpass')");
    $_SESSION['add_handlers'] = 'success';
}else{
    $_SESSION['add_handlers'] = 'error';
}
header('Location: crew.php');
exit;
    ?>