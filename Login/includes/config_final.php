<?php
// =========================================================================
// MASTER CONFIGURATION FILE - FINAL VERSION (Using only src_db)
// =========================================================================

// --- 1. Basic Site Configuration ---
define('BASE_URL', 'http://YOUR_WEBSITE_URL_HERE/'); // Update before deployment

// --- 2. Database Connection Settings (ONLY src_db)
$host = 'localhost';
$dbname = 'src_db';        // Unified database
$username = 'root';        // Your DB user
$password = '';            // Your DB password (empty if using XAMPP)

// --- 3. Department Redirect Paths (NOT database names)
$databases = [
    "srcvotin_ccs"  => "/CCS VOTING/dashboard.php",
    "srcvotin_cbs"  => "/CBS VOTING/dashboard.php",
    "srcvotin_coe"  => "/COE VOTING/dashboard.php",
    "srcvotin_elem" => "/ELEMENTARY/dashboard.php",
    "srcvotin_inte" => "/INTEGRATED/dashboard.php"
];

// --- 4. Department Mapping (Department → Redirect Folder)
$department_db_map = [
    'College of Computer Studies'   => 'srcvotin_ccs',
    'College of Business Studies'   => 'srcvotin_cbs',
    'College of Education'          => 'srcvotin_coe',
    'Elementary Department'         => 'srcvotin_elem',
    'Integrated High School'        => 'srcvotin_inte'
];

// --- 5. PDO Database Connection ---
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Connection to database '$dbname' failed: " . $e->getMessage());
}
?>
