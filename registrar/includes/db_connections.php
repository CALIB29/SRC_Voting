<?php
// Unified Database Connections for Registrar
require_once __DIR__ . '/../../includes/db_connect.php';

// Mapper: Ito ang tulay sa pagitan ng Department Name sa CSV at ng Database Name.
// Lahat ay gumagamit na ngayon ng unified production database: 'srceduph_src_db'
$dbname = 'srceduph_src_db';

$department_db_map = [
    'College of Computer Studies' => $dbname,
    'College of Business Studies' => $dbname,
    'College of Education' => $dbname,
    'Elementary Department' => $dbname,
    'Integrated High School' => $dbname,
    'College of Engineering' => $dbname
];

// $pdo is already created in the required db_connect.php
$connections = [];
$connections[$dbname] = $pdo;

// Ang $connections[$dbname] at $department_db_map array ay parehong handa na
// para gamitin ng import_students.php.
?>