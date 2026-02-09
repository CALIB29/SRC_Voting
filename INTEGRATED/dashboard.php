<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/']);
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch user details from DB
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT s.*, c.course_name, d.department_name FROM students s LEFT JOIN courses c ON s.course_id = c.course_id LEFT JOIN departments d ON s.department_id = d.department_id WHERE s.student_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found in DB, log out
if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Set session variables if not already set
if (!isset($_SESSION['first_name'])) {
    $_SESSION['first_name'] = $user['first_name'];
}

// Check if user has already voted
$has_voted = $user['has_voted'];

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
    <title>User Dashboard | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <link rel="stylesheet" href="../assets/css/student_premium.css">
    <link rel="stylesheet" href="../assets/css/mobile_base.css">
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('Dashboard'); ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="pic/srclogo.png" alt="Logo">
                <div class="sidebar-brand">
                    <h5>Santa Rita College</h5>
                    <span
                        class="dept-badge"><?php echo isset($_SESSION['departments']) ? str_replace([' VOTING', '_', '-'], '', $_SESSION['departments']) : 'Voting System'; ?></span>
                </div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item active"><a href="dashboard.php" class="nav-link"><i
                            class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li class="nav-item"><a href="vote.php" class="nav-link"><i class="fas fa-vote-yea"></i> <span>Vote
                            Now</span></a></li>
                <li class="nav-item"><a href="view.php" class="nav-link"><i class="fas fa-users"></i> <span>View
                            Candidates</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Student Portal</h1>
                </div>
                <div class="user-profile">
                    <div class="avatar"><?php echo substr($_SESSION['first_name'], 0, 1); ?></div>
                    <div class="user-meta">
                        <span
                            class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Student'); ?></span>
                        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="welcome-section">
                    <div class="welcome-text">
                        <h2>Welcome Back, <?php echo $_SESSION['first_name']; ?>!</h2>
                        <p>Track your voting status and election Standing.</p>
                    </div>
                    <div class="time-widget">
                        <div class="time-item">
                            <i class="far fa-calendar-alt"></i>
                            <span id="current-date"></span>
                        </div>
                        <div class="time-item">
                            <i class="far fa-clock"></i>
                            <span id="current-time"></span>
                        </div>
                    </div>
                </div>

                <?php if ($show_celebration): ?>
                    <!-- Celebration Banner -->
                    <div class="celebration-card">
                        <div class="confetti-icon">🏆</div>
                        <div class="celebration-info">
                            <h3>Election Results are Live!</h3>
                            <p>The election "<?php echo htmlspecialchars($latest_concluded['title']); ?>" has concluded. See
                                who won!</p>
                            <a href="resultview.php" class="premium-btn">View Results →</a>
                        </div>
                        <script>
                            window.addEventListener('load', () => {
                                confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 }, colors: ['#6366f1', '#a855f7', '#06b6d4'] });
                            });
                        </script>
                    </div>
                <?php endif; ?>

                <div class="dashboard-grid">
                    <?php if ($has_voted): ?>
                        <div class="status-card success">
                            <i class="fas fa-check-circle"></i>
                            <div class="status-content">
                                <h3>Vote Recorded</h3>
                                <p>Thank you for participating! Your voice has been heard.</p>
                                <a href="resultview.php" class="status-link">View Standings →</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="status-card pending">
                            <i class="fas fa-exclamation-circle"></i>
                            <div class="status-content">
                                <h3>Action Required</h3>
                                <p>You haven't cast your vote yet. Make sure your voice counts!</p>
                                <a href="vote.php" class="status-link vote">Vote Now →</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="info-card">
                        <div class="card-header">
                            <i class="fas fa-id-card"></i>
                            <h3>Student Information</h3>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Department</label>
                                <span><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Student ID</label>
                                <span><?php echo $user['student_id']; ?></span>
                            </div>
                            <div class="info-item">
                                <label>Course</label>
                                <span><?php echo htmlspecialchars($user['course_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Voting Status</label>
                                <span class="badge <?php echo $has_voted ? 'success' : 'warning'; ?>">
                                    <?php echo $has_voted ? 'Already Voted' : 'Not Yet Voted'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="quick-actions-section">
                    <h3>Quick Actions</h3>
                    <div class="action-grid">
                        <a href="vote.php" class="action-card">
                            <div class="icon-box primary"><i class="fas fa-vote-yea"></i></div>
                            <h4>Cast Vote</h4>
                            <p>Official ballot</p>
                        </a>
                        <a href="resultview.php" class="action-card">
                            <div class="icon-box secondary"><i class="fas fa-poll"></i></div>
                            <h4>Election Heat</h4>
                            <p>Live standings</p>
                        </a>
                        <a href="view.php" class="action-card">
                            <div class="icon-box accent"><i class="fas fa-users"></i></div>
                            <h4>Candidates</h4>
                            <p>Meet the team</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
            let hours = now.getHours();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const minutes = now.getMinutes().toString().padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes} ${ampm}`;
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>

    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('student'); ?>
</body>

</html>