<?php
session_start();

// Check if the checkbox state is sent via POST
if (isset($_POST['checkbox_state'])) {
    // Update the session variable with the submitted value
    $_SESSION['checkbox_state'] = $_POST['checkbox_state'] ? 1 : 0;
    
    // Update the file with the new checkbox state
    $file = fopen("offline.txt", "w");
    fwrite($file, $_SESSION['checkbox_state']);
    fclose($file);
}

// Redirect back to the admin panel
header("Location: settings.php");
exit();
?>
