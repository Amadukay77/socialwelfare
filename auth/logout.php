 <?php

session_start();

/* Clear all session variables */
$_SESSION = [];

/* Destroy the session */
session_destroy();

/* Send user back to login page */
header("Location: login.php");
exit;

?>