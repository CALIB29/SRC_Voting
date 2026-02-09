<?php
// fix_paths.php
$files = [
    'CCSVOTING/admin/*.php',
    'CBSVOTING/admin/*.php',
    'COEVOTING/admin/*.php',
    'INTEGRATED/admin/*.php',
    'ELEMENTARY/admin/*.php'
];

foreach ($files as $pattern) {
    foreach (glob("c:/xampp/htdocs/src_votingsystem/$pattern") as $file) {
        $content = file_get_contents($file);
        $content = str_replace('href="../assets/css/mobile_base.css"', 'href="../../assets/css/mobile_base.css"', $content);
        file_put_contents($file, $content);
    }
}
echo "Paths fixed.\n";
