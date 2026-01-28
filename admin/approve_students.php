<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$department_id = $_SESSION['department_id'];
$message = '';
$message_type = '';

// Handle student approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_student_id'])) {
    $student_to_approve = $_POST['approve_student_id'];

    try {
        $stmt = $pdo->prepare("UPDATE students SET is_approved = 1 WHERE student_id = ? AND department_id = ?");
        $stmt->execute([$student_to_approve, $department_id]);

        if ($stmt->rowCount() > 0) {
            $message = "Student account has been approved successfully.";
            $message_type = 'success';
        } else {
            $message = "Error: Student not found or already approved.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch pending students for the admin's department
$pending_students = [];
try {
    $stmt = $pdo->prepare(
        "SELECT s.student_id, s.first_name, s.middle_name, s.last_name, s.email, c.course_name " .
        "FROM students s " .
        "JOIN course c ON s.course_id = c.course_id " .
        "WHERE s.is_approved = 0 AND s.department_id = ? ORDER BY s.last_name, s.first_name"
    );
    $stmt->execute([$department_id]);
    $pending_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $page_error = "Could not fetch pending students: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Students | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css"> 
</head>
<body>
    <div class="admin-dashboard-container">
        <!-- Top Bar -->
        <div class="admin-top-bar">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <span>SRC Voting System</span>
            </div>
            <div class="admin-info">
                <span><?php echo htmlspecialchars($_SESSION['department_name']); ?> Admin</span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <div class="admin-main-content">
            <!-- Sidebar -->
            <div class="admin-sidebar">
                 <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                    <li class="active"><a href="approve_students.php"><i class="fas fa-user-check"></i><span>Approve Students</span></a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users-cog"></i><span>Manage Users</span></a></li>
                    <li><a href="manage_candidates.php"><i class="fas fa-user-tie"></i><span>Manage Candidates</span></a></li>
                    <li><a href="results.php"><i class="fas fa-chart-pie"></i><span>Results Analytics</span></a></li>
                </ul>
            </div> 

            <!-- Main Content Area -->
            <div class="admin-content-area">
                <div class="content-header">
                    <h2>Pending Student Approvals</h2>
                </div>

                <?php if (!empty($message)) : ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($page_error)) : ?>
                    <div class="message error">
                        <?php echo htmlspecialchars($page_error); ?>
                    </div>
                <?php else : ?>
                    <div class="content-table-container">
                        <?php if (empty($pending_students)) : ?>
                            <p>No students are currently pending approval in your department.</p>
                        <?php else : ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_students as $student) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars(trim($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name'])); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                                            <td>
                                                <form method="POST" action="approve_students.php" style="display:inline;">
                                                    <input type="hidden" name="approve_student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                                    <button type="submit" class="btn btn-success">Approve</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
