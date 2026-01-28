<?php
// Tiyakin na ang file na ito ay tinatawag mong `config.php` o `db_connections.php`
// at ito ang ginagamit mo sa `require_once`

$host = 'localhost';
$username = 'root';
$password = '';
// Unified database name
$dbname = 'src_db';

// ========================================================================= //
// ITO ANG KRITIKAL NA DAGDAG
// ========================================================================= //
// Mapper: Ito ang tulay sa pagitan ng Department Name sa CSV at ng Database Name (ngayon lahat ay sa src_db na).
// Tiyaking EKSATONG-EKSATO ang mga pangalan ng department dito sa kung ano ang
// nakasulat sa iyong CSV file. Case-sensitive ito.
$department_db_map = [
    'College of Computer Studies' => 'src_db',
    'College of Business Studies' => 'src_db',
    'College of Education'        => 'src_db',
    'Elementary Department'       => 'src_db',
    'Integrated High School'      => 'src_db',
    'College of Engineering'      => 'src_db'
    // Tandaan: Case-sensitive, 'College of Computer Studies' ay iba sa 'college of computer studies'
];


// ========================================================================= //
// ANG IYONG EXISTING CODE AY TAMA NA
// ========================================================================= //
// Single PDO connection to unified src_db
$connections = [];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Store under src_db key for mapping usage
    $connections['src_db'] = $pdo;
} catch (PDOException $e) {
    die("Connection to database '$dbname' failed: " . $e->getMessage());
}

// Ang $connections['src_db'] at $department_db_map array ay parehong handa na
// para gamitin ng import_students.php.
?>