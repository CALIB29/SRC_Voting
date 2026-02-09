<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$success = false;
$schedule_message = '';
$keep_schedule_open = false;

$total_users = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$approved_users = $pdo->query("SELECT COUNT(*) FROM students WHERE is_approved = 1")->fetchColumn();
$pending_users = $pdo->query("SELECT COUNT(*) FROM students WHERE is_approved = 0")->fetchColumn();
$total_candidates = $pdo->query("SELECT COUNT(*) FROM vot_candidates")->fetchColumn();
$voted_users = $pdo->query("SELECT COUNT(*) FROM students WHERE has_voted = 1")->fetchColumn();

// Handle schedule form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_schedule'])) {
        $keep_schedule_open = true;
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        if (empty($start_date) || empty($end_date) || empty($start_time) || empty($end_time)) {
            $schedule_message = "All fields are required.";
        } else {
            $start_datetime = $start_date . ' ' . $start_time . ':00';
            $end_datetime = $end_date . ' ' . $end_time . ':00';

            if (strtotime($start_datetime) >= strtotime($end_datetime)) {
                $schedule_message = "End date/time must be after start date/time.";
            } else {
                try {
                    $existing_schedule = $pdo->query("SELECT COUNT(*) FROM vot_voting_schedule")->fetchColumn();
                    $election_name = "Election " . date('Y', strtotime($start_datetime));

                    if ($existing_schedule > 0) {
                        $stmt = $pdo->prepare("UPDATE vot_voting_schedule SET election_name = ?, start_datetime = ?, end_datetime = ?, updated_at = NOW()");
                        $success = $stmt->execute([$election_name, $start_datetime, $end_datetime]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO vot_voting_schedule (election_name, start_datetime, end_datetime, created_at) VALUES (?, ?, ?, NOW())");
                        $success = $stmt->execute([$election_name, $start_datetime, $end_datetime]);
                    }
                    $schedule_message = $success ? "Voting schedule updated successfully!" : "Error updating voting schedule.";
                } catch (PDOException $e) {
                    $schedule_message = "Database error: " . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['reset_schedule'])) {
        $keep_schedule_open = true;
        try {
            $stmt = $pdo->prepare("DELETE FROM vot_voting_schedule");
            $success = $stmt->execute();
            $schedule_message = $success ? "Voting schedule has been reset!" : "Error resetting schedule.";
        } catch (PDOException $e) {
            $schedule_message = "Database error: " . $e->getMessage();
        }
    }
}

$schedule = $pdo->query("SELECT * FROM vot_voting_schedule LIMIT 1")->fetch(PDO::FETCH_ASSOC);

require_once '../../includes/get_winners.php';
$winnersData = getLatestElectionWinners($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">
    <style>
        .winner-spotlight {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.2);
            margin-bottom: 2rem;
        }

        .winner-card {
            flex: 0 0 240px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
        }

        .winner-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid var(--primary);
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/mobile_base.css">
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('Dashboard'); ?>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../pic/srclogo.png" alt="Logo">
                <span>SRC Admin</span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item active"><a href="dashboard.php"><i
                            class="fas fa-chart-pie"></i><span>Dashboard</span></a></div>
                <div class="nav-item"><a href="manage_users.php"><i class="fas fa-users"></i><span>Voters</span></a>
                </div>
                <div class="nav-item"><a href="manage_candidates.php"><i
                            class="fas fa-user-tie"></i><span>Candidates</span></a></div>
                <div class="nav-item"><a href="results.php"><i class="fas fa-poll-h"></i><span>Poll Results</span></a>
                </div>
                <div class="nav-item"><a href="archive.php"><i class="fas fa-archive"></i><span>Archive</span></a></div>
                <div class="nav-item" style="margin-top: auto;"><a href="logout.php" style="color:var(--danger)"><i
                            class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div class="welcome-text">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, Administrator</p>
                </div>
            </header>

            <div class="stats-grid">
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background:rgba(99,102,241,0.1); color:var(--primary);"><i
                            class="fas fa-users"></i></div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background:rgba(16,185,129,0.1); color:var(--success);"><i
                            class="fas fa-user-check"></i></div>
                    <div class="stat-label">Approved</div>
                    <div class="stat-value"><?php echo $approved_users; ?></div>
                </div>
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background:rgba(6,182,212,0.1); color:var(--accent);"><i
                            class="fas fa-vote-yea"></i></div>
                    <div class="stat-label">Votes Cast</div>
                    <div class="stat-value"><?php echo $voted_users; ?></div>
                </div>
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background:rgba(168,85,247,0.1); color:var(--secondary);"><i
                            class="fas fa-user-tie"></i></div>
                    <div class="stat-label">Candidates</div>
                    <div class="stat-value"><?php echo $total_candidates; ?></div>
                </div>
            </div>

            <?php if ($winnersData && !empty($winnersData['winners'])): ?>
                <div class="modern-card"
                    style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1)); border: 1px solid rgba(99, 102, 241, 0.2); margin-bottom: 2rem; position: relative; overflow: hidden;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div
                                style="width: 45px; height: 45px; background: var(--warning); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-trophy" style="font-size: 1.25rem;"></i>
                            </div>
                            <div>
                                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); line-height: 1;">
                                    Leading Candidates</h2>
                                <p style="color: var(--text-muted); font-weight: 500; font-size: 0.85rem;">
                                    Current top results for all positions
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="winners-scroll-container"
                        style="display: flex; gap: 1.5rem; overflow-x: auto; padding: 1.5rem 0 1rem 0; scrollbar-width: thin;">
                        <?php foreach ($winnersData['winners'] as $winner): ?>
                            <div class="winner-spotlight-card"
                                style="flex: 0 0 240px; background: var(--bg-card); border-radius: 20px; padding: 1.5rem; border: 1px solid var(--border); position: relative; box-shadow: 0 10px 20px rgba(0,0,0,0.1); text-align: center;">
                                <div class="winner-badge"
                                    style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; padding: 6px 16px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4); white-space: nowrap; z-index: 10; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-crown" style="font-size: 0.8rem; color: #FFD700;"></i>
                                    <?php echo htmlspecialchars($winner['position']); ?>
                                </div>
                                <div style="margin-top: 0.5rem;">
                                    <div style="position: relative; display: inline-block; margin-bottom: 1rem;">
                                        <img src="<?php echo fixCandidatePhotoPath($winner['photo_path'], '../../'); ?>"
                                            onerror="this.src='../../logo/srclogo.png'"
                                            style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary);">
                                        <div
                                            style="position: absolute; bottom: 0; right: 0; background: var(--warning); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid var(--bg-card);">
                                            <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                        </div>
                                    </div>
                                    <h4
                                        style="font-weight: 700; color: var(--text-main); min-height: 2.4em; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; font-size: 1rem;">
                                        <?php echo htmlspecialchars($winner['first_name'] . ' ' . $winner['last_name']); ?>
                                    </h4>
                                    <div
                                        style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--primary); font-weight: 700;">
                                        <i class="fas fa-vote-yea"></i>
                                        <span><?php echo number_format($winner['votes']); ?> Votes</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="modern-card">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <div
                        style="padding: 0.5rem; background: rgba(245,158,11,0.1); border-radius: 8px; color: var(--warning);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 style="font-size: 1.25rem; font-weight: 700;">Election Schedule</h3>
                </div>

                <?php if ($schedule_message): ?>
                    <div
                        style="padding: 1rem; border-radius: var(--radius-md); background: rgba(16,185,129,0.1); color: var(--success); margin-bottom: 1.5rem;">
                        <i class="fas fa-info-circle"></i> <?php echo $schedule_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Start Date & Time</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <input type="date" name="start_date"
                                    value="<?php echo $schedule ? date('Y-m-d', strtotime($schedule['start_datetime'])) : ''; ?>"
                                    required>
                                <input type="time" name="start_time"
                                    value="<?php echo $schedule ? date('H:i', strtotime($schedule['start_datetime'])) : ''; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>End Date & Time</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <input type="date" name="end_date"
                                    value="<?php echo $schedule ? date('Y-m-d', strtotime($schedule['end_datetime'])) : ''; ?>"
                                    required>
                                <input type="time" name="end_time"
                                    value="<?php echo $schedule ? date('H:i', strtotime($schedule['end_datetime'])) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button type="submit" name="set_schedule" class="modern-btn modern-btn-primary">Set Voting
                            Window</button>
                        <button type="submit" name="reset_schedule" class="modern-btn"
                            style="background: rgba(255,255,255,0.05); color:white; border:1px solid var(--border);">Reset
                            Schedule</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('admin'); ?>
</body>

</html>