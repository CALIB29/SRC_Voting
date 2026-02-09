<?php
// =========================================================================
// MASTER CONFIGURATION FILE - FINAL VERSION (Using only src_db)
// =========================================================================

// --- 1. Basic Site Configuration ---
define('BASE_URL', 'http://YOUR_WEBSITE_URL_HERE/'); // Update before deployment

// --- 2. Database Connection Settings (Unified)
require_once __DIR__ . '/../../includes/db_connect.php';

// ... (existing mappings kept for functionality)
$databases = [
    "srcvotin_ccs" => "/CCS VOTING/dashboard.php",
    "srcvotin_cbs" => "/CBS VOTING/dashboard.php",
    "srcvotin_coe" => "/COE VOTING/dashboard.php",
    "srcvotin_elem" => "/ELEMENTARY/dashboard.php",
    "srcvotin_inte" => "/INTEGRATED/dashboard.php"
];

// --- 4. Department Mapping (Department → Redirect Folder)
$department_db_map = [
    'College of Computer Studies' => 'srcvotin_ccs',
    'College of Business Studies' => 'srcvotin_cbs',
    'College of Education' => 'srcvotin_coe',
    'Elementary Department' => 'srcvotin_elem',
    'Integrated High School' => 'srcvotin_inte'
];
?>