<?php
// ERROR DETECTOR
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'includes/config_final.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$otp = $input['otp'] ?? '';

if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input. Please try again.']);
    exit;
}

foreach ($databases as $dbName => $path) {
    if (!isset($connections[$dbName]))
        continue;
    $pdo = $connections[$dbName];
    try {
        $stmt = $pdo->prepare("SELECT reset_token, reset_token_expires_at FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch();

        if ($userData) {
            if (empty($userData['reset_token']) || strtotime($userData['reset_token_expires_at']) < time()) {
                echo json_encode(['success' => false, 'message' => 'OTP has expired or is invalid. Please request a new one.']);
                exit;
            }
            if (password_verify($otp, $userData['reset_token'])) {
                echo json_encode(['success' => true]);
                exit;
            }
        }
    } catch (PDOException $e) {
        continue;
    }
}

echo json_encode(['success' => false, 'message' => 'The OTP you entered is incorrect.']);
?>