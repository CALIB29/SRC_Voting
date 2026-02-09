<?php
/**
 * Main Database Connection
 * Compatible with Localhost and Tigernet Hosting
 */

// Detect environment: Check if CLI or if HTTP_HOST is localhost
$is_cli = (php_sapi_name() === 'cli');
$is_localhost = isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($is_cli || $is_localhost) {
    // Localhost Settings
    $host = 'localhost';
    $db = 'src_db';
    $user = 'root';
    $pass = '';
} else {
    // Tigernet Hosting Settings (src.edu.ph)
    $host = 'localhost';
    $db = 'srceduph_src_db';
    $user = 'srceduph_src_db';
    $pass = 'na&S_y2#QI&#';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Session Management for Tigernet Hosting
 * Uses a custom session folder to ensure persistence
 */
if (session_status() === PHP_SESSION_NONE) {
    // Create a private session folder if it doesn't exist
    $session_save_path = __DIR__ . '/../sessions';
    if (!is_dir($session_save_path)) {
        @mkdir($session_save_path, 0700, true);
    }

    // Only set custom path if directory was created successfully
    if (is_dir($session_save_path) && is_writable($session_save_path)) {
        session_save_path($session_save_path);
    }

    // Robust HTTPS Detection
    $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    // Set cookie parameters
    session_set_cookie_params([
        'path' => '/',
        'secure' => $is_secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Start session (simplified - no close/reopen)
    session_start();
}
?>