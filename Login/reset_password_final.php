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
$new_password = $input['new_password'] ?? '';

if (empty($email) || empty($otp) || empty($new_password) || strlen($new_password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided or password is too short.']);
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

        if ($userData && !empty($userData['reset_token']) && password_verify($otp, $userData['reset_token'])) {
            // Double check expiration bago mag-reset
            if (strtotime($userData['reset_token_expires_at']) < time()) {
                echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
                exit;
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // I-update ang password at i-clear ang reset token
            $updateStmt = $pdo->prepare("UPDATE students SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE email = ?");
            $updateStmt->execute([$hashed_password, $email]);

            echo json_encode(['success' => true, 'message' => 'Password has been reset successfully!']);
            exit;
        }
    } catch (PDOException $e) {
        continue;
    }
}

echo json_encode(['success' => false, 'message' => 'Failed to reset password. The OTP may be incorrect. Please try again.']);
?>