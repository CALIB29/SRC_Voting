<?php
require_once '../includes/db_connect.php'; // Siguraduhing tama ang path

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Simulan ang transaction para siguradong lahat ng query ay sabay-sabay na magtatagumpay o mag-fail
$pdo->beginTransaction();

try {
    // 1. I-deactivate ang lahat ng kasalukuyang active na election
    // Gagawin nitong "archived" o luma ang kasalukuyang halalan
    $pdo->query("UPDATE vot_elections SET is_active = 0 WHERE is_active = 1");

    // 2. Gumawa ng bagong election record at i-set ito bilang active (is_active = 1)
    // Ang pangalan ay base sa kasalukuyang petsa para madaling tandaan
    $newElectionName = "Election " . date("Y-m-d H:i");
    $stmt = $pdo->prepare("INSERT INTO vot_elections (title, is_active) VALUES (?, 1)");
    $stmt->execute([$newElectionName]);

    // 3. I-reset ang 'has_voted' status ng LAHAT ng users para makaboto sila ulit sa bagong halalan
    $pdo->query("UPDATE students SET has_voted = 0");

    // Kung lahat ay naging matagumpay, i-save ang changes sa database
    $pdo->commit();

    echo "<script>
            alert('A new election has been started. Previous results are now archived.');
            window.location.href = 'dashboard.php';
          </script>";

} catch (Exception $e) {
    // Kung may naganap na error sa kahit anong step, i-undo lahat ng changes
    $pdo->rollBack();
    // Ipakita ang error. Sa totoong production, mas magandang i-log ito.
    die("Error starting new election: " . $e->getMessage());
}
?>