<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Make sure user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Logged-in admin's department
$department_id = $_SESSION['department_id'];
$department_name = $_SESSION['department_name'];

// -------------------------------
// DASHBOARD STATISTICS
// -------------------------------
$total_users = $pdo->query("SELECT COUNT(student_id) FROM students")->fetchColumn();
$approved_users = $pdo->query("SELECT COUNT(student_id) FROM students WHERE is_approved = 1")->fetchColumn();
$voted_users = $pdo->query("SELECT COUNT(student_id) FROM students WHERE has_voted = 1")->fetchColumn();
$total_candidates = $pdo->query("SELECT COUNT(id) FROM vot_candidates")->fetchColumn();

// -------------------------------
// SCHEDULE SYSTEM
// -------------------------------
function ensureScheduleDatetimeSchema($pdo)
{
    try {
        foreach (['time_start', 'time_end'] as $col) {
            $column = $pdo->query("SHOW COLUMNS FROM vot_schedules LIKE '$col'")->fetch(PDO::FETCH_ASSOC);
            if ($column && stripos($column['Type'], 'datetime') === false) {
                $pdo->exec("ALTER TABLE vot_schedules MODIFY $col DATETIME NOT NULL");
            }
        }
    } catch (PDOException $e) {
    }
}

function ensureScheduleDefaults($pdo)
{
    $labId = $pdo->query("SELECT lab_id FROM facility ORDER BY lab_id ASC LIMIT 1")->fetchColumn();
    if (!$labId) {
        $stmt = $pdo->prepare("INSERT INTO facility (lab_name, location) VALUES (?, ?)");
        $stmt->execute(['Default Voting Lab', '']);
        $labId = (int) $pdo->lastInsertId();
    }
    $subjectId = $pdo->query("SELECT subject_id FROM subject ORDER BY subject_id ASC LIMIT 1")->fetchColumn();
    if (!$subjectId) {
        $stmt = $pdo->prepare("INSERT INTO subject (subject_code, subject_name, units) VALUES (?, ?, ?)");
        $stmt->execute(['VOTE-SCHED', 'Voting Schedule Window', 0]);
        $subjectId = (int) $pdo->lastInsertId();
    }
    $employeeId = $pdo->query("SELECT employee_id FROM vot_employees ORDER BY employee_id ASC LIMIT 1")->fetchColumn();
    if (!$employeeId) {
        $stmt = $pdo->prepare("INSERT INTO vot_employees (firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Schedule', 'Manager', 'vot_schedules.bot@src.local', 'temp123', 'Dean']);
        $employeeId = (int) $pdo->lastInsertId();
    }
    return [$labId, $subjectId, $employeeId];
}

ensureScheduleDatetimeSchema($pdo);
list($default_lab, $default_subject, $default_employee) = ensureScheduleDefaults($pdo);

$success = false;
$schedule_message = '';
$keep_schedule_open = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_schedule'])) {
        $keep_schedule_open = true;
        $start_datetime = $_POST['start_date'] . ' ' . $_POST['start_time'] . ':00';
        $end_datetime = $_POST['end_date'] . ' ' . $_POST['end_time'] . ':00';

        if (strtotime($start_datetime) >= strtotime($end_datetime)) {
            $schedule_message = "End date/time must be after start date/time.";
        } else {
            try {
                $existing_id = $pdo->query("SELECT schedule_id FROM vot_schedules LIMIT 1")->fetchColumn();
                if ($existing_id) {
                    $stmt = $pdo->prepare("UPDATE vot_schedules SET lab_id=?, subject_id=?, employee_id=?, time_start=?, time_end=?, updated_at=NOW() WHERE schedule_id=?");
                    $success = $stmt->execute([$default_lab, $default_subject, $default_employee, $start_datetime, $end_datetime, $existing_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO vot_schedules (lab_id, subject_id, employee_id, time_start, time_end, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    $success = $stmt->execute([$default_lab, $default_subject, $default_employee, $start_datetime, $end_datetime]);
                }
                $schedule_message = $success ? "Schedule updated!" : "Update failed.";
            } catch (PDOException $e) {
                $schedule_message = "Error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['reset_schedule'])) {
        $keep_schedule_open = true;
        $pdo->exec("DELETE FROM vot_schedules");
        $schedule_message = "Schedule reset.";
    }
}

$schedule = $pdo->query("SELECT * FROM vot_schedules LIMIT 1")->fetch(PDO::FETCH_ASSOC);
require_once '../includes/get_winners.php';
$winners_by_dept = getWinnersForAllDepartments($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Dashboard | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/modern_admin.css">
    <style>
        .winner-spotlight {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.2);
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
</head>

<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../pic/srclogo.png" alt="Logo">
                <span>SRC Master</span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item active"><a href="dashboard.php"><i
                            class="fas fa-home"></i><span>Dashboard</span></a></div>
                <div class="nav-item"><a href="manage_users.php"><i
                            class="fas fa-users"></i><span>Subscribers</span></a></div>
                <div class="nav-item"><a href="manage_candidates.php"><i
                            class="fas fa-user-tie"></i><span>Candidates</span></a></div>
                <div class="nav-item"><a href="results.php"><i class="fas fa-poll"></i><span>Results</span></a></div>
                <div class="nav-item" style="margin-top: auto;"><a href="logout.php" style="color:var(--danger)"><i
                            class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div class="welcome-text">
                    <h1>Master Dashboard</h1>
                    <p>Welcome back, SRC Administrator</p>
                </div>
                <div class="user-badge"
                    style="display:flex; align-items:center; gap:1rem; background:rgba(255,255,255,0.05); padding:0.5rem 1rem; border-radius:99px; border:1px solid var(--border);">
                    <i class="fas fa-user-shield" style="color:var(--primary)"></i>
                    <span style="font-weight:700; font-size: 0.9rem;"><?php echo htmlspecialchars($department_name); ?>
                        Panel</span>
                </div>
            </header>

            <div class="stats-grid">
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background:rgba(99,102,241,0.1); color:var(--primary);"><i
                            class="fas fa-users"></i></div>
                    <div class="stat-label">Total Voters</div>
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

            <div class="modern-card winner-spotlight">
                <div class="spotlight-header">
                    <div>
                        <h3 style="font-size: 1.5rem; font-weight: 850;"><i class="fas fa-trophy"
                                style="color:var(--warning)"></i> Election Winners Spotlight</h3>
                        <p style="color:var(--text-muted)">Real-time leaders across all departments</p>
                    </div>
                </div>
                <div class="winners-scroll">
                    <?php if (empty($winners_by_dept)): ?>
                        <div style="padding: 2rem; color: var(--text-muted); text-align: center; width: 100%;">No election
                            results available yet.</div>
                    <?php else: ?>
                        <?php foreach ($winners_by_dept as $dept => $winners): ?>
                            <?php foreach ($winners as $pos => $w): ?>
                                <div class="winner-spotlight-card"
                                    style="flex: 0 0 240px; background: var(--bg-card); border-radius: 20px; padding: 1.5rem; border: 1px solid var(--border); position: relative; box-shadow: 0 10px 20px rgba(0,0,0,0.1); text-align: center;">
                                    <div class="winner-badge"
                                        style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; padding: 6px 16px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4); white-space: nowrap; z-index: 10; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-crown" style="font-size: 0.8rem; color: #FFD700;"></i>
                                        <?php echo htmlspecialchars($pos); ?>
                                    </div>
                                    <div style="margin-top: 0.5rem;">
                                        <div style="position: relative; display: inline-block; margin-bottom: 1rem;">
                                            <img src="<?php echo fixCandidatePhotoPath($w['photo_path'], '../'); ?>"
                                                onerror="this.src='../logo/srclogo.png'"
                                                style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary);">
                                            <div
                                                style="position: absolute; bottom: 0; right: 0; background: var(--warning); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid var(--bg-card);">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                        </div>
                                        <div
                                            style="font-size: 0.7rem; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($dept); ?>
                                        </div>
                                        <h4
                                            style="font-weight: 700; color: var(--text-main); min-height: 2.4em; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; font-size: 1rem; line-height: 1.2;">
                                            <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name']); ?>
                                        </h4>
                                        <div
                                            style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--success); font-weight: 700;">
                                            <i class="fas fa-poll"></i>
                                            <span><?php echo number_format($w['votes']); ?> Votes</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modern-card">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <div
                        style="padding: 0.5rem; background: rgba(245,158,11,0.1); border-radius: 8px; color: var(--warning);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 style="font-size: 1.25rem; font-weight: 700;">Global Voting Schedule</h3>
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
                            <label>Voting Begins</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <input type="date" name="start_date"
                                    value="<?php echo $schedule ? date('Y-m-d', strtotime($schedule['time_start'])) : ''; ?>"
                                    required>
                                <input type="time" name="start_time"
                                    value="<?php echo $schedule ? date('H:i', strtotime($schedule['time_start'])) : ''; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Voting Ends</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <input type="date" name="end_date"
                                    value="<?php echo $schedule ? date('Y-m-d', strtotime($schedule['time_end'])) : ''; ?>"
                                    required>
                                <input type="time" name="end_time"
                                    value="<?php echo $schedule ? date('H:i', strtotime($schedule['time_end'])) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button type="submit" name="set_schedule" class="modern-btn modern-btn-primary">
                            <i class="fas fa-save" style="margin-right:0.5rem"></i> Update Schedule Window
                        </button>
                        <button type="submit" name="reset_schedule" class="modern-btn"
                            style="background: rgba(255,255,255,0.05); color:white; border:1px solid var(--border);">
                            <i class="fas fa-redo" style="margin-right:0.5rem"></i> Clear Schedule
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>