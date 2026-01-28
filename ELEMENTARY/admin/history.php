<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Fetch history records grouped by position
try {
    $stmt = $pdo->query("SELECT * FROM election_history ORDER BY year DESC, id ASC");
    $all_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_history = [];
}

// Grouping by position for the display
$grouped_history = [];
foreach ($all_history as $record) {
    if (!isset($grouped_history[$record['position']])) {
        $grouped_history[$record['position']] = [];
    }
    $grouped_history[$record['position']][] = $record;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election History | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">
    <style>
        .history-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .history-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }

        .history-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }

        .winner-photo {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-bottom: 3px solid var(--primary);
        }

        .winner-info {
            padding: 1.5rem;
            text-align: center;
        }

        .winner-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .winner-position {
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }

        .winner-meta {
            display: flex;
            justify-content: center;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .position-section {
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            border-left: 5px solid var(--primary);
            padding-left: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../pic/srclogo.png" alt="Logo">
                <span>SRC Admin</span>
            </div>

            <nav class="nav-menu">
                <div class="nav-item">
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
                <div class="nav-item active">
                    <a href="manage_history.php">
                        <i class="fas fa-history"></i>
                        <span>History</span>
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
                    <h1>Election Gallery</h1>
                    <p>Archive of past student leaders and their contributions</p>
                </div>
            </header>

            <?php if (empty($grouped_history)): ?>
                <div class="modern-card" style="text-align: center; padding: 5rem;">
                    <i class="fas fa-folder-open"
                        style="font-size: 4rem; color: var(--border); margin-bottom: 1.5rem; display: block;"></i>
                    <h2 style="color: var(--text-muted);">No history records yet.</h2>
                    <p style="color: var(--text-muted); margin-top: 0.5rem;">Records added in Manage History will appear
                        here.</p>
                    <a href="manage_history.php" class="btn btn-schedule"
                        style="margin-top: 1.5rem; width: auto; display: inline-block;">
                        Add First Record
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_history as $position => $records): ?>
                    <div class="position-section">
                        <h2 class="section-title"><?php echo htmlspecialchars($position); ?>s</h2>
                        <div class="history-grid">
                            <?php foreach ($records as $record): ?>
                                <div class="history-card">
                                    <img src="<?php echo htmlspecialchars($record['photo_path']); ?>" alt="Winner"
                                        class="winner-photo" onerror="this.src='../../pic/default-avatar.png'">
                                    <div class="winner-info">
                                        <div class="winner-name"><?php echo htmlspecialchars($record['fullname']); ?></div>
                                        <div class="winner-position"><?php echo htmlspecialchars($record['position']); ?></div>
                                        <div class="winner-meta">
                                            <div class="meta-item">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo htmlspecialchars($record['year']); ?>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-graduation-cap"></i>
                                                <?php echo htmlspecialchars($record['year_section']); ?>
                                            </div>
                                        </div>
                                        <div
                                            style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; text-align: left;">
                                            <strong>Achievements:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($record['platforms'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>