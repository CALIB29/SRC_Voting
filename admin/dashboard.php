<?php
session_start();
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
// DASHBOARD STATISTICS (FOCUSED ON 4 CATEGORIES)
// -------------------------------

// Total students
$total_users = $pdo->query("SELECT COUNT(student_id) FROM students")->fetchColumn();

// Approved students
$approved_users = $pdo->query("SELECT COUNT(student_id) FROM students WHERE is_approved = 1")->fetchColumn();

// Voted students
$voted_users = $pdo->query("SELECT COUNT(student_id) FROM students WHERE has_voted = 1")->fetchColumn();

// Total candidates
$total_candidates = $pdo->query("SELECT COUNT(id) FROM candidates")->fetchColumn();

// -------------------------------
// SCHEDULE SYSTEM (UNCHANGED)
// -------------------------------

function ensureScheduleDatetimeSchema($pdo)
{
    try {
        $column = $pdo->query("SHOW COLUMNS FROM schedule LIKE 'time_start'")->fetch(PDO::FETCH_ASSOC);
        if ($column && stripos($column['Type'], 'datetime') === false) {
            $pdo->exec("ALTER TABLE schedule MODIFY time_start DATETIME NOT NULL");
        }

        $column = $pdo->query("SHOW COLUMNS FROM schedule LIKE 'time_end'")->fetch(PDO::FETCH_ASSOC);
        if ($column && stripos($column['Type'], 'datetime') === false) {
            $pdo->exec("ALTER TABLE schedule MODIFY time_end DATETIME NOT NULL");
        }
    } catch (PDOException $e) {
    }
}

function ensureScheduleDefaults($pdo)
{
    // Facility
    $labId = $pdo->query("SELECT lab_id FROM facility ORDER BY lab_id ASC LIMIT 1")->fetchColumn();
    if (!$labId) {
        $stmt = $pdo->prepare("INSERT INTO facility (lab_name, location) VALUES (?, ?)");
        $stmt->execute(['Default Voting Lab', '']);
        $labId = (int) $pdo->lastInsertId();
    }

    // Subject
    $subjectId = $pdo->query("SELECT subject_id FROM subject ORDER BY subject_id ASC LIMIT 1")->fetchColumn();
    if (!$subjectId) {
        $stmt = $pdo->prepare("INSERT INTO subject (subject_code, subject_name, units) VALUES (?, ?, ?)");
        $stmt->execute(['VOTE-SCHED', 'Voting Schedule Window', 0]);
        $subjectId = (int) $pdo->lastInsertId();
    }

    // Employee
    $employeeId = $pdo->query("SELECT employee_id FROM employees ORDER BY employee_id ASC LIMIT 1")->fetchColumn();
    if (!$employeeId) {
        $stmt = $pdo->prepare("INSERT INTO employees (firstname, lastname, email, password, role, profile_pic) 
                               VALUES (?, ?, ?, ?, ?, NULL)");
        $stmt->execute(['Schedule', 'Manager', 'schedule.bot@src.local', 'temp123', 'Dean']);
        $employeeId = (int) $pdo->lastInsertId();
    }

    return [$labId, $subjectId, $employeeId];
}

ensureScheduleDatetimeSchema($pdo);
list($default_lab, $default_subject, $default_employee) = ensureScheduleDefaults($pdo);

$success = false;
$schedule_message = '';
$keep_schedule_open = false;

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
                    $existing_schedule_id = $pdo->query("SELECT schedule_id FROM schedule ORDER BY schedule_id ASC LIMIT 1")->fetchColumn();

                    if ($existing_schedule_id) {
                        $stmt = $pdo->prepare("
                            UPDATE schedule 
                            SET lab_id = ?, subject_id = ?, employee_id = ?, time_start = ?, time_end = ?, updated_at = NOW()
                            WHERE schedule_id = ?
                        ");
                        $success = $stmt->execute([$default_lab, $default_subject, $default_employee, $start_datetime, $end_datetime, $existing_schedule_id]);
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO schedule (lab_id, subject_id, employee_id, time_start, time_end, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        $success = $stmt->execute([$default_lab, $default_subject, $default_employee, $start_datetime, $end_datetime]);
                    }

                    $schedule_message = $success ? "Voting schedule updated successfully!" : "Error updating schedule.";
                } catch (PDOException $e) {
                    $schedule_message = "Database error: " . $e->getMessage();
                }
            }
        }

    } elseif (isset($_POST['reset_schedule'])) {
        $keep_schedule_open = true;
        try {
            $stmt = $pdo->prepare("DELETE FROM schedule");
            $success = $stmt->execute();
            $schedule_message = $success ? "Voting schedule reset!" : "Error resetting schedule.";
        } catch (PDOException $e) {
            $schedule_message = "Database error: " . $e->getMessage();
        }
    }
}

// Get current schedule
$schedule = $pdo->query("SELECT * FROM schedule ORDER BY schedule_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Fetch latest election winners for dashboard display
require_once '../includes/get_winners.php';
$winnersData = getLatestElectionWinners($pdo);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>

<body>
    <div class="admin-dashboard-container">
        <!-- Top Bar -->
        <div class="admin-top-bar">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <span>Santa Rita College</span>
            </div>
            <div class="admin-info">
                <span><?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin'; ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <div class="admin-main-content">
            <!-- Sidebar -->
            <div class="admin-sidebar">
                <div class="sidebar-logo">
                    <img src="../pic/srclogo.png" alt="Voting System Logo">
                    <span>Voting System</span>
                </div>
                <ul>
                    <li class="active">
                        <a href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="approve_students.php">
                            <i class="fas fa-user-check"></i>
                            <span>Approve Students</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php">
                            <i class="fas fa-users-cog"></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_candidates.php">
                            <i class="fas fa-user-tie"></i>
                            <span>Manage Candidates</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_history.php">
                            <i class="fas fa-history"></i>
                            <span>Manage History</span>
                        </a>
                    </li>
                    <li>
                        <a href="results.php">
                            <i class="fas fa-chart-pie"></i>
                            <span>Results Analytics</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="admin-content-area">
                <div class="content-header">
                    <h2>Dashboard Overview</h2>
                </div>

                <?php if ($winnersData && !empty($winnersData['winners'])): ?>
                    <!-- Winners Spotlight Banner on Dashboard -->
                    <div class="winners-spotlight-banner"
                        style="padding: 2rem; background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(0, 91, 170, 0.05)); border: 2px solid #4F46E5; border-radius: 12px; margin-bottom: 2rem; position: relative; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        <div
                            style="position: absolute; top: -20px; right: -20px; font-size: 8rem; color: rgba(79, 70, 229, 0.05); transform: rotate(-15deg);">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; position: relative; z-index: 1;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div
                                    style="width: 50px; height: 50px; background: #f59e0b; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-award" style="font-size: 1.5rem;"></i>
                                </div>
                                <div>
                                    <h2
                                        style="font-size: 1.5rem; font-weight: 800; color: #1e293b; line-height: 1; margin: 0;">
                                        Election Winners</h2>
                                    <p style="color: #64748b; font-weight: 500; font-size: 0.9rem; margin: 5px 0 0 0;">
                                        <?php echo htmlspecialchars($winnersData['election']['title']); ?> - Official Final
                                        Results
                                    </p>
                                </div>
                            </div>
                            <a href="results.php?id=<?php echo $winnersData['election']['id']; ?>" class="btn btn-schedule"
                                style="padding: 0.6rem 1.2rem; font-size: 0.85rem; text-decoration: none;">
                                View Detailed Results
                            </a>
                        </div>

                        <div class="winners-scroll-container"
                            style="display: flex; gap: 1.5rem; overflow-x: auto; padding: 1.5rem 0 1rem 0; scrollbar-width: thin; -ms-overflow-style: none;">
                            <style>
                                .winners-scroll-container::-webkit-scrollbar {
                                    display: none;
                                }
                            </style>
                            <?php foreach ($winnersData['winners'] as $winner): ?>
                                <div class="winner-spotlight-card"
                                    style="flex: 0 0 240px; background: white; border-radius: 20px; padding: 1.5rem; border: 1px solid rgba(0,0,0,0.05); position: relative; box-shadow: 0 10px 20px rgba(0,0,0,0.05);">
                                    <div class="winner-badge"
                                        style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(to right, #4F46E5, #7c3aed); color: white; padding: 6px 16px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4); white-space: nowrap; z-index: 10; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-crown" style="font-size: 0.8rem; color: #FFD700;"></i>
                                        <?php echo htmlspecialchars($winner['position']); ?>
                                    </div>
                                    <div style="text-align: center; margin-top: 0.5rem;">
                                        <div style="position: relative; display: inline-block;">
                                            <img src="../<?php
                                            $path = $winner['photo_path'] ?: 'pic/default-avatar.png';
                                            echo htmlspecialchars(preg_replace('/^(\.\.\/|\.\/)+/', '', $path));
                                            ?>" onerror="this.src='../logo/srclogo.png'"
                                                style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #4F46E5;">
                                            <div
                                                style="position: absolute; bottom: 0; right: 0; background: #f59e0b; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white;">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                        </div>
                                        <h4
                                            style="margin-top: 1rem; font-weight: 700; color: #1e293b; min-height: 2.4em; display: flex; align-items: center; justify-content: center; margin-bottom: 0;">
                                            <?php echo htmlspecialchars($winner['first_name'] . ' ' . $winner['last_name']); ?>
                                        </h4>
                                        <div
                                            style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: #4F46E5; font-weight: 700;">
                                            <i class="fas fa-vote-yea"></i>
                                            <span><?php echo number_format($winner['votes']); ?> Votes</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <p><?php echo $total_users; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Approved Users</h3>
                        <p><?php echo $approved_users; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Candidates</h3>
                        <p><?php echo $total_candidates; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Voted Users</h3>
                        <p><?php echo $voted_users; ?></p>
                    </div>
                </div>

                <!-- Schedule and Charts -->
                <div class="charts-container">
                    <!-- Voting Schedule Card -->
                    <div class="chart-card">
                        <h3>Voting Schedule</h3>
                        <form method="post">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                        value="<?php echo isset($schedule) ? substr($schedule['time_start'], 0, 10) : ''; ?>"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label for="start_time">Start Time</label>
                                    <input type="time" id="start_time" name="start_time" class="form-control"
                                        value="<?php echo isset($schedule) ? substr($schedule['time_start'], 11, 5) : ''; ?>"
                                        required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control"
                                        value="<?php echo isset($schedule) ? substr($schedule['time_end'], 0, 10) : ''; ?>"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label for="end_time">End Time</label>
                                    <input type="time" id="end_time" name="end_time" class="form-control"
                                        value="<?php echo isset($schedule) ? substr($schedule['time_end'], 11, 5) : ''; ?>"
                                        required>
                                </div>
                            </div>
                            <button type="submit" name="set_schedule" class="btn btn-schedule">Set Schedule</button>
                            <button type="submit" name="reset_schedule" class="btn btn-danger"
                                onclick="return confirm('Are you sure?');">Reset</button>
                        </form>
                    </div>

                    <!-- User Statistics Pie Chart -->
                    <div class="chart-card">
                        <h3>User Statistics</h3>
                        <canvas id="userPieChart" style="max-height:280px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Check if schedule container should be open on page load and set button text accordingly
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('scheduleContainer');
            const button = document.querySelector('.schedule-toggle-btn');

            if (container.classList.contains('show')) {
                button.innerHTML = '<i class="fas fa-calendar-minus"></i> Hide Voting Schedule';
            }
        });

        // Toggle Schedule Container
        function toggleSchedule() {
            const container = document.getElementById('scheduleContainer');
            const button = document.querySelector('.schedule-toggle-btn');

            if (container.classList.contains('show')) {
                container.classList.remove('show');
                button.innerHTML = '<i class="fas fa-calendar-plus"></i> Manage Voting Schedule';
            } else {
                container.classList.add('show');
                button.innerHTML = '<i class="fas fa-calendar-minus"></i> Hide Voting Schedule';
            }
        }

        // Pie Chart - Focused on Total Users, Approved Users, Candidates, and Voted Users
        const totalUsers = <?php echo $total_users; ?>;
        const approvedUsers = <?php echo $approved_users; ?>;
        const totalCandidates = <?php echo $total_candidates; ?>;
        const votedUsers = <?php echo $voted_users; ?>;

        const ctxPie = document.getElementById('userPieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Total Users', 'Approved Users', 'Candidates', 'Voted Users'],
                datasets: [{
                    data: [totalUsers, approvedUsers, totalCandidates, votedUsers],
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#2563eb'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        });

        // Bar Chart - Voting Stats
        const ctxBar = document.getElementById('votingBarChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Total Users', 'Approved Users', 'Candidates', 'Voted Users'],
                datasets: [{
                    label: 'Count',
                    data: [totalUsers, approvedUsers, totalCandidates, votedUsers],
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#2563eb'],
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Form validation for schedule dates
        document.querySelector('form').addEventListener('submit', function (e) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;

            // Create full datetime for comparison
            const startDateTime = new Date(startDate + 'T' + startTime);
            const endDateTime = new Date(endDate + 'T' + endTime);

            if (startDateTime >= endDateTime) {
                e.preventDefault();
                alert('End date/time must be after start date/time.');
                return false;
            }
        });
    </script>
</body>

</html>