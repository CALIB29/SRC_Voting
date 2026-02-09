<?php
require 'includes/db_connect.php';

echo "<h1>Database Tables Investigation</h1>";

// List all tables
echo "<h2>All Tables in Database:</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check students table structure
echo "<h2>Students Table Structure:</h2>";
try {
    $stmt = $pdo->query("SHOW FULL COLUMNS FROM students");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Highlight department_id
    $fieldNames = array_column($columns, 'Field');
    if (in_array('department_id', $fieldNames)) {
        echo "<p style='color: green; font-weight: bold;'>✓ department_id column EXISTS</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ department_id column MISSING</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Test the exact query from update_account.php
echo "<h2>Test Query from update_account.php:</h2>";
try {
    $stmt = $pdo->prepare("SELECT student_id, rfid_number, first_name, last_name, profile_picture, course_id, department_id FROM students WHERE rfid_number = ?");
    $stmt->execute(['test']);
    echo "<p style='color: green;'>✓ Query executed successfully (no results expected)</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Query failed: " . $e->getMessage() . "</p>";
}
?>