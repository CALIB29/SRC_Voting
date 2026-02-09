<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// ------ ELECTION SELECTION LOGIC ------
$election_id_to_show = null;
$election_name = "No Election Selected";
$is_viewing_archive = false;
$page_title = "Live Results Analytics";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $election_id_to_show = $_GET['id'];
    $is_viewing_archive = true;
    $stmt = $pdo->prepare("SELECT title FROM vot_elections WHERE id = ?");
    $stmt->execute([$election_id_to_show]);
    $election_name_from_db = $stmt->fetchColumn();
    if ($election_name_from_db) {
        $election_name = $election_name_from_db;
        $page_title = "Archive: " . $election_name;
    }
} else {
    $stmt = $pdo->prepare("SELECT id, title FROM vot_elections WHERE is_active = 1");
    $stmt->execute();
    $active_election = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($active_election) {
        $election_id_to_show = $active_election['id'];
        $election_name = $active_election['title'];
    }
}

// Data fetching
$candidates = [];
$positions_in_db = [];
$total_votes = 0;

if ($election_id_to_show) {
    $stmt = $pdo->prepare("
        SELECT c.*, 
        (SELECT MIN(vote_id) FROM vot_votes WHERE candidate_id = c.id) as first_vote_id
        FROM vot_candidates c
        WHERE c.election_id = :id 
        ORDER BY c.position, c.votes DESC, first_vote_id ASC
    ");
    $stmt->execute(['id' => $election_id_to_show]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($candidates) {
        $stmt = $pdo->prepare("SELECT DISTINCT position FROM vot_candidates WHERE election_id = :id");
        $stmt->execute(['id' => $election_id_to_show]);
        $positions_in_db = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM vot_votes WHERE election_id = :id");
    $stmt->execute(['id' => $election_id_to_show]);
    $total_votes = $stmt->fetchColumn();
}

$fixed_order = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor', 'PRO'];
$final_order = $fixed_order;
foreach ($positions_in_db as $pos) {
    if (!in_array($pos, $final_order)) {
        $final_order[] = $pos;
    }
}

$results_by_position = [];
foreach ($final_order as $pos) {
    foreach ($candidates as $candidate) {
        if ($candidate['position'] === $pos) {
            $results_by_position[$pos][] = $candidate;
        }
    }
}

$total_users = $pdo->query("SELECT COUNT(*) FROM students WHERE is_approved = 1")->fetchColumn();
$voter_turnout = ($total_users > 0 && $total_votes > 0) ? round(($total_votes / $total_users) * 100, 2) : 0;

$voting_schedule = null;
$election_end_time = null;
if ($election_id_to_show && !$is_viewing_archive) {
    $stmt = $pdo->query("SELECT * FROM vot_voting_schedule LIMIT 1");
    $voting_schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($voting_schedule && isset($voting_schedule['end_datetime'])) {
        $election_end_time = $voting_schedule['end_datetime'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_election']) && !$is_viewing_archive) {
    $stmt = $pdo->prepare("UPDATE vot_elections SET is_active = 0, end_datetime = NOW() WHERE id = ?");
    $stmt->execute([$election_id_to_show]);
    header("Location: results.php?id=" . $election_id_to_show);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results Analytics | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">
    <style>
        .winners-scroll {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            padding: 1rem 0;
            scrollbar-width: none;
        }

        .winner-card {
            flex: 0 0 280px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 2rem;
            text-align: center;
            border: 2px solid transparent;
            transition: var(--transition);
        }

        .winner-card.top {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
        }

        .winner-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 4px solid var(--primary);
        }

        .progress-bar-container {
            background: rgba(255, 255, 255, 0.05);
            height: 10px;
            border-radius: 99px;
            margin-top: 1rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }

        @media (max-width: 768px) {
            .winner-card {
                flex: 0 0 85%;
            }
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/mobile_base.css">
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('Results'); ?>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../pic/srclogo.png" alt="Logo">
                <span>SRC Admin</span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item"><a href="dashboard.php"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
                </div>
                <div class="nav-item"><a href="manage_users.php"><i class="fas fa-users"></i><span>Voters</span></a>
                </div>
                <div class="nav-item"><a href="manage_candidates.php"><i
                            class="fas fa-user-tie"></i><span>Candidates</span></a></div>
                <div class="nav-item active"><a href="results.php"><i class="fas fa-poll-h"></i><span>Poll
                            Results</span></a></div>
                <div class="nav-item"><a href="archive.php"><i class="fas fa-archive"></i><span>Archive</span></a></div>
                <div class="nav-item" style="margin-top:auto;"><a href="logout.php" style="color:var(--danger)"><i
                            class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div class="welcome-text">
                    <h1><?php echo $page_title; ?></h1>
                    <p><?php echo $election_name; ?> - Statistical Breakdown</p>
                </div>
                <?php if ($election_id_to_show && !$is_viewing_archive): ?>
                    <form method="POST"
                        onsubmit="return confirm('WARNING: This will permanently stop the live election. Proceed?')">
                        <button type="submit" name="end_election" class="modern-btn modern-btn-danger">
                            <i class="fas fa-power-off"></i> Finalize Election
                        </button>
                    </form>
                <?php endif; ?>
            </header>

            <div class="stats-grid">
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background:rgba(99,102,241,0.1); color:var(--primary);"><i
                            class="fas fa-vote-yea"></i></div>
                    <div class="stat-label">Total Votes</div>
                    <div class="stat-value"><?php echo number_format($total_votes); ?></div>
                </div>
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background:rgba(16,185,129,0.1); color:var(--success);"><i
                            class="fas fa-chart-line"></i></div>
                    <div class="stat-label">Voter Turnout</div>
                    <div class="stat-value"><?php echo $voter_turnout; ?>%</div>
                </div>
                <div class="modern-card stat-card">
                    <div class="stat-icon" style="background:rgba(245,158,11,0.1); color:var(--warning);"><i
                            class="fas fa-hourglass-half"></i></div>
                    <div class="stat-label">Status</div>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <?php echo $is_viewing_archive ? 'Archived' : 'Live Counting'; ?>
                    </div>
                </div>
            </div>

            <div class="modern-card">
                <h3 style="margin-bottom: 2rem;">Current Leaders</h3>
                <div class="winners-scroll">
                    <?php if (empty($results_by_position)): ?>
                        <div style="padding: 3rem; text-align: center; width: 100%; color: var(--text-muted);">Waiting for
                            first votes...</div>
                    <?php else: ?>
                        <?php foreach ($results_by_position as $pos => $candidates_list):
                            $winner = $candidates_list[0]; ?>
                            <div class="winner-card top">
                                <div
                                    style="font-size: 0.75rem; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 1rem;">
                                    <?php echo $pos; ?>
                                </div>
                                <img src="<?php echo fixCandidatePhotoPath($winner['photo_path'], '../../'); ?>"
                                    class="winner-img" onerror="this.src='../../logo/srclogo.png'">
                                <div style="font-weight: 800; font-size: 1.15rem; margin-bottom: 0.25rem;">
                                    <?php echo $winner['first_name'] . ' ' . $winner['last_name']; ?>
                                </div>
                                <div style="color:var(--success); font-weight: 900; font-size: 1.25rem;"><i
                                        class="fas fa-poll"></i> <?php echo $winner['votes']; ?></div>
                                <div class="progress-bar-container">
                                    <div class="progress-fill"
                                        style="width: <?php echo ($total_votes > 0) ? ($winner['votes'] / $total_votes * 100) : 0; ?>%">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php foreach ($results_by_position as $pos => $candidates_list): ?>
                <div class="modern-card">
                    <h3 style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <span><?php echo $pos; ?> Race</span>
                        <span class="status-badge primary"><?php echo count($candidates_list); ?> Candidates</span>
                    </h3>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Section</th>
                                    <th style="text-align: center;">Votes</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates_list as $c):
                                    $pct = ($total_votes > 0) ? round(($c['votes'] / $total_votes) * 100, 1) : 0; ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <img src="../<?php echo $c['photo_path']; ?>"
                                                    style="width:36px; height:36px; border-radius:8px; object-fit:cover;"
                                                    onerror="this.src='../pic/default-avatar.png'">
                                                <span
                                                    style="font-weight:700;"><?php echo $c['first_name'] . ' ' . $c['last_name']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $c['section']; ?></td>
                                        <td style="text-align: center; font-weight: 800; color: var(--success);">
                                            <?php echo $c['votes']; ?>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap: 1rem;">
                                                <div
                                                    style="flex:1; background:rgba(255,255,255,0.05); height:6px; border-radius:3px;">
                                                    <div
                                                        style="width:<?php echo $pct; ?>%; height:100%; background:var(--primary); border-radius:3px;">
                                                    </div>
                                                </div>
                                                <span style="font-size:0.8rem; font-weight:700;"><?php echo $pct; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>
    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('admin'); ?>
</body>

</html>