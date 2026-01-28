<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// ------ LOGIC PARA PILIIN KUNG ANONG ELECTION ANG IPAPAKITA ------
$election_id_to_show = null;
$election_name = "No Election Selected";
$is_viewing_archive = false;
$page_title = "Current Results";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $election_id_to_show = $_GET['id'];
    $is_viewing_archive = true;
    $stmt = $pdo->prepare("SELECT title FROM elections WHERE id = ?");
    $stmt->execute([$election_id_to_show]);
    $election_name_from_db = $stmt->fetchColumn();
    if ($election_name_from_db) {
        $election_name = $election_name_from_db;
        $page_title = "Archive: " . $election_name;
    }
} else {
    $stmt = $pdo->prepare("SELECT id, title FROM elections WHERE is_active = 1");
    $stmt->execute();
    $active_election = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($active_election) {
        $election_id_to_show = $active_election['id'];
        $election_name = $active_election['title'] . " (Current)";
    }
}

// Simulan ang mga variables bilang empty
$candidates = [];
$positions_in_db = [];
$total_votes = 0;

if ($election_id_to_show) {
    // Logic updated for tie-breaker: first to receive a vote wins
    $stmt = $pdo->prepare("
        SELECT c.*, 
        (SELECT MIN(vote_id) FROM votes WHERE candidate_id = c.id) as first_vote_id
        FROM candidates c
        WHERE c.election_id = :id 
        ORDER BY c.position, c.votes DESC, first_vote_id ASC
    ");
    $stmt->execute(['id' => $election_id_to_show]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($candidates) {
        $stmt = $pdo->prepare("SELECT DISTINCT position FROM candidates WHERE election_id = :id");
        $stmt->execute(['id' => $election_id_to_show]);
        $positions_in_db = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM votes WHERE election_id = :id");
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        /* ----- BUONG ORIGINAL CSS MO - PARA SIGURADONG DI MAWAWALA ANG DESIGN ----- */
        :root {
            --primary-color: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary-color: #7c3aed;
            --accent-color: #2563eb;
            --light-color: #f9fafb;
            --dark-color: #111827;
            --gray-dark: #374151;
            --gray-medium: #6b7280;
            --gray-light: #e5e7eb;
            --danger-color: #dc2626;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --box-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: #f3f4f6;
            color: var(--dark-color);
            line-height: 1.5;
        }

        .admin-dashboard-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .admin-top-bar {
            background-color: white;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
            height: 70px;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .admin-info span {
            font-weight: 500;
            color: var(--gray-dark);
            font-size: 0.95rem;
        }

        .logout-btn {
            color: var(--gray-medium);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
        }

        .logout-btn:hover {
            color: var(--danger-color);
            background-color: rgba(220, 38, 38, 0.1);
        }

        .admin-main-content {
            display: flex;
            flex: 1;
        }

        .admin-sidebar {
            width: 280px;
            background-color: rgb(54, 51, 112);
            padding: 1.5rem 0;
            box-shadow: var(--box-shadow);
            height: calc(100vh - 70px);
            position: sticky;
            top: 70px;
            transition: var(--transition);
            border-right: 1px solid var(--gray-light);
        }

        .admin-sidebar ul {
            list-style: none;
            padding: 0 1rem;
        }

        .admin-sidebar li {
            margin-bottom: 0.25rem;
        }

        .admin-sidebar a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            gap: 0.75rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .admin-sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .admin-sidebar a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .admin-sidebar .active a {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }

        .admin-content-area {
            flex: 1;
            padding: 2rem;
            background-color: #f3f4f6;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .content-header h2 {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1.75rem;
        }

        .results-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .summary-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-md);
        }

        .summary-card h3 {
            font-size: 1rem;
            color: var(--gray-medium);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .summary-card p {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .position-results {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .position-results h3 {
            font-size: 1.25rem;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--gray-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        th {
            background-color: var(--light-color);
            color: var(--gray-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        tr:hover {
            background-color: rgba(79, 70, 229, 0.03);
        }

        .candidate-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .candidate-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray-light);
        }

        .candidate-info h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .platform {
            font-size: 0.875rem;
            color: var(--gray-medium);
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .percentage-bar-container span {
            color: black;
            font-weight: bold;
        }

        .percentage-bar-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .percentage-bar {
            height: 8px;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .export-options {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-top: 2rem;
        }

        .export-options h3 {
            font-size: 1.25rem;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.75rem;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--box-shadow);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .sidebar-logo {
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .sidebar-logo img {
            max-width: 100%;
            height: auto;
            max-height: 80px;
        }

        .sidebar-logo span {
            display: block;
            color: white;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .logo {
            padding: 20px;
            text-align: center;
        }

        .logo img {
            max-width: 100%;
            height: auto;
            max-height: 80px;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .btn-danger:active {
            transform: translateY(0);
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
            color: white;
        }

        @media (max-width: 1200px) {
            .admin-sidebar {
                width: 240px;
            }
        }

        @media (max-width: 992px) {
            .admin-sidebar {
                width: 80px;
                padding: 1rem 0;
                overflow: hidden;
            }

            .admin-sidebar a span,
            .sidebar-logo span {
                display: none;
            }

            .admin-sidebar a {
                justify-content: center;
                padding: 0.75rem 0;
            }

            .sidebar-logo img {
                max-height: 40px;
            }
        }

        @media (max-width: 768px) {
            .admin-top-bar {
                padding: 0 1rem;
            }

            .admin-content-area {
                padding: 1.5rem;
            }

            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .results-summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .admin-sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                top: auto;
                height: auto;
                width: 100%;
                display: flex;
                justify-content: center;
                z-index: 100;
                padding: 0.5rem 0;
                border-top: 1px solid var(--gray-light);
            }

            .admin-sidebar ul {
                display: flex;
                width: 100%;
                justify-content: space-around;
                padding: 0;
            }

            .admin-sidebar li {
                margin-bottom: 0;
            }

            .admin-main-content {
                padding-bottom: 80px;
            }

            .admin-info span {
                display: none;
            }

            .sidebar-logo {
                display: none;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media print {

            .admin-sidebar,
            .admin-top-bar,
            .export-options,
            .btn,
            a.logout-btn,
            .results-summary,
            .reset-btn {
                display: none !important;
            }

            body {
                background: white;
                color: black;
            }

            .admin-content-area {
                margin: 0;
                padding: 0;
                width: 100%;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            table th,
            table td {
                border: 1px solid #000;
                padding: 5px;
            }

            tbody tr:not(.rank-1) {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="admin-dashboard-container">
        <div class="admin-top-bar">
            <div class="logo"><i class="fas fa-vote-yea"></i><span>Santa Rita College</span></div>
            <div class="admin-info">
                <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>
        <div class="admin-main-content">
            <div class="admin-sidebar">
                <center>
                    <div class="logo"><img src="../pic/srclogo.png" alt="Logo"><span style="color: white">Voting
                            System</span></div>
                </center>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users-cog"></i><span>Manage Users</span></a></li>
                    <li><a href="manage_candidates.php"><i class="fas fa-user-tie"></i><span>Manage
                                Candidates</span></a></li>

                    <li><a href="manage_history.php"><i class="fas fa-history"></i><span>Manage history</span></a></li>
                    <li class="active"><a href="results.php"><i class="fas fa-chart-pie"></i><span>Results
                                Analytics</span></a></li>
                </ul>
            </div>
            <div class="admin-content-area">
                <h2>
                    <?php if ($is_viewing_archive): ?>
                        Archived Results: <span
                            style="color: #4f46e5;"><?php echo htmlspecialchars($election_name); ?></span>
                    <?php else: ?>
                        Current Election Results
                    <?php endif; ?>
                </h2>

                <?php if ($is_viewing_archive && !empty($candidates)): ?>
                    <!-- Celebration Banner for Results History -->
                    <div class="celebration-banner"
                        style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2); animation: slideIn 0.5s ease-out;">
                        <div style="font-size: 2.5rem;">🎉</div>
                        <div style="flex: 1;">
                            <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: white;">Election Concluded!
                            </h3>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">Congratulations to the winners of
                                "<?php echo htmlspecialchars($election_name); ?>".</p>
                        </div>
                        <div style="font-size: 2.5rem;">🎊</div>
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
                                    colors: ['#4f46e5', '#7c3aed', '#10b981']
                                });
                                confetti({
                                    particleCount: 3,
                                    angle: 120,
                                    spread: 55,
                                    origin: { x: 1 },
                                    colors: ['#4f46e5', '#7c3aed', '#10b981']
                                });

                                if (Date.now() < end) {
                                    requestAnimationFrame(frame);
                                }
                            }());
                        });
                    </script>
                <?php endif; ?>
                <div class="results-summary">
                    <div class="summary-card">
                        <h3>Total Registered Voters</h3>
                        <p><?php echo $total_users; ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Total Votes Cast</h3>
                        <p><?php echo $total_votes; ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Voter Turnout</h3>
                        <p><?php echo $voter_turnout; ?>%</p>
                    </div>
                </div>

                <?php if (!$is_viewing_archive): ?>
                    <a href="start_new_election.php" class="btn btn-danger mb-3 p-3 d-flex align-items-center reset-btn"
                        onclick="return confirm('WARNING: This will archive the current election and start a new one. Are you sure?')">
                        <i class="fas fa-archive me-2"></i> Archive & Start New Election
                    </a>
                <?php else: ?>
                    <a href="archive.php" class="btn btn-secondary mb-3">
                        <i class="fas fa-arrow-left me-2"></i> Back to Archives
                    </a>
                <?php endif; ?>

                <?php if (empty($candidates)): ?>
                    <div class="position-results"
                        style="padding: 2rem;text-align: center;border: 1px dashed var(--gray-light);">
                        <p>No election data found.</p>
                        <p style="font-size: 0.9rem; color: var(--gray-medium); margin-top: 0.5rem;">
                            <?php if ($is_viewing_archive) {
                                echo "There might not be any candidates associated with this archived election.";
                            } else {
                                echo "There is no active election, or no candidates have been added yet.";
                            } ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results_by_position as $position => $position_candidates): ?>
                        <div class="position-results">
                            <h3><?php echo htmlspecialchars($position); ?></h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Candidate</th>
                                        <th>Votes</th>
                                        <th>Percentage Votes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $position_total_votes = array_sum(array_column($position_candidates, 'votes'));
                                    $rank = 1;
                                    foreach ($position_candidates as $candidate):
                                        $percentage = ($position_total_votes > 0) ? round(($candidate['votes'] / $position_total_votes) * 100, 2) : 0;
                                        $row_class = ($rank == 1) ? "rank-1" : "not-rank-1";
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td><?php echo $rank; ?></td>
                                            <td>
                                                <div class="candidate-info">
                                                    <img src="../<?php echo htmlspecialchars(preg_replace('/^(\.\.\/|\.\/)+/', '', $candidate['photo_path'])); ?>"
                                                        onerror="this.src='../logo/srclogo.png'"
                                                        alt="<?php echo htmlspecialchars($candidate['first_name']); ?>"
                                                        class="candidate-photo">
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                        </h4>
                                                        <p class="platform">
                                                            <?php echo nl2br(htmlspecialchars($candidate['platform'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $candidate['votes']; ?></td>
                                            <td>
                                                <div class="percentage-bar-container">
                                                    <div class="percentage-bar"
                                                        style="width: <?php echo $percentage; ?>%; background-color: <?php echo ($rank == 1) ? 'green' : 'red'; ?>;">
                                                    </div>
                                                    <span style="color: black;"><?php echo $percentage; ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $rank++; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- ===== DITO ANG PAGBABAGO ===== -->
                <div class="export-options">
                    <h3>Export Options</h3>

                    <button onclick="window.print()" class="btn">🖨️ Print Winners</button>

                    <?php if (!$is_viewing_archive): ?>
                        <!-- Kung nasa CURRENT results page, ipakita ang button papuntang ARCHIVE -->
                        <a href="archive.php" class="btn btn-primary">
                            <i class="fas fa-archive"></i> View Election Archives
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>

</html>