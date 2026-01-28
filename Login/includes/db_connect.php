<?php
require_once 'config.php';

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'src_db';

// Primary PDO connection to unified database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection to database '$dbname' failed: " . $e->getMessage());
}

// Backward-compatibility: provide $connections array with a single entry
$connections = ['src_db' => $pdo];
?>
