<?php
// Standardized connection for Login folder
require_once __DIR__ . '/../../includes/db_connect.php';

// Backward-compatibility: provide $connections array with a single entry
if (isset($pdo)) {
    $connections = ['src_db' => $pdo];
}
?>