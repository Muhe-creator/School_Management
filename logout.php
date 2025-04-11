<?php
// Start or resume session
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect user back to login page
header("Location: index.php");
exit;
?>
