<?php
require_once __DIR__ . '/../includes/db_connect.php';

$tables = [
    'departments' => 'vot_departments',
    'employees' => 'vot_employees'
];

// 1. DB Rename (idempotent)
foreach ($tables as $old => $new) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE '$old'");
        if ($check->rowCount() > 0) {
            $pdo->exec("RENAME TABLE `$old` TO `$new`");
            echo "Renamed table in DB: $old -> $new\n";
        }
    } catch (PDOException $e) {
    }
}

// 2. File Update
$rootPath = realpath(__DIR__ . '/..');
echo "Scanning: $rootPath\n";

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath));
foreach ($iterator as $file) {
    if ($file->isDir())
        continue;
    $filePath = $file->getRealPath();
    if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php')
        continue;
    if (strpos($filePath, 'migrations') !== false)
        continue;

    $content = file_get_contents($filePath);
    $original = $content;

    foreach ($tables as $old => $new) {
        // More direct replacement for this specific task
        // We want to replaces table names but avoid replacing variables like $department_id (substring match)
        // Table names in SQL are usually standalone words or followed by a space/newline/backtick

        // Pattern for SQL table usage
        // 1. Backticked
        $content = str_replace("`$old`", "`$new`", $content);

        // 2. Standalone word preceded by keyword and space
        $keywords = ['FROM', 'JOIN', 'UPDATE', 'INTO', 'TABLE', 'DESCRIBE', 'DELETE FROM'];
        foreach ($keywords as $kw) {
            $content = preg_replace("/\b($kw)\s+$old\b/i", "$1 $new", $content);
        }

        // 3. Table aliases (e.g., departments d) - tricky, but common
        // Pattern: [FROM|JOIN] table alias
        $content = preg_replace("/(FROM|JOIN)\s+$old\s+([a-zA-Z0-9_]+)/i", "$1 $new $2", $content);

        // 4. Aliasing with dots: departments.col
        $content = preg_replace("/\b$old\./i", "$new.", $content);
    }

    if ($content !== $original) {
        file_put_contents($filePath, $content);
        echo "Updating: " . str_replace($rootPath, '', $filePath) . "\n";
    }
}
echo "Done.\n";
?>