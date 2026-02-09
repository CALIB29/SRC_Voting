<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

function tableExists($pdo, $table)
{
    try {
        $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
    } catch (Exception $e) {
        return false;
    }
    return $result !== false;
}

// Check for 'vot_departments' and 'departments'
$vot_exists = tableExists($pdo, 'vot_departments');
$dept_exists = tableExists($pdo, 'departments');

echo "vot_departments exists: " . ($vot_exists ? "Yes" : "No") . "\n";
echo "departments exists: " . ($dept_exists ? "Yes" : "No") . "\n";

if ($vot_exists && !$dept_exists) {
    echo "Renaming vot_departments to departments...\n";
    try {
        $pdo->exec("RENAME TABLE vot_departments TO departments");
        echo "Renamed successfully.\n";
    } catch (Exception $e) {
        echo "Rename failed: " . $e->getMessage() . "\n";
    }
} elseif (!$dept_exists) {
    echo "Creating departments table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `departments` (
      `department_id` int(11) NOT NULL AUTO_INCREMENT,
      `department_name` varchar(100) NOT NULL,
      `department_code` varchar(50) DEFAULT NULL,
      `is_active` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`department_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try {
        $pdo->exec($sql);
        echo "Created successfully.\n";
    } catch (Exception $e) {
        echo "Create failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "departments table already exists.\n";
}

// Same for employees
$vot_emp_exists = tableExists($pdo, 'vot_employees');
$emp_exists = tableExists($pdo, 'employees');
echo "vot_employees exists: " . ($vot_emp_exists ? "Yes" : "No") . "\n";
echo "employees exists: " . ($emp_exists ? "Yes" : "No") . "\n";

if ($vot_emp_exists && !$emp_exists) {
    echo "Renaming vot_employees to employees...\n";
    try {
        $pdo->exec("RENAME TABLE vot_employees TO employees");
        echo "Renamed successfully.\n";
    } catch (Exception $e) {
        echo "Rename failed: " . $e->getMessage() . "\n";
    }
} elseif (!$emp_exists) {
    echo "Creating employees table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `employees` (
      `employee_id` int(11) NOT NULL AUTO_INCREMENT,
      `firstname` varchar(100) NOT NULL,
      `lastname` varchar(100) NOT NULL,
      `email` varchar(150) NOT NULL,
      `password` varchar(255) NOT NULL,
      `profile_pic` varchar(255) DEFAULT NULL,
      `department_id` int(11) DEFAULT NULL,
      `role_id` int(11) NOT NULL, 
      `last_login_at` datetime DEFAULT NULL,
      `is_archived` tinyint(1) NOT NULL DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`employee_id`),
      KEY `email` (`email`),
      KEY `department_id` (`department_id`),
      KEY `is_archived` (`is_archived`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try {
        $pdo->exec($sql);
        echo "Created successfully.\n";
    } catch (Exception $e) {
        echo "Create failed: " . $e->getMessage() . "\n";
    }
}

// Verify roles table existence as well
$roles_exists = tableExists($pdo, 'roles');
if (!$roles_exists) {
    echo "Creating roles table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `roles` (
      `role_id` int(11) NOT NULL AUTO_INCREMENT,
      `role_name` varchar(50) NOT NULL,
      PRIMARY KEY (`role_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try {
        $pdo->exec($sql);
        echo "Created successfully.\n";

        // Insert default roles if created
        $pdo->exec("INSERT INTO `roles` (`role_id`, `role_name`) VALUES
            (1, 'dean'),
            (2, 'faculty'),
            (3, 'Mis Admin'),
            (4, 'vot_Dean'),
            (5, 'vot_Teacher')
            ON DUPLICATE KEY UPDATE role_name=VALUES(role_name)");

    } catch (Exception $e) {
        echo "Create failed: " . $e->getMessage() . "\n";
    }
}

