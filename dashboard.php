<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Fetch all student details using JOINs for course and departments names
$student_id = $_SESSION['user_id'];
$user = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            s.student_id, s.first_name, s.last_name, s.email, s.profile_picture, s.has_voted,
            c.course_name,
            d.department_name
        FROM students s
        LEFT JOIN courses c ON s.course_id = c.course_id
        LEFT JOIN vot_departments d ON s.department_id = d.department_id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch user data.");
}

// If user not found in DB (e.g., deleted after login), log them out
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$has_voted = (bool) $user['has_voted'];

// Check for the latest concluded election to show celebration
$stmt_concluded = $pdo->query("SELECT id, title, end_datetime FROM vot_elections WHERE is_active = 0 AND end_datetime IS NOT NULL ORDER BY end_datetime DESC LIMIT 1");
$latest_concluded = $stmt_concluded->fetch();
$show_celebration = false;
if ($latest_concluded) {
    if ($latest_concluded['end_datetime']) {
        $now = new DateTime();
        $end_date = new DateTime($latest_concluded['end_datetime']);
        $diff = $now->diff($end_date);
        // Show celebration if it ended within the last 48 hours
        if ($diff->days < 2) {
            $show_celebration = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | SRC Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard_style.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">
                <img src="logo/srclogo.png" alt="SRC Logo">
                <h5>SRC Voting System</h5>
            </div>
            <ul>
                <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote Now</a></li>
                <li><a href="resultview.php"><i class="fas fa-poll"></i> View Results</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">Student Dashboard</div>
                <div class="user-info">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/images/default-avatar.png'); ?>"
                            alt="Avatar">
                    </div>
                    <span><?php echo htmlspecialchars($user['first_name']); ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="welcome-header">
                    <h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                </div>

                <?php if ($show_celebration): ?>
                    <!-- Celebration Banner for Recently Concluded Election -->
                    <div class="celebration-banner"
                        style="background: linear-gradient(135deg, #4361ee, #3f37c9); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 30px; display: flex; align-items: center; gap: 1.5rem; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12); animation: slideIn 0.5s ease-out; position: relative; overflow: hidden;">
                        <div style="font-size: 2.5rem;">🏆</div>
                        <div style="flex: 1;">
                            <h3 style="margin: 0; font-size: 1.4rem; font-weight: 700; color: white;">Election Results are
                                Out!</h3>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">The election
                                "<?php echo htmlspecialchars($latest_concluded['title']); ?>" has ended. Check the results
                                now!</p>
                            <!-- Note: User might need to navigate to departments specific resultview -->
                            <p style="margin-top: 5px; font-size: 0.8rem; opacity: 0.8;">Visit your departments portal to
                                see
                                the full results.</p>
                        </div>
                        <div style="font-size: 2.5rem;">✨</div>
                    </div>
                    <style>
                        @keyframes slideIn {
                            from {
                                transform: translateY(-20px);
                                opacity: 0;
                            }

                            to {
                                transform: translateY(0);
                                opacity: 1;
                            }
                        }
                    </style>
                    <script>
                        window.addEventListener('load', () => {
                            const duration = 4 * 1000;
                            const end = Date.now() + duration;

                            (function frame() {
                                confetti({
                                    particleCount: 3,
                                    angle: 60,
                                    spread: 55,
                                    origin: { x: 0 },
                                    colors: ['#4361ee', '#3f37c9', '#4cc9f0']
                                });
                                confetti({
                                    particleCount: 3,
                                    angle: 120,
                                    spread: 55,
                                    origin: { x: 1 },
                                    colors: ['#4361ee', '#3f37c9', '#4cc9f0']
                                });

                                if (Date.now() < end) {
                                    requestAnimationFrame(frame);
                                }
                            }());
                        });
                    </script>
                <?php endif; ?>

                <?php if ($has_voted): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i>
                        <p><strong>Thank you for voting!</strong> Your vote has been recorded.</p>
                    </div>
                <?php else: ?>
                    <div class="alert info">
                        <i class="fas fa-info-circle"></i>
                        <p>You have not voted yet. <a href="vote.php">Go to the voting page.</a></p>
                    </div>
                <?php endif; ?>

                <div class="user-details">
                    <h3><i class="fas fa-id-card"></i> Your Information</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="label">Student ID</span>
                            <span class="value"><?php echo htmlspecialchars($user['student_id']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Email</span>
                            <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">departments</span>
                            <span
                                class="value"><?php echo htmlspecialchars($user['department_name'] ?? 'Not Assigned'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Course</span>
                            <span
                                class="value"><?php echo htmlspecialchars($user['course_name'] ?? 'Not Assigned'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Voting Status</span>
                            <span class="value <?php echo $has_voted ? 'voted' : 'not-voted'; ?>">
                                <?php echo $has_voted ? 'Voted' : 'Not Voted'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>