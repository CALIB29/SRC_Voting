<?php
// Siguraduhing walang anumang space o karakter bago ang linyang ito.

header('Content-Type: application/json');

// Siguraduhing tama ang path papunta sa iyong central config file
require_once 'includes/config_final.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['otp'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

$email = trim($input['email']);
$otp = trim($input['otp']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_numeric($otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or OTP format.']);
    exit;
}

$userFoundInAnyDB = false;

// Isa-isahin ang lahat ng student databases na nasa config file
foreach ($databases as $dbName => $path) {
    if (!isset($connections[$dbName]))
        continue;
    $pdo = $connections[$dbName];
    try {
        // Hanapin kung may user sa database na ito na may ganitong email
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            // SUCCESS! Nahanap ang user sa database na ito!
            $hashed_otp = password_hash((string) $otp, PASSWORD_DEFAULT);
            $expiry_time = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $updateStmt = $pdo->prepare("UPDATE students SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
            $updateStmt->execute([$hashed_otp, $expiry_time, $email]);

            $userFoundInAnyDB = true;
            break; // Itigil na ang paghahanap dahil nahanap na natin ang user
        }
    } catch (PDOException $e) {
        // Laktawan lang kung may error sa isang DB
        continue;
    }
}

// Magpadala ng response pabalik sa JavaScript
echo json_encode(['success' => $userFoundInAnyDB, 'message' => $userFoundInAnyDB ? 'OTP saved.' : 'Account not found.']);
exit;
?>