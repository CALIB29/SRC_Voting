<?php
include 'includes/db_connect.php';
$s = $pdo->query("SELECT photo_path FROM candidates LIMIT 5");
while ($r = $s->fetch()) {
    echo $r['photo_path'] . "\n";
}
?>