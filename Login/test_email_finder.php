<?php
// =========================================================================
// ULTIMATE DETECTIVE SCRIPT - test_email_finder.php
// Ang tanging layunin nito ay hanapin ang isang specific na email at i-report ang lahat.
// =========================================================================

// Ipakita ang lahat ng errors para walang makaligtas
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Ultimate Email Finder - Diagnostic Report</h1>";
echo "<pre>"; // Gumamit ng <pre> tag para mas madaling basahin ang output

// --- Hakbang 1: I-load ang Configuration File ---
$config_path = 'includes/config_final.php'; // Gagamitin natin ang ni-rename mong file
echo "<b>Step 1: Tinatangkang i-load ang config file...</b><br>";
echo "Path: " . $config_path . "<br>";

if (!file_exists($config_path)) {
    die("<b>RESULTA: FATAL ERROR!</b> Hindi mahanap ang config file sa landas na '{$config_path}'. Itigil ang test.");
}

require_once $config_path;
echo "<span style='color:green;'>SUCCESS:</span> Matagumpay na na-load ang config file.<hr>";


// --- Hakbang 2: Suriin ang Laman ng $databases array ---
echo "<b>Step 2: Sinusuri ang laman ng \$databases array...</b><br>";

if (!isset($databases) || !is_array($databases) || empty($databases)) {
    die("<b>RESULTA: FATAL ERROR!</b> Ang config file ay na-load, ngunit ang variable na \$databases ay wala, hindi array, o walang laman.");
}

echo "<span style='color:green;'>SUCCESS:</span> Nahanap ang \$databases array. Ito ang nilalaman:<br>";
print_r($databases);
echo "<hr>";


// --- Hakbang 3: Simulan ang Paghahanap sa Bawat Database ---
$email_to_find = 'rimarchdizon45@gmail.com';
echo "<b>Step 3: Hahanapin ang email na '{$email_to_find}' sa bawat database...</b><br><br>";

$final_conclusion = "<span style='color:red; font-weight:bold;'>HINDI NAKITA</span> ang email sa kahit anong database.";
$found_in_db = null;

foreach ($databases as $dbName => $path) {
    echo "-------------------------------------<br>";
    echo "Sinusuri ang database: <b>{$dbName}</b>...<br>";

    if (!isset($connections[$dbName])) {
        echo "<span style='color:orange;'>WARNING:</span> Walang active connection para sa database na ito sa \$connections array. Nilalaktawan...<br>";
        continue;
    }

    $pdo = $connections[$dbName];
    try {
        $stmt = $pdo->prepare("SELECT student_id, email AS gmail FROM students WHERE email = ?");
        $stmt->execute([$email_to_find]);
        $userData = $stmt->fetch();

        if ($userData) {
            echo "<span style='color:green; font-weight:bold;'>SUCCESS! NAKITA!</span><br>";
            echo "Natagpuan sa database na ito. User data:<br>";
            print_r($userData);
            $final_conclusion = "<span style='color:green; font-weight:bold;'>NAKITA</span> ang email sa database na: <b>{$dbName}</b>";
            $found_in_db = $dbName;
            break; // Itigil na ang paghahanap
        } else {
            echo "<span style='color:grey;'>INFO:</span> Hindi nakita sa database na ito.<br>";
        }

    } catch (PDOException $e) {
        echo "<span style='color:red;'>ERROR:</span> Nagkaroon ng problema sa pag-query sa database na ito: " . $e->getMessage() . "<br>";
    }
}

echo "-------------------------------------<hr>";

// --- Hakbang 4: Ang Final na Resulta ---
echo "<h2>Final Conclusion:</h2>";
echo "<h2>" . $final_conclusion . "</h2>";

echo "</pre>";
?>