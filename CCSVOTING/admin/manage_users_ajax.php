<?php
require_once '../includes/db_connect.php';

// Set JSON header
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if it's an AJAX request
if (!isset($_POST['ajax']) || $_POST['ajax'] !== 'true') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Validate required parameters
if (!isset($_POST['user_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$user_id = $_POST['user_id']; // this is students.student_id (string)
$action = $_POST['action'];

try {
    // Get user data before action (in case of rejection)
    $stmt = $pdo->prepare("SELECT first_name, middle_name, last_name, email FROM students WHERE student_id = ?");
    $stmt->execute([$user_id]);
    $userData = $stmt->fetch();

    if (!$userData) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE students SET is_approved = 1 WHERE student_id = ?");
        $stmt->execute([$user_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'User approved successfully',
            'user_data' => $userData
        ]);

    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$user_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'User rejected and removed',
            'user_data' => $userData
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database operation failed']);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation failed']);
}
?>
