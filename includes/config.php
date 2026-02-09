<?php
// Global Configuration
if (!defined('BASE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    // Automatically detect project folder
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    // If we are in /admin/ or /CCSVOTING/admin/, we need to go up to the project root
    // Instead of complex logic, we can look for "src_votingsystem" in the path
    $request_uri = $_SERVER['REQUEST_URI'];
    if (strpos($request_uri, '/src_votingsystem/') !== false) {
        $project_folder = '/src_votingsystem/';
    } else {
        $project_folder = '/';
    }
    define('BASE_URL', $protocol . $host . $project_folder);
}
define('MAX_LOGIN_ATTEMPTS', 5);
define('UPLOAD_DIR', 'uploads/');
?>