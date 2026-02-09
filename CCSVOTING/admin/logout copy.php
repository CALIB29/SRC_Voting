<?php
require_once __DIR__ . '../../../../../../../../../../includes/config.php';
// Regular user logout
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['path' => '/']); session_start(); }
session_unset();
session_destroy();
header("Location: " . BASE_URL . "admin/login.php");
exit;