<?php
require_once __DIR__ . '/../includes/db_connect.php';

$tables = [
    'voting_schedule' => 'vot_voting_schedule', // Longest first
    'election_history_photos' => 'vot_election_history_photos',
    'election_history' => 'vot_election_history',
    'candidates' => 'vot_candidates',
    'elections' => 'vot_elections',
    'schedule' => 'vot_schedules',
    'votes' => 'vot_votes'
];

// 1. Rename Tables in DB
echo "Renaming tables in database...\n";
foreach ($tables as $old => $new) {
    try {
        // Check if old table exists
        $check = $pdo->query("SHOW TABLES LIKE '$old'");
        if ($check->rowCount() > 0) {
            $pdo->exec("RENAME TABLE `$old` TO `$new`");
            echo "Renamed: $old -> $new\n";
        } else {
            // Check if new table exists
            $checkNew = $pdo->query("SHOW TABLES LIKE '$new'");
            if ($checkNew->rowCount() > 0) {
                echo "Skipped: $old (Already renamed to $new)\n";
            } else {
                echo "Warning: Table $old not found and $new not found (Might imply it was never created or typo).\n";
            }
        }
    } catch (PDOException $e) {
        echo "Error renaming $old: " . $e->getMessage() . "\n";
    }
}

// 2. Search and Replace in Files
echo "Updating PHP files...\n";
$directory = new RecursiveDirectoryIterator(__DIR__ . '/../');
$iterator = new RecursiveIteratorIterator($directory);
// Process PHP and SQL files? mostly PHP.
$regexFile = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$keywords = 'FROM|JOIN|UPDATE|INTO|DELETE|TABLE|DESCRIBE';

foreach ($regexFile as $file) {
    $filePath = $file[0];
    if (basename($filePath) === 'rename_tables_2026.php')
        continue;
    if (strpos($filePath, 'db_structure.txt') !== false)
        continue; // Skip structure file (though it's .txt)

    $content = file_get_contents($filePath);
    $originalContent = $content;

    foreach ($tables as $old => $new) {
        // Pattern 1: Backticked `oldname`
        // Handles: `candidates` -> `vot_candidates`
        $content = preg_replace("/`$old`/", "`$new`", $content);

        // Pattern 2: SQL Keywords + space + oldname (word boundary)
        // Handles: FROM candidates -> FROM vot_candidates
        $content = preg_replace("/\b($keywords)\s+$old\b/i", "$1 $new", $content);

        // Pattern 3: mixed case check for SQL usually lower in code vars but upper in SQL
        // We utilize /i flag.

        // Pattern 4: Explicit table.column aliasing usage
        // e.g. candidates.id -> vot_candidates.id
        $content = preg_replace("/\b$old\./i", "$new.", $content);

        // Pattern 5: Comma separated lists in FROM clause
        // e.g. FROM users, candidates
        $content = preg_replace("/,\s*$old\b/i", ", $new", $content);
    }

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Updated: $filePath\n";
    }
}

echo "Migration Completed.\n";
?>
