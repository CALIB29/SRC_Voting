<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch user details from DB
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT s.*, c.course_name, d.department_name FROM students s LEFT JOIN course c ON s.course_id = c.course_id LEFT JOIN department d ON s.department_id = d.department_id WHERE s.student_id = ?");
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
$stmt_concluded = $pdo->query("SELECT id, title, end_datetime FROM elections WHERE is_active = 0 AND end_datetime IS NOT NULL ORDER BY end_datetime DESC LIMIT 1");
$latest_concluded = $stmt_concluded->fetch();
$show_celebration = false;
if ($latest_concluded) {
    $now = new DateTime();
    $end_date = new DateTime($latest_concluded['end_datetime']);
    $diff = $now->diff($end_date);
    // Show celebration if it ended within the last 48 hours
    if ($diff->days < 2) {
        $show_celebration = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #eef1fd;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --success-light: #e8f8fd;
            --success-dark: #3ab7e0;
            --info: #4895ef;
            --info-light: #e8f2fe;
            --warning: #f72585;
            --warning-light: #fdebf3;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.03);
            padding: 20px 0;
            transition: var(--transition);
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 100%;
            height: auto;
            max-height: 70px;
            margin-bottom: 10px;
        }

        .logo h5 {
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 5px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0 15px;
        }

        .sidebar li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            border-radius: var(--border-radius-sm);
            margin-bottom: 5px;
            font-weight: 500;
        }

        .sidebar li a:hover {
            background-color: var(--primary-light);
            color: var(--primary);
            transform: translateX(3px);
        }

        .sidebar li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar li.active a {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--primary);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-left: 280px;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.03);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
            font-size: 1rem;
        }

        .user-info span {
            margin-right: 15px;
            font-weight: 500;
            color: var(--dark);
        }

        .logout-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .logout-btn i {
            margin-right: 8px;
        }

        .logout-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
        }

        /* Content Area */
        .content-area {
            padding: 30px;
            flex: 1;
        }

        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .welcome-header h2 {
            color: var(--dark);
            font-weight: 700;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }

        .welcome-header h2 i {
            margin-right: 12px;
            color: var(--primary);
        }

        .time-date {
            background: white;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            font-weight: 500;
            color: var(--gray);
        }

        /* Alert Cards */
        .alert {
            padding: 20px 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            background-color: white;
            border-left: 4px solid transparent;
            transition: var(--transition);
        }

        .alert:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .alert.success {
            border-left-color: var(--success);
            background-color: var(--success-light);
            color: var(--success-dark);
        }

        .alert.info {
            border-left-color: var(--info);
            background-color: var(--info-light);
            color: var(--info);
        }

        .alert i {
            margin-right: 15px;
            font-size: 1.5rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content p {
            margin-bottom: 5px;
        }

        .alert-content a {
            color: inherit;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
        }

        .alert-content a:hover {
            text-decoration: underline;
        }

        .alert-content a i {
            margin-left: 5px;
            font-size: 0.9rem;
        }

        /* User Details Card */
        .user-details {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .user-details:hover {
            box-shadow: var(--box-shadow-lg);
        }

        .user-details h3 {
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
            font-weight: 600;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }

        .user-details h3 i {
            margin-right: 12px;
            color: var(--primary);
        }

        .user-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .detail-value {
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Quick Actions */
        .quick-actions {
            margin-top: 40px;
        }

        .quick-actions h3 {
            color: var(--dark);
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }

        .quick-actions h3 i {
            margin-right: 12px;
            color: var(--primary);
        }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            text-align: center;
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .action-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .action-card h4 {
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .action-card p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .user-details-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
            }

            .main-content {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 10px 0;
            }

            .sidebar ul {
                display: flex;
                overflow-x: auto;
                padding: 0 10px;
            }

            .sidebar li {
                flex: 0 0 auto;
            }

            .sidebar li a {
                padding: 10px 15px;
                border-left: none;
                border-bottom: 3px solid transparent;
                white-space: nowrap;
            }

            .sidebar li.active a {
                border-left: none;
                border-bottom: 3px solid var(--primary);
            }

            .main-content {
                margin-left: 0;
            }

            .welcome-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .time-date {
                margin-top: 15px;
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .content-area {
                padding: 20px;
            }

            .top-bar {
                padding: 15px 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .user-info {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
            }

            .action-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert,
        .user-details,
        .quick-actions {
            animation: fadeIn 0.5s ease forwards;
        }

        .alert {
            animation-delay: 0.1s;
        }

        .user-details {
            animation-delay: 0.2s;
        }

        .quick-actions {
            animation-delay: 0.3s;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="pic/srclogo.png" alt="Santa Rita College Logo">
                <h5>Santa Rita College</h5>
                <div style="font-size:0.95em;color:#4361ee;font-weight:500;margin-top:4px;">
                    <?php echo isset($_SESSION['department']) ? str_replace([' VOTING', '_', '-'], '', $_SESSION['department']) : 'Department'; ?>
                </div>
            </div>
            <ul>
                <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote Now</a></li>
                <li><a href="view.php"><i class="fas fa-users"></i> View Candidates</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">Student Dashboard</div>
                <div class="user-info">
                    <div class="user-avatar"><?php echo substr($_SESSION['first_name'], 0, 1); ?></div>
                    <span>
                        <?php
                        // Safely display first name
                        echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Guest';
                        ?>
                    </span>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="welcome-header">
                    <h2><i class="fas fa-user-graduate"></i> Welcome,
                        <?php echo $_SESSION['first_name']; ?><?php if (isset($_SESSION['department'])) {
                               echo ' <span style="font-size:0.7em;color:#4361ee;">(' . str_replace([' VOTING', '_', '-'], '', $_SESSION['department']) . ')</span>';
                           } ?>
                    </h2>
                    <div class="time-date">
                        <i class="far fa-calendar-alt"></i> <span id="current-date"></span>
                        <i class="far fa-clock" style="margin-left: 15px;"></i> <span id="current-time"></span>
                    </div>
                </div>

                <?php if ($show_celebration): ?>
                    <!-- Celebration Banner for Recently Concluded Election -->
                    <div class="celebration-banner"
                        style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 30px; display: flex; align-items: center; gap: 1.5rem; box-shadow: var(--box-shadow-lg); animation: slideIn 0.5s ease-out; position: relative; overflow: hidden;">
                        <div style="font-size: 2.5rem;">🏆</div>
                        <div style="flex: 1;">
                            <h3 style="margin: 0; font-size: 1.4rem; font-weight: 700; color: white;">Election Results are
                                Out!</h3>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">The election
                                "<?php echo htmlspecialchars($latest_concluded['title']); ?>" has ended. Check the results
                                now!</p>
                            <a href="resultview.php"
                                style="display: inline-block; margin-top: 10px; color: white; background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: background 0.3s;">View
                                Full Results →</a>
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
                        <div class="alert-content">
                            <p><strong>Thank you for voting!</strong> Your vote has been successfully recorded.</p>
                            <a href="resultview.php">View election results <i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert info">
                        <i class="fas fa-info-circle"></i>
                        <div class="alert-content">
                            <a href="vote.php">Go to voting page <i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="user-details">
                    <h3><i class="fas fa-id-card"></i> Student Information</h3>
                    <div class="user-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Department</span>
                            <span class="detail-value">
                                <?php echo isset($_SESSION['department']) ? str_replace([' VOTING', '_', '-'], '', $_SESSION['department']) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Student ID</span>
                            <span class="detail-value"><?php echo $user['student_id']; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Course</span>
                            <span
                                class="detail-value"><?php echo htmlspecialchars($user['course_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Department</span>
                            <span
                                class="detail-value"><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Year & Section</span>
                            <span class="detail-value">N/A</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Voting Status</span>
                            <span class="detail-value"
                                style="color: <?php echo $has_voted ? 'var(--success-dark)' : 'var(--warning)'; ?>">
                                <?php echo $has_voted ? 'Voted' : 'Not Voted'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="quick-actions">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="action-cards">
                        <a href="vote.php" class="action-card">
                            <i class="fas fa-vote-yea"></i>
                            <h4>Cast Vote</h4>
                            <p>Participate in the current election</p>
                        </a>
                        <a href="resultview.php" class="action-card">
                            <i class="fas fa-poll"></i>
                            <h4>View Results</h4>
                            <p>See current election standings</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update current date and time
        function updateDateTime() {
            const now = new Date();

            // Format date (e.g., "Monday, September 20, 2023")
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);

            // Format time (e.g., "11:45 AM")
            let hours = now.getHours();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            const minutes = now.getMinutes().toString().padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes} ${ampm}`;
        }

        // Update immediately and then every minute
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Add animation to action cards on hover
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mouseenter', function () {
                const icon = this.querySelector('i');
                icon.style.transform = 'scale(1.1)';
                icon.style.transition = 'transform 0.3s ease';
            });

            card.addEventListener('mouseleave', function () {
                const icon = this.querySelector('i');
                icon.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>