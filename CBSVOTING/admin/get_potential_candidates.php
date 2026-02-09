<?php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Get active election
    $active_election_id = $pdo->query("SELECT id FROM vot_elections WHERE is_active = 1")->fetchColumn();

    // Get current candidates names to exclude
    $current_candidates = [];
    if ($active_election_id) {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM vot_candidates WHERE election_id = ?");
        $stmt->execute([$active_election_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $current_candidates[] = strtolower($row['first_name'] . ' ' . $row['last_name']);
        }
    }

    // Search students
    $stmt = $pdo->prepare("SELECT student_id, first_name, middle_name, last_name, course_id FROM students 
                           WHERE first_name LIKE :search 
                           OR last_name LIKE :search 
                           OR middle_name LIKE :search 
                           LIMIT 10");
    $searchTerm = "%$search%";
    $stmt->bindValue(':search', $searchTerm);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($students as $student) {
        $fullName = strtolower($student['first_name'] . ' ' . $student['last_name']);
        if (in_array($fullName, $current_candidates)) {
            continue; // Already a candidate
        }

        // Check past candidacy (excluding current election)
        $c_stmt = $pdo->prepare("SELECT position FROM vot_candidates 
                                 WHERE first_name = ? AND last_name = ? 
                                 AND election_id != ? 
                                 ORDER BY id DESC LIMIT 1");
        $c_stmt->execute([$student['first_name'], $student['last_name'], $active_election_id ?: 0]);
        $past = $c_stmt->fetch(PDO::FETCH_ASSOC);

        $student['is_past_candidate'] = $past ? true : false;
        $student['past_position'] = $past ? $past['position'] : null;

        $results[] = $student;
    }

    header('Content-Type: application/json');
    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>