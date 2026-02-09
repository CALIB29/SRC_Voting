<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/']);
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ------ LOGIC 1: Get the ID of the currently active election. ------
$active_election_id = $pdo->query("SELECT id FROM vot_elections WHERE is_active = 1")->fetchColumn();


// ------ LOGIC 2: Get candidates for the ACTIVE election. ------
$candidates = [];
if ($active_election_id) {
    $stmt = $pdo->prepare("
        SELECT *, CONCAT(last_name, ', ', first_name, ' ', middle_name) AS full_name
        FROM vot_candidates
        WHERE election_id = :election_id
        ORDER BY 
            CASE position
                WHEN 'President' THEN 1 WHEN 'Vice President' THEN 2 WHEN 'Secretary' THEN 3 WHEN 'Treasurer' THEN 4
                WHEN 'Auditor' THEN 5 WHEN 'PRO' THEN 6 WHEN 'Business Manager' THEN 7 WHEN 'Sgt. at Arms' THEN 8
                ELSE 9
            END,
            last_name, first_name
    ");
    $stmt->execute(['election_id' => $active_election_id]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$candidates_by_position = [];
foreach ($candidates as $candidate) {
    $candidates_by_position[$candidate['position']][] = $candidate;
}

// fixed positions order
$fixed_positions = [
    'President',
    'Vice President',
    'Secretary',
    'Treasurer',
    'Auditor',
    'PRO',
    'Business Manager',
    'Sgt. at Arms'
];

$ordered_candidates_by_position = [];
foreach ($fixed_positions as $pos) {
    if (isset($candidates_by_position[$pos])) {
        $ordered_candidates_by_position[$pos] = $candidates_by_position[$pos];
    }
}
foreach ($candidates_by_position as $pos => $cand_list) {
    if (!isset($ordered_candidates_by_position[$pos])) {
        $ordered_candidates_by_position[$pos] = $cand_list;
    }
}
$candidates_by_position = $ordered_candidates_by_position;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Candidates | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/student_premium.css">
    <link rel="stylesheet" href="../assets/css/mobile_base.css">
    <style>
        .candidate-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .candidate-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--box-shadow);
        }

        .candidate-img-wrapper {
            position: relative;
            padding-top: 100%;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.2);
        }

        .candidate-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .candidate-card:hover .candidate-img {
            transform: scale(1.05);
        }

        .candidate-info {
            padding: 1.25rem;
            text-align: center;
        }

        .candidate-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .position-badge {
            display: inline-block;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            font-size: 1.4rem;
            font-weight: 700;
        }

        .section-title i {
            color: var(--primary);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            color: var(--text-main);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.2);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }

        .btn-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-close:hover {
            color: white;
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .profile-img {
            width: 100%;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
        }

        .profile-info h3 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, white, var(--text-muted));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .profile-position {
            font-size: 1.1rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: block;
        }

        .platform-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.5rem;
        }

        .platform-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
        }

        .platform-text {
            color: var(--text-dim);
            line-height: 1.7;
            white-space: pre-wrap;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .profile-img {
                max-width: 250px;
                margin: 0 auto;
                display: block;
            }

            .profile-info {
                text-align: center;
            }

            .platform-header {
                justify-content: center;
            }

            .platform-text {
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('View Candidates'); ?>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="pic/srclogo.png" alt="Logo">
                <div class="sidebar-brand">
                    <h5>Santa Rita College</h5>
                    <span
                        class="dept-badge"><?php echo isset($_SESSION['departments']) ? str_replace([' VOTING', '_', '-'], '', $_SESSION['departments']) : 'Voting System'; ?></span>
                </div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span></a></li>
                <li class="nav-item"><a href="vote.php" class="nav-link"><i class="fas fa-vote-yea"></i> <span>Vote
                            Now</span></a></li>
                <li class="nav-item active"><a href="view.php" class="nav-link"><i class="fas fa-users"></i> <span>View
                            Candidates</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Election Candidates</h1>
                </div>
                <div class="user-profile">
                    <div class="avatar"><?php echo substr($_SESSION['first_name'], 0, 1); ?></div>
                    <div class="user-meta">
                        <span
                            class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Student'); ?></span>
                        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <?php if (empty($candidates_by_position)): ?>
                    <div class="status-card pending">
                        <i class="fas fa-folder-open"></i>
                        <div class="status-content">
                            <h3>No Candidates</h3>
                            <p>No candidates found for the current election. Please check back later.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($candidates_by_position as $position => $candidates_list): ?>
                        <div class="section-title">
                            <i class="fas fa-user-tie"></i>
                            <?= htmlspecialchars($position) ?>
                        </div>
                        <div class="candidate-grid">
                            <?php foreach ($candidates_list as $row): ?>
                                <?php
                                $full_name = trim($row['first_name'] . ' ' . (!empty($row['middle_name']) ? $row['middle_name'] . ' ' : '') . $row['last_name']);
                                ?>
                                <div class="candidate-card" onclick="openCandidateModal(this)"
                                    data-name="<?= htmlspecialchars($full_name) ?>"
                                    data-photo="<?= fixCandidatePhotoPath($row['photo_path'], '../') ?>"
                                    data-platform="<?= htmlspecialchars($row['platform']) ?>"
                                    data-position="<?= htmlspecialchars($row['position']) ?>">
                                    <div class="candidate-img-wrapper">
                                        <img src="<?= fixCandidatePhotoPath($row['photo_path'], '../') ?>" class="candidate-img"
                                            alt="<?= htmlspecialchars($full_name) ?>" onerror="this.src='pic/srclogo.png'">
                                    </div>
                                    <div class="candidate-info">
                                        <h5 class="candidate-name"><?= htmlspecialchars($full_name) ?></h5>
                                        <span class="position-badge"><?= htmlspecialchars($row['position']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Candidate Modal -->
    <div id="candidateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Candidate Profile</h5>
                <button type="button" class="btn-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="profile-grid">
                    <div>
                        <img id="modalImage" src="" class="profile-img" alt="Candidate">
                    </div>
                    <div class="profile-info">
                        <h3 id="modalName">Candidate Name</h3>
                        <span id="modalPosition" class="profile-position">Position</span>

                        <div class="platform-box">
                            <div class="platform-header">
                                <i class="fas fa-bullhorn"></i> Campaign Platform
                            </div>
                            <div id="modalPlatform" class="platform-text"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('student'); ?>

    <script>
        function openCandidateModal(element) {
            const name = element.getAttribute('data-name');
            const photo = element.getAttribute('data-photo');
            const platform = element.getAttribute('data-platform');
            const position = element.getAttribute('data-position');

            document.getElementById('modalName').textContent = name;
            document.getElementById('modalImage').src = photo;
            document.getElementById('modalPlatform').textContent = platform || "No platform information provided.";
            document.getElementById('modalPosition').textContent = position;

            document.getElementById('candidateModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('candidateModal').classList.remove('show');
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('candidateModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>