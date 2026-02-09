<?php
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['path' => '/']); session_start(); }

// Burahin ang lahat ng session variables
$_SESSION = array();

// I-destroy ang session
session_destroy();

// Ngayon, i-redirect sa login page na gusto mo
header("Location: /Login/admin/index.php");
exit;
?>