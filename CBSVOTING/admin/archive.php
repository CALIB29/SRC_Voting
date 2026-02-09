<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch all archived elections (inactive)
try {
    $stmt = $pdo->prepare("SELECT * FROM vot_elections WHERE is_active = 0 ORDER BY created_at DESC");
    $stmt->execute();
    $archived_elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $archived_elections = [];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">
    <style>
        :root {
            --primary: #3B82F6;
            --primary-glow: rgba(59, 130, 246, 0.15);
            --secondary: #06B6D4;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg-main: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --radius-lg: 20px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .archive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .election-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .election-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .election-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
            opacity: 0;
            transition: var(--transition);
        }

        .election-card:hover::before {
            opacity: 1;
        }

        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .election-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-glow);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .election-status {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            background: var(--border);
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .election-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            line-height: 1.4;
        }

        .election-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: auto;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .meta-row i {
            width: 16px;
            color: var(--primary);
        }

        .action-btn {
            margin-top: 0.5rem;
            width: 100%;
            padding: 0.875rem;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            background: #2563EB;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-card);
            border: 2px dashed var(--border);
            border-radius: var(--radius-lg);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-muted);
            font-weight: 500;
        }
    </style>

    <link rel="stylesheet" href="../../assets/css/mobile_base.css"></head>

<body>
<?php if (function_exists('renderMobileTopBar')) renderMobileTopBar('Voting System'); ?>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../pic/srclogo.png" alt="Logo">
                <span>SRC Admin</span>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php">
                        <i class="fas fa-th-large"></i>
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
                <div class="nav-item active">
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
                    <h1>Election Archives</h1>
                    <p>View and analyze past election results</p>
                </div>
                <div class="header-actions">
                    <div class="current-time" id="digitalClock">--:--:--</div>
                </div>
            </header>

            <div class="content-body">
                <div class="archive-grid">
                    <?php if (empty($archived_elections)): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>No archived elections found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($archived_elections as $election): ?>
                            <div class="election-card">
                                <div class="election-header">
                                    <div class="election-icon">
                                        <i class="fas fa-box-archive"></i>
                                    </div>
                                    <span class="election-status">Archived</span>
                                </div>

                                <h3 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h3>

                                <div class="election-meta">
                                    <div class="meta-row">
                                        <i class="far fa-calendar-alt"></i>
                                        <span>Started: <?php echo date("F j, Y", strtotime($election['created_at'])); ?></span>
                                    </div>
                                    <div class="meta-row">
                                        <i class="far fa-clock"></i>
                                        <span>Status: Completed</span>
                                    </div>
                                </div>

                                <a href="results.php?id=<?php echo $election['id']; ?>" class="action-btn">
                                    <i class="fas fa-chart-line"></i>
                                    View Full Analytics
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('digitalClock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

<?php if (function_exists('renderMobileBottomNav')) renderMobileBottomNav('admin'); ?>
</body>

</html>