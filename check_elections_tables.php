<?php
require_once 'includes/db_connect.php';

try {
    // Check if vot_elections table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'vot_elections'")->fetchAll();

    if (empty($tables)) {
        echo "ERROR: vot_elections table does NOT exist!\n\n";
        echo "Available tables:\n";
        $all_tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($all_tables as $table) {
            echo "- $table\n";
        }
    } else {
        echo "vot_elections table EXISTS\n\n";
        $stmt = $pdo->query('DESCRIBE vot_elections');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Table Structure: vot_elections\n";
        echo str_repeat("=", 80) . "\n";
        foreach ($columns as $col) {
            echo "Field: " . $col['Field'] . " | Type: " . $col['Type'] . " | Null: " . $col['Null'] . "\n";
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "Current records:\n";
        $records = $pdo->query("SELECT * FROM vot_elections")->fetchAll(PDO::FETCH_ASSOC);
        print_r($records);
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo 'Trace: ' . $e->getTraceAsString();
}
