<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db_connect.php';

session_unset();
session_destroy();
header("Location: " . BASE_URL . "admin/login.php");
exit;
