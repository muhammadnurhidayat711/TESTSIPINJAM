<?php
session_start();
$_SESSION = array();  // Clear session variables
session_destroy();     // Destroy the session
header("location:index.php");  // Redirect to index.php
exit();  // It's a good practice to call exit after header redirection
?>
