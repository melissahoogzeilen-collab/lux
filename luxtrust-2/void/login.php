<?php
include 'inc/config.php';
include 'inc/connect.php';
session_start();

if (isset($_POST['loginUsername'], $_POST['loginPassword'])) {
    $user = $_POST['loginUsername'];
    $pass = $_POST['loginPassword'];

    // Check for invalid characters
    if (preg_match('/[\'"^*\/]/', $user) || preg_match('/[\'"^*\/]/', $pass)) {
        $_SESSION['error'] = "Invalid characters in username or password.";
        header('Location: login.php'); // Redirect to login page to display the error
        exit;
    }

    // Check credentials
    if ($user === $admin_panel_username && $pass === $admin_panel_password) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user;
        $_SESSION['role'] = 'Admin';
        header('Location: index.php');
        exit;
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT username FROM handlers WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $user, $pass);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user;
            $_SESSION['role'] = 'Handler';
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['error'] = "Invalid login credentials. Please try again.";
            header('Location: login.php'); // Redirect to login page to display the error
            exit;
        }
    }
}

// Redirect with an error if the necessary POST variables are not set
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['error'] = "Username and password are required.";
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title>Login - ADMIN</title>
   <link rel="icon" href="img/favicon.ico" type="image/png">
   <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
   <link rel="stylesheet" href="assets/css/style.css">
   <link rel="stylesheet" href="assets/css/loginform.css">
</head>
<body >
   <div id="particles-js" >
         <div class="animated bounceInDown">
            <div class="container">
                  <form method="POST" name="form" class="box">
    <h4><span>Zynex Panel </span></h4>
    <div>
        <img class="currently-loading" style="width: 100px;height: 100px;" src="logo.png">
    </div>
    <div class="form-group">
        <input id="loginUsername" type="text" name="loginUsername" placeholder="Username" required data-msg="Please enter your username" class="input-material" autocomplete="off">
    </div>
    <div class="form-group">
        <input id="loginPassword" type="password" name="loginPassword" placeholder="Password" required data-msg="Please enter your password" class="input-material" autocomplete="off">
    </div>
	
	                  <?php echo $err; ?>

    <input type="submit" value="Login" class="btn1 btn btn-primary">
</form>

            </div>
         </div>
   </div>
   <script src="assets/js/particle.js"></script>
   <script src="assets/js/script.js"></script>
       <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/popper.js/umd/popper.min.js"> </script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/jquery.cookie/jquery.cookie.js"> </script>
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="vendor/jquery-validation/jquery.validate.min.js"></script>
    <script src="js/front.js"></script>
</body>
</html>