<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

$sql = "
INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'College of Computer Studies', 'CCS', 1, '2025-11-30 22:29:42', '2026-02-02 00:15:05'),
(3, 'College of Education', 'COE', 1, '2025-11-30 22:30:22', '2026-02-02 00:16:13'),
(4, 'College of Business Studies', 'CBS', 1, '2025-11-30 22:31:36', '2026-02-02 00:15:37'),
(5, 'Senior High School', 'SENIOR HIGH SCHOOL', 1, '2025-11-30 22:38:33', '2026-02-03 16:40:16'),
(6, 'Elementary School', 'Elementary School', 1, '2026-02-02 00:19:02', '2026-02-02 00:19:02'),
(9, 'ELEMENTARY', NULL, 1, '2026-02-02 19:10:14', '2026-02-02 19:10:14')
ON DUPLICATE KEY UPDATE
`department_name` = VALUES(`department_name`),
`department_code` = VALUES(`department_code`),
`is_active` = VALUES(`is_active`),
`updated_at` = VALUES(`updated_at`);
";

try {
    $pdo->exec($sql);
    echo "Departments data inserted/updated successfully.";
} catch (PDOException $e) {
    echo "SQL execution failed: " . $e->getMessage();
}
