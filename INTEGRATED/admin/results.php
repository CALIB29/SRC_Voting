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
        $page_title = "Retrospective: " . $election_name;
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
    // Logic updated for tie-breaker: first to receive a vote wins
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

// Fetch voting schedule for countdown
$voting_schedule = null;
$election_end_time = null;
if ($election_id_to_show && !$is_viewing_archive) {
    $stmt = $pdo->query("SELECT * FROM vot_voting_schedule LIMIT 1");
    $voting_schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($voting_schedule && isset($voting_schedule['end_datetime'])) {
        $election_end_time = $voting_schedule['end_datetime'];
    }
}

// Handle End Election action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_election']) && !$is_viewing_archive) {
    try {
        // Mark election as inactive
        $stmt = $pdo->prepare("UPDATE vot_elections SET is_active = 0, end_datetime = NOW() WHERE id = ?");
        $stmt->execute([$election_id_to_show]);

        // Clear voting schedule
        $pdo->query("DELETE FROM vot_voting_schedule");

        // Redirect to show winners
        header("Location: results.php?id=" . $election_id_to_show);
        exit;
    } catch (PDOException $e) {
        error_log("Error ending election: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        .live-indicator {
            display: inline-flex;
            align-items: center;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 0.5rem 1rem;
            border-radius: 99px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .candidate-result-card {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 60px 80px 1fr 180px 120px;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }

        .candidate-result-card.rank-1 {
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.05), transparent);
            border-color: var(--primary);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.1);
        }

        .result-rank {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-secondary);
            text-align: center;
        }

        .rank-1 .result-rank {
            color: var(--warning);
            font-size: 2.25rem;
            text-shadow: 0 0 15px rgba(245, 158, 11, 0.3);
        }

        .result-photo {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid var(--border);
        }

        .rank-1 .result-photo {
            border-color: var(--warning);
        }

        .winner-crown {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--warning);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.4);
            border: 2px solid white;
            z-index: 10;
        }

        .vote-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary);
            display: block;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .vote-number.pop {
            transform: scale(1.3);
            color: var(--success);
        }

        .result-info h4 {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            transition: all 0.3s ease;
        }

        .rank-1 .result-info h4 {
            font-size: 1.75rem;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .progress-container {
            flex: 1;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .progress-bar {
            height: 8px;
            background: var(--bg-secondary);
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 99px;
            width: 0%;
            transition: width 1s ease;
        }

        /* Countdown Timer */
        .countdown-timer {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            padding: 0.5rem 1rem;
            border-radius: 99px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .countdown-timer i {
            font-size: 1rem;
        }

        .countdown-timer.expired {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.2);
        }

        /* End Election Button */
        .end-election-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, var(--danger), #DC2626);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1000;
        }

        .end-election-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4);
        }

        .end-election-btn:active {
            transform: translateY(-1px);
        }

        .end-election-btn i {
            font-size: 1.25rem;
        }

        @media (max-width: 992px) {
            .candidate-result-card {
                grid-template-columns: 50px 70px 1fr 100px;
                grid-template-areas: "rank photo info stats" "rank progress progress progress";
                gap: 1rem;
            }
        }
    </style>

    <link rel="stylesheet" href="../../assets/css/mobile_base.css">
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('Results'); ?>
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
                <div class="nav-item active">
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
                    <h1><?php echo $is_viewing_archive ? 'Archive Analytics' : 'Election Results'; ?></h1>
                    <p>Monitoring results for <span
                            style="color: var(--primary); font-weight: 700;"><?php echo htmlspecialchars($election_name); ?></span>
                    </p>
                </div>
                <?php if (!$is_viewing_archive): ?>
                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div class="live-indicator">
                            <div class="live-dot"></div>
                            LIVE REAL-TIME UPDATES
                        </div>
                        <?php if ($election_end_time): ?>
                            <div id="countdown-timer" class="countdown-timer"
                                data-end-time="<?php echo htmlspecialchars($election_end_time); ?>">
                                <i class="fas fa-clock"></i>
                                <span id="countdown-display">Calculating...</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>

            <!-- Winners Spotlight (Only when archived/ended) -->
            <?php if ($is_viewing_archive && !empty($results_by_position)): ?>
                <div class="winners-spotlight-banner modern-card"
                    style="padding: 2rem; background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(0, 91, 170, 0.05)); border: 2px solid var(--primary); margin-bottom: 2rem; position: relative; overflow: hidden;">
                    <div
                        style="position: absolute; top: -20px; right: -20px; font-size: 8rem; color: rgba(79, 70, 229, 0.05); transform: rotate(-15deg);">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                        <div
                            style="width: 50px; height: 50px; background: var(--warning); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="fas fa-award" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); line-height: 1;">
                                Election Winners</h2>
                            <p style="color: var(--text-secondary); font-weight: 500;">Official Final Results</p>
                        </div>
                    </div>

                    <div class="winners-scroll-container"
                        style="display: flex; gap: 1.5rem; overflow-x: auto; padding: 1.5rem 0 1rem 0; scrollbar-width: thin;">
                        <?php foreach ($results_by_position as $pos => $cands):
                            $winner = $cands[0];
                            if ($winner['votes'] > 0):
                                ?>
                                <div class="winner-spotlight-card"
                                    style="flex: 0 0 240px; background: var(--bg-primary); border-radius: 20px; padding: 1.5rem; border: 1px solid var(--border); position: relative; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                                    <div class="winner-badge"
                                        style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; padding: 6px 16px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4); white-space: nowrap; z-index: 10; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-crown" style="font-size: 0.8rem; color: #FFD700;"></i>
                                        <?php echo htmlspecialchars($pos); ?>
                                    </div>
                                    <div style="text-align: center; margin-top: 0.5rem;">
                                        <div style="position: relative; display: inline-block;">
                                            <img src="../../<?php echo htmlspecialchars(preg_replace('/^(\.\.\/|\.\/)+/', '', $winner['photo_path'])); ?>"
                                                onerror="this.src='../../logo/srclogo.png'"
                                                style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary);">
                                            <div
                                                style="position: absolute; bottom: 0; right: 0; background: var(--warning); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid var(--bg-primary);">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                        </div>
                                        <h4 style="margin-top: 1rem; font-weight: 700; color: var(--text-primary);">
                                            <?php echo htmlspecialchars($winner['first_name'] . ' ' . $winner['last_name']); ?>
                                        </h4>
                                        <div
                                            style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--primary); font-weight: 700;">
                                            <i class="fas fa-vote-yea"></i>
                                            <span><?php echo number_format($winner['votes']); ?> Votes</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Global Stats -->
            <div class="stats-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="modern-card" style="padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem;">
                    <div
                        style="width: 48px; height: 48px; background: rgba(79, 70, 229, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <i class="fas fa-users" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <p
                            style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500; margin-bottom: 0.25rem;">
                            Registered Voters</p>
                        <h3 id="total-users-val" style="font-size: 1.5rem; font-weight: 800;">
                            <?php echo number_format($total_users); ?>
                        </h3>
                    </div>
                </div>
                <div class="modern-card" style="padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem;">
                    <div
                        style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--success);">
                        <i class="fas fa-vote-yea" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <p
                            style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500; margin-bottom: 0.25rem;">
                            Total Votes Cast</p>
                        <h3 id="total-votes-val" style="font-size: 1.5rem; font-weight: 800;">
                            <?php echo number_format($total_votes); ?>
                        </h3>
                    </div>
                </div>
                <div class="modern-card" style="padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem;">
                    <div
                        style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--warning);">
                        <i class="fas fa-chart-line" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <p
                            style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500; margin-bottom: 0.25rem;">
                            Voter Turnout</p>
                        <h3 id="turnout-val" style="font-size: 1.5rem; font-weight: 800;"><?php echo $voter_turnout; ?>%
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Position Results -->
            <?php if (empty($candidates) || empty($results_by_position)): ?>
                <div class="modern-card"
                    style="padding: 4rem; text-align: center; background: var(--bg-secondary); border-style: dashed;">
                    <div style="font-size: 3.5rem; color: var(--border); margin-bottom: 1.5rem;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <?php if (!$election_id_to_show && !$is_viewing_archive): ?>
                        <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
                            No Active Election Found
                        </h3>
                        <p style="color: var(--text-secondary); max-width: 400px; margin: 0 auto 2rem auto;">
                            There is currently no active election. You need to start a new election to begin adding candidates
                            and accepting vot_votes.
                        </p>
                        <button
                            onclick="if(confirm('Start a new election now? This will archive any previous voting data.')) window.location.href='start_new_election.php';"
                            class="modern-button primary" style="padding: 1rem 2rem;">
                            <i class="fas fa-play-circle"></i> Start New Election
                        </button>
                    <?php else: ?>
                        <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
                            Election Active: No Candidates Yet
                        </h3>
                        <p style="color: var(--text-secondary); max-width: 400px; margin: 0 auto 2rem auto;">
                            The election "<?php echo htmlspecialchars($election_name); ?>" is active, but no candidates have
                            been added yet.
                        </p>
                        <a href="manage_candidates.php" class="modern-button primary" style="padding: 1rem 2rem;">
                            <i class="fas fa-user-plus"></i> Add Candidates
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div id="results-analytics-container">
                    <?php foreach ($results_by_position as $position => $position_candidates): ?>
                        <div class="position-section" id="section-<?php echo preg_replace('/[^a-zA-Z0-9]/', '-', $position); ?>"
                            style="margin-bottom: 3rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                <h2
                                    style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); white-space: nowrap;">
                                    <i class="fas fa-user-tag" style="color: var(--primary); margin-right: 0.75rem;"></i>
                                    <?php echo htmlspecialchars($position); ?>
                                </h2>
                                <div style="flex: 1; height: 1px; background: var(--border);"></div>
                            </div>

                            <div class="candidates-container"
                                id="container-<?php echo preg_replace('/[^a-zA-Z0-9]/', '-', $position); ?>"
                                style="display: grid; gap: 1rem;">
                                <?php
                                $position_total_votes = array_sum(array_column($position_candidates, 'votes'));
                                $rank = 1;
                                foreach ($position_candidates as $candidate):
                                    $percentage = ($position_total_votes > 0) ? round(($candidate['votes'] / $position_total_votes) * 100, 2) : 0;
                                    $is_winner = ($rank == 1 && $candidate['votes'] > 0);
                                    ?>
                                    <div class="candidate-result-card <?php echo $is_winner ? 'rank-1' : ''; ?>"
                                        id="candidate-<?php echo $candidate['id']; ?>" data-id="<?php echo $candidate['id']; ?>"
                                        data-votes="<?php echo $candidate['votes']; ?>" data-rank="<?php echo $rank; ?>">

                                        <div class="result-rank">#<?php echo $rank; ?></div>

                                        <div style="position: relative;">
                                            <img src="../<?php echo htmlspecialchars($candidate['photo_path']); ?>" alt="Candidate"
                                                class="result-photo" onerror="this.src='../pic/default-avatar.png'">
                                            <?php if ($is_winner): ?>
                                                <div class="winner-crown"><i class="fas fa-crown"></i></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="result-info">
                                            <h4>
                                                <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                            </h4>
                                            <p
                                                style="font-size: 0.875rem; color: var(--text-secondary); display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?php echo htmlspecialchars($candidate['platform']); ?>
                                            </p>
                                        </div>

                                        <div class="progress-container">
                                            <div class="progress-info">
                                                <span style="color: var(--text-secondary);">Position Strength</span>
                                                <span class="pct-text"
                                                    style="color: var(--primary);"><?php echo $percentage; ?>%</span>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>

                                        <div class="vote-stats" style="text-align: right;">
                                            <span class="vote-number"
                                                id="votes-val-<?php echo $candidate['id']; ?>"><?php echo number_format($candidate['votes']); ?></span>
                                            <span
                                                style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Votes</span>
                                        </div>
                                    </div>
                                    <?php $rank++; endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Floating Actions -->
            <div class="floating-actions">
                <button onclick="window.print()" class="modern-button primary">
                    <i class="fas fa-print"></i> Export Report
                </button>

                <?php if (!$is_viewing_archive): ?>
                    <form method="POST" style="display: inline;"
                        onsubmit="return confirm('⚠️ CRITICAL ACTION\n\nThis will:\n✓ End the current election immediately\n✓ Close voting for all students\n✓ Display winners automatically\n✓ Move election to archives\n\nThis action cannot be undone. Proceed?');">
                        <input type="hidden" name="end_election" value="1">
                        <button type="submit" class="modern-button danger">
                            <i class="fas fa-stop-circle"></i> End Election Now
                        </button>
                    </form>
                <?php else: ?>
                    <a href="manage_history.php" class="modern-button secondary">
                        <i class="fas fa-arrow-left"></i> Back to History
                    </a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- JS Logic for Polling and FLIP Animation -->
    <script>
        const ELECTION_ID = <?php echo ($election_id_to_show) ? $election_id_to_show : 'null'; ?>;
        const IS_ARCHIVE = <?php echo ($is_viewing_archive) ? 'true' : 'false'; ?>;

        if (!IS_ARCHIVE && ELECTION_ID) {
            setInterval(fetchUpdates, 3000);
        }

        async function fetchUpdates() {
            try {
                const response = await fetch(`get_results_ajax.php?id=${ELECTION_ID}`);
                const data = await response.json();

                if (data.error) return;

                // Update Stats
                updateStatText('total-users-val', data.total_voters.toLocaleString());
                updateStatText('total-votes-val', data.total_votes.toLocaleString());
                updateStatText('turnout-val', data.voter_turnout + '%');

                // Update Each Position
                for (const [pos, vot_candidates] of Object.entries(data.positions)) {
                    updatePositionPool(pos, vot_candidates);
                }
            } catch (error) {
                console.error("Polling error:", error);
            }
        }

        function updateStatText(id, newValue) {
            const el = document.getElementById(id);
            if (el && el.innerText !== newValue.toString()) {
                el.innerText = newValue;
            }
        }

        function updatePositionPool(position, sortedCandidates) {
            const sanitizedPos = position.replace(/[^a-zA-Z0-9]/g, '-');
            const container = document.getElementById(`container-${sanitizedPos}`);
            if (!container) return;

            // 1. Get current DOM state
            const currentCards = Array.from(container.children);
            const firstPositions = currentCards.map(card => {
                const rect = card.getBoundingClientRect();
                return { id: card.dataset.id, top: rect.top };
            });

            // 2. Update values and ranks without moving DOM yet
            sortedCandidates.forEach(cand => {
                const card = document.getElementById(`candidate-${cand.id}`);
                if (card) {
                    // Update vot_votes
                    const voteEl = document.getElementById(`votes-val-${cand.id}`);
                    const oldVotes = parseInt(voteEl.innerText.replace(/,/g, ''));
                    if (parseInt(cand.votes) > oldVotes) {
                        voteEl.classList.add('pop');
                        setTimeout(() => voteEl.classList.remove('pop'), 600);
                    }
                    voteEl.innerText = parseInt(cand.votes).toLocaleString();
                    card.dataset.votes = cand.votes;

                    // Update Percentage
                    const pctText = card.querySelector('.pct-text');
                    const progressFill = card.querySelector('.progress-fill');
                    if (pctText) pctText.innerText = cand.percentage + '%';
                    if (progressFill) progressFill.style.width = cand.percentage + '%';

                    // Update Rank display
                    card.querySelector('.result-rank').innerText = `#${cand.rank}`;
                    card.dataset.rank = cand.rank;

                    // Winner Special Treatment
                    const imgBox = card.querySelector('div[style*="position: relative"]');
                    if (cand.rank === 1 && cand.votes > 0) {
                        card.classList.add('rank-1');
                        if (!card.querySelector('.winner-crown')) {
                            const crown = document.createElement('div');
                            crown.className = 'winner-crown';
                            crown.innerHTML = '<i class="fas fa-crown"></i>';
                            if (imgBox) imgBox.appendChild(crown);
                        }
                    } else {
                        card.classList.remove('rank-1');
                        const crown = card.querySelector('.winner-crown');
                        if (crown) crown.remove();
                    }
                }
            });

            // 3. FLIP: Check if order changed
            const isOrderChanged = sortedCandidates.some((cand, idx) => {
                const card = currentCards[idx];
                return card && card.dataset.id != cand.id;
            });

            if (isOrderChanged) {
                // Re-sort DOM elements
                const fragment = document.createDocumentFragment();
                sortedCandidates.forEach(cand => {
                    const card = document.getElementById(`candidate-${cand.id}`);
                    if (card) fragment.appendChild(card);
                });

                // Invert & Play
                const cardsToAnimate = Array.from(fragment.children);

                // Save last positions before appending
                container.appendChild(fragment);

                cardsToAnimate.forEach(card => {
                    const first = firstPositions.find(p => p.id === card.dataset.id);
                    if (first) {
                        const last = card.getBoundingClientRect().top;
                        const invert = first.top - last;

                        if (invert !== 0) {
                            card.style.transition = 'none';
                            card.style.transform = `translateY(${invert}px)`;

                            // Trigger reflow
                            card.parentElement.offsetHeight;

                            card.style.transition = 'all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
                            card.style.transform = '';
                        }
                    }
                });
            }
        }
    </script>

    <!-- Countdown Timer Script -->
    <script>
        // Countdown Timer Logic
        const countdownTimer = document.getElementById('countdown-timer');
        if (countdownTimer) {
            const endTime = new Date(countdownTimer.dataset.endTime).getTime();

            function updateCountdown() {
                const now = new Date().getTime();
                const distance = endTime - now;

                if (distance < 0) {
                    document.getElementById('countdown-display').textContent = 'Voting Ended';
                    countdownTimer.classList.add('expired');
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                let timeString = '';
                if (days > 0) {
                    timeString += `${days}d `;
                }
                if (hours > 0 || days > 0) {
                    timeString += `${hours}h `;
                }
                timeString += `${minutes}m ${seconds}s remaining`;

                document.getElementById('countdown-display').textContent = timeString;
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        }
    </script>

    <script>
        // Celebration effect for winners
        window.addEventListener('load', () => {
            const duration = 3 * 1000;
            const end = Date.now() + duration;

            (function frame() {
                confetti({
                    particleCount: 3,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 },
                    colors: ['#4F46E5', '#7C3AED']
                });
                confetti({
                    particleCount: 3,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 },
                    colors: ['#4F46E5', '#7C3AED']
                });

                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());
        });
    </script>

    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('admin'); ?>
</body>

</html>