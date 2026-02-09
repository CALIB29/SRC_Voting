<?php


require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$success = false;
$schedule_message = '';
$keep_schedule_open = false; // Add this variable to track if schedule section should stay open

$total_users = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$approved_users = $pdo->query("SELECT COUNT(*) FROM students WHERE is_approved = 1")->fetchColumn();
$pending_users = $pdo->query("SELECT COUNT(*) FROM students WHERE is_approved = 0")->fetchColumn();
$total_candidates = $pdo->query("SELECT COUNT(*) FROM vot_candidates")->fetchColumn();
$voted_users = $pdo->query("SELECT COUNT(*) FROM students WHERE has_voted = 1")->fetchColumn();

// Handle schedule form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_schedule'])) {
        $keep_schedule_open = true; // Keep schedule section open after form submission
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        if (empty($start_date) || empty($end_date) || empty($start_time) || empty($end_time)) {
            $schedule_message = "All fields are required.";
        } else {
            $start_datetime = $start_date . ' ' . $start_time . ':00';
            $end_datetime = $end_date . ' ' . $end_time . ':00';

            $start_timestamp = strtotime($start_datetime);
            $end_timestamp = strtotime($end_datetime);

            if ($start_timestamp >= $end_timestamp) {
                $schedule_message = "End date/time must be after start date/time.";
            } else {
                $existing_schedule = $pdo->query("SELECT COUNT(*) FROM vot_voting_schedule")->fetchColumn();

                try {
                    if ($existing_schedule > 0) {
                        $stmt = $pdo->prepare("UPDATE vot_voting_schedule SET start_datetime = ?, end_datetime = ?, updated_at = NOW()");
                        $success = $stmt->execute([$start_datetime, $end_datetime]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO vot_voting_schedule (start_datetime, end_datetime, created_at) VALUES (?, ?, NOW())");
                        $success = $stmt->execute([$start_datetime, $end_datetime]);
                    }

                    $schedule_message = $success ? "Voting schedule updated successfully!" : "Error updating voting vot_schedules.";
                } catch (PDOException $e) {
                    $schedule_message = "Database error: " . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['reset_schedule'])) {
        $keep_schedule_open = true; // Keep schedule section open after form submission
        try {
            $stmt = $pdo->prepare("DELETE FROM vot_voting_schedule");
            $success = $stmt->execute();
            $schedule_message = $success ? "Voting schedule has been reset!" : "Error resetting vot_schedules.";
        } catch (PDOException $e) {
            $schedule_message = "Database error: " . $e->getMessage();
        }
    }
}

$schedule = $pdo->query("SELECT * FROM vot_voting_schedule LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Fetch latest election winners for dashboard display
require_once '../../includes/get_winners.php';
$winnersData = getLatestElectionWinners($pdo);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">

    <link rel="stylesheet" href="../../assets/css/mobile_base.css">
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('Dashboard'); ?>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../pic/srclogo.png" alt="Logo">
                <span>SRC Admin</span>
            </div>

            <nav class="nav-menu">
                <div class="nav-item active">
                    <a href="dashboard.php">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i>
                        <span>Voters</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="manage_candidates.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Candidates</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="results.php">
                        <i class="fas fa-poll-h"></i>
                        <span>Poll Results</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="archive.php">
                        <i class="fas fa-archive"></i>
                        <span>Archive</span>
                    </a>
                </div>
                <div class="nav-item" style="margin-top: auto;">
                    <a href="logout.php" class="text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <div class="welcome-text">
                    <h1>Admin Dashboard</h1>
                    <p>Santa Rita College Election Management System</p>
                </div>
                <div class="header-actions">
                    <button onclick="toggleSchedule()" class="modern-btn modern-btn-primary">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Election Schedule</span>
                    </button>
                </div>
            </header>

            <?php if ($winnersData && !empty($winnersData['winners'])): ?>
                <!-- Winners Spotlight Banner on Dashboard -->
                <div class="winners-spotlight-banner modern-card"
                    style="padding: 2rem; background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(0, 91, 170, 0.05)); border: 2px solid var(--primary); margin-bottom: 2rem; position: relative; overflow: hidden;">
                    <div
                        style="position: absolute; top: -20px; right: -20px; font-size: 8rem; color: rgba(79, 70, 229, 0.05); transform: rotate(-15deg);">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div
                                style="width: 50px; height: 50px; background: var(--warning); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-award" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); line-height: 1;">
                                    Election Winners</h2>
                                <p style="color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($winnersData['election']['title']); ?> - Official Final
                                    Results
                                </p>
                            </div>
                        </div>
                        <a href="results.php?id=<?php echo $winnersData['election']['id']; ?>" class="modern-button primary"
                            style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">
                            View Detailed Results
                        </a>
                    </div>

                    <div class="winners-scroll-container"
                        style="display: flex; gap: 1.5rem; overflow-x: auto; padding: 1.5rem 0 1rem 0; scrollbar-width: thin;">
                        <?php foreach ($winnersData['winners'] as $winner): ?>
                            <div class="winner-spotlight-card"
                                style="flex: 0 0 240px; background: var(--bg-card); border-radius: 20px; padding: 1.5rem; border: 1px solid var(--border); position: relative; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                                <div class="winner-badge"
                                    style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; padding: 6px 16px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4); white-space: nowrap; z-index: 10; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-crown" style="font-size: 0.8rem; color: #FFD700;"></i>
                                    <?php echo htmlspecialchars($winner['position']); ?>
                                </div>
                                <div style="text-align: center; margin-top: 0.5rem;">
                                    <div style="position: relative; display: inline-block;">
                                        <img src="<?php echo fixCandidatePhotoPath($winner['photo_path'], '../../'); ?>"
                                            onerror="this.src='../../logo/srclogo.png'"
                                            style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary);">
                                        <div
                                            style="position: absolute; bottom: 0; right: 0; background: var(--warning); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid var(--bg-card);">
                                            <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                        </div>
                                    </div>
                                    <h4
                                        style="margin-top: 1rem; font-weight: 700; color: var(--text-main); min-height: 2.4em; display: flex; align-items: center; justify-content: center;">
                                        <?php echo htmlspecialchars($winner['first_name'] . ' ' . $winner['last_name']); ?>
                                    </h4>
                                    <div
                                        style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--primary); font-weight: 700;">
                                        <i class="fas fa-vote-yea"></i>
                                        <span><?php echo number_format($winner['votes']); ?> Votes</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($schedule_message): ?>
                <div class="modern-card"
                    style="border-left: 4px solid <?php echo $success ? 'var(--success)' : 'var(--danger)'; ?>; margin-bottom: 2rem;">
                    <p style="color: <?php echo $success ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: 600;">
                        <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                        <?php echo $schedule_message; ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Schedule Section -->
            <section id="scheduleSection" class="modern-card"
                style="display: <?php echo $keep_schedule_open ? 'block' : 'none'; ?>; margin-bottom: 2rem; border-color: var(--primary);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="font-weight: 700; color: var(--text-main);">Election Configuration</h3>
                    <button onclick="toggleSchedule()" class="modern-btn" style="padding: 0.5rem;"><i
                            class="fas fa-times"></i></button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <!-- Current Schedule -->
                    <div>
                        <h4
                            style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem;">
                            Current Status</h4>
                        <?php if ($schedule): ?>
                            <div
                                style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted);">Starts
                                        On</label>
                                    <span style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">
                                        <?php echo date('M d, Y - h:i A', strtotime($schedule['start_datetime'])); ?>
                                    </span>
                                </div>
                                <div style="margin-bottom: 1.5rem;">
                                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted);">Ends
                                        On</label>
                                    <span style="font-size: 1.1rem; font-weight: 600; color: var(--secondary);">
                                        <?php echo date('M d, Y - h:i A', strtotime($schedule['end_datetime'])); ?>
                                    </span>
                                </div>
                                <form method="POST"
                                    onsubmit="return confirm('Are you sure you want to reset the schedule?')">
                                    <button type="submit" name="reset_schedule" class="modern-btn modern-btn-danger"
                                        style="width: 100%;">
                                        <i class="fas fa-undo"></i> Reset Election
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div
                                style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                                <i class="fas fa-clock"
                                    style="font-size: 2rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <p style="color: var(--text-muted);">No election scheduled</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Set Schedule Form -->
                    <div>
                        <h4
                            style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem;">
                            Update vot_schedules</h4>
                        <form method="POST" class="schedule-form">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label
                                        style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Start
                                        Date</label>
                                    <input type="date" name="start_date" class="modern-input" required
                                        value="<?php echo $schedule ? date('Y-m-d', strtotime($schedule['start_datetime'])) : ''; ?>">
                                </div>
                                <div>
                                    <label
                                        style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Start
                                        Time</label>
                                    <input type="time" name="start_time" class="modern-input" required
                                        value="<?php echo $schedule ? date('H:i', strtotime($schedule['start_datetime'])) : ''; ?>">
                                </div>
                            </div>
                            <div
                                style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label
                                        style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">End
                                        Date</label>
                                    <input type="date" name="end_date" class="modern-input" required
                                        value="<?php echo $schedule ? date('Y-m-d', strtotime($schedule['end_datetime'])) : ''; ?>">
                                </div>
                                <div>
                                    <label
                                        style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">End
                                        Time</label>
                                    <input type="time" name="end_time" class="modern-input" required
                                        value="<?php echo $schedule ? date('H:i', strtotime($schedule['end_datetime'])) : ''; ?>">
                                </div>
                            </div>
                            <button type="submit" name="set_schedule" class="modern-btn modern-btn-primary"
                                style="width: 100%;">
                                <i class="fas fa-save"></i> Save Configuration
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="stat-label">Total Registered</span>
                    <span class="stat-value"><?php echo number_format($total_users); ?></span>
                </div>

                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <span class="stat-label">Approved Voters</span>
                    <span class="stat-value"><?php echo number_format($approved_users); ?></span>
                </div>

                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <span class="stat-label">Pending Approval</span>
                    <span class="stat-value"><?php echo number_format($pending_users); ?></span>
                </div>

                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background: rgba(124, 58, 237, 0.1); color: var(--secondary);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <span class="stat-label">Candidates</span>
                    <span class="stat-value"><?php echo number_format($total_candidates); ?></span>
                </div>

                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background: rgba(255, 255, 255, 0.1); color: #FFF;">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <span class="stat-label">Votes Cast</span>
                    <span class="stat-value"><?php echo number_format($voted_users); ?></span>
                </div>
            </div>

            <!-- Quick Actions -->
            <h3 style="font-weight: 700; color: var(--text-main); margin-bottom: 1.5rem;">Quick Management</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <a href="manage_users.php" class="modern-card"
                    style="text-decoration: none; text-align: center; border-color: transparent;">
                    <i class="fas fa-user-plus"
                        style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem; display: block;"></i>
                    <span style="font-weight: 600; color: var(--text-main);">Manage Voters</span>
                </a>
                <a href="manage_candidates.php" class="modern-card"
                    style="text-decoration: none; text-align: center; border-color: transparent;">
                    <i class="fas fa-id-card"
                        style="font-size: 2rem; color: var(--success); margin-bottom: 1rem; display: block;"></i>
                    <span style="font-weight: 600; color: var(--text-main);">Manage Candidates</span>
                </a>
                <a href="results.php" class="modern-card"
                    style="text-decoration: none; text-align: center; border-color: transparent;">
                    <i class="fas fa-chart-line"
                        style="font-size: 2rem; color: var(--secondary); margin-bottom: 1rem; display: block;"></i>
                    <span style="font-weight: 600; color: var(--text-main);">View Results</span>
                </a>
            </div>
        </main>
    </div>

    <script>
        function toggleSchedule() {
            const section = document.getElementById('scheduleSection');
            if (section.style.display === 'none') {
                section.style.display = 'block';
                section.scrollIntoView({ behavior: 'smooth' });
            } else {
                section.style.display = 'none';
            }
        }

        // Form validation for schedule dates
        const scheduleForm = document.querySelector('.schedule-form');
        if (scheduleForm) {
            scheduleForm.addEventListener('submit', function (e) {
                const startDate = this.querySelector('input[name="start_date"]').value;
                const endDate = this.querySelector('input[name="end_date"]').value;
                const startTime = this.querySelector('input[name="start_time"]').value;
                const endTime = this.querySelector('input[name="end_time"]').value;

                if (!startDate || !endDate || !startTime || !endTime) return;

                const startDateTime = new Date(startDate + 'T' + startTime);
                const endDateTime = new Date(endDate + 'T' + endTime);

                if (startDateTime >= endDateTime) {
                    e.preventDefault();
                    alert('End date/time must be after start date/time.');
                    return false;
                }
            });
        }
    </script>

    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('admin'); ?>
</body>

</html>