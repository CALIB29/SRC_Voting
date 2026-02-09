<?php
// apply_mobile_app_layout.php

function processFile($file)
{
    echo "Processing: $file\n";
    $content = file_get_contents($file);
    $basename = basename($file);
    $is_admin = (strpos($file, 'admin') !== false);
    $depth = substr_count($file, '\\') - substr_count('c:\xampp\htdocs\src_votingsystem', '\\');
    $rel_path = str_repeat('../', $depth);
    if ($depth == 0)
        $rel_path = './';

    // 1. Add CSS links (Mobile Base)
    $css_link = "\n    <link rel=\"stylesheet\" href=\"{$rel_path}assets/css/mobile_base.css\">";
    if (strpos($content, 'mobile_base.css') === false) {
        $content = preg_replace('/<\/head>/i', $css_link . '</head>', $content);
    }

    // 2. Add Mobile Top Bar
    if (strpos($content, 'renderMobileTopBar') === false) {
        $title = "Voting System";
        if (strpos($basename, 'dashboard') !== false)
            $title = "Dashboard";
        if (strpos($basename, 'manage_users') !== false)
            $title = "Users";
        if (strpos($basename, 'manage_candidates') !== false)
            $title = "Candidates";
        if (strpos($basename, 'results') !== false || strpos($basename, 'resultview') !== false)
            $title = "Results";
        if (strpos($basename, 'vote') !== false)
            $title = "Vote";

        $top_bar = "\n<?php if (function_exists('renderMobileTopBar')) renderMobileTopBar('$title'); ?>";
        $content = preg_replace('/<body[^>]*>/i', '$0' . $top_bar, $content);
    }

    // 3. Add Mobile Bottom Nav
    if (strpos($content, 'renderMobileBottomNav') === false) {
        $context = $is_admin ? 'admin' : 'student';
        $bottom_nav = "\n<?php if (function_exists('renderMobileBottomNav')) renderMobileBottomNav('$context'); ?>\n</body>";
        $content = preg_replace('/<\/body>/i', $bottom_nav, $content);
    }

    // 4. Force Single Column Layout adjustments in inline style if needed
    // (Most should be handled by mobile_base.css)

    file_put_contents($file, $content);
}

$directories = [
    'CCSVOTING',
    'CBSVOTING',
    'COEVOTING',
    'INTEGRATED',
    'ELEMENTARY'
];

foreach ($directories as $dir) {
    $path = "c:\\xampp\\htdocs\\src_votingsystem\\$dir";

    // Student files
    $files = glob("$path/*.php");
    foreach ($files as $file) {
        if (in_array(basename($file), ['dashboard.php', 'vote.php', 'view.php', 'resultview.php'])) {
            processFile($file);
        }
    }

    // Admin files
    $admin_files = glob("$path/admin/*.php");
    foreach ($admin_files as $file) {
        if (in_array(basename($file), ['dashboard.php', 'manage_users.php', 'manage_candidates.php', 'results.php', 'archive.php'])) {
            processFile($file);
        }
    }
}

echo "Migration Complete.\n";
