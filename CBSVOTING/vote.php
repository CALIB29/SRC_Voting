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

// ------ LOGIC 2: Check if the user has already voted. ------
$user_id = $_SESSION['user_id']; // students.student_id
$stmt_user_voted = $pdo->prepare("SELECT has_voted FROM students WHERE student_id = ?");
$stmt_user_voted->execute([$user_id]);
$user = $stmt_user_voted->fetch();

if ($user && $user['has_voted']) {
    header("Location: dashboard.php");
    exit;
}

// ------ LOGIC 3: Check the voting vot_schedules. ------
$schedule_stmt = $pdo->query("SELECT start_datetime, end_datetime FROM vot_voting_schedule LIMIT 1");
$schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
$voting_allowed = false;
$voting_status_message = ""; // Use this for all status messages

if ($schedule && !empty($schedule['start_datetime']) && !empty($schedule['end_datetime'])) {
    try {
        $now = new DateTime("now", new DateTimeZone('Asia/Manila'));
        $start_datetime = new DateTime($schedule['start_datetime'], new DateTimeZone('Asia/Manila'));
        $end_datetime = new DateTime($schedule['end_datetime'], new DateTimeZone('Asia/Manila'));

        if ($now >= $start_datetime && $now <= $end_datetime) {
            $voting_allowed = true;
            $voting_status_message = "Voting is now open! It will close on " . $end_datetime->format('F j, Y, g:i A') . ".";
        } elseif ($now < $start_datetime) {
            $voting_allowed = false;
            $voting_status_message = "Voting has not started yet. It will begin on " . $start_datetime->format('F j, Y, g:i A') . ".";
        } else {
            $voting_allowed = false;
            $voting_status_message = "Voting has ended on " . $end_datetime->format('F j, Y, g:i A') . ".";
        }
    } catch (Exception $e) {
        $voting_status_message = "Invalid schedule format set by the administrator.";
        $voting_allowed = false;
    }
} else {
    $voting_allowed = false;
    $voting_status_message = "No voting schedule has been set by the administrator.";
}


// ------ LOGIC 4: Get candidates for the ACTIVE election. ------
$candidates = [];
if ($active_election_id) {
    $stmt_candidates = $pdo->prepare("
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
    $stmt_candidates->execute(['election_id' => $active_election_id]);
    $candidates = $stmt_candidates->fetchAll(PDO::FETCH_ASSOC);
}

$candidates_by_position = [];
foreach ($candidates as $candidate) {
    $candidates_by_position[$candidate['position']][] = $candidate;
}

// ------ LOGIC 5: Handle vote submission with election_id ------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $voting_allowed) {
    try {
        if (!$active_election_id) {
            throw new Exception("No active election to vote for.");
        }
        $pdo->beginTransaction();
        foreach ($_POST as $position => $candidate_id) {
            if ($position === 'submit_vote')
                continue;
            $stmt_insert = $pdo->prepare("INSERT INTO vot_votes (user_id, candidate_id, position, election_id) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$user_id, $candidate_id, $position, $active_election_id]);
            $stmt_update = $pdo->prepare("UPDATE vot_candidates SET votes = votes + 1 WHERE id = ?");
            $stmt_update->execute([$candidate_id]);
        }
        $stmt_voted = $pdo->prepare("UPDATE students SET has_voted = 1 WHERE student_id = ?");
        $stmt_voted->execute([$user_id]);
        $pdo->commit();
        $_SESSION['voted'] = true;
        header("Location: dashboard.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Voting failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Now | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/student_premium.css">
    <link rel="stylesheet" href="../assets/css/mobile_base.css">
    <style>
        /* Specific overrides for voting page */
        .voting-form-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .position-group {
            background: var(--bg-card);
            backdrop-filter: var(--glass);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2.5rem;
            animation: fadeIn 0.5s ease forwards;
        }

        .position-group h3 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .position-group h3 i {
            color: var(--primary);
        }

        .candidates-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .candidate-card {
            position: relative;
            cursor: pointer;
            height: 100%;
        }

        .candidate-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .candidate-info {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition);
            height: 100%;
            position: relative;
        }

        .candidate-card:hover .candidate-info {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--box-shadow);
        }

        .candidate-card input[type="radio"]:checked+.candidate-info {
            background: linear-gradient(145deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary);
        }

        .candidate-card input[type="radio"]:checked+.candidate-info::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .candidate-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .candidate-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-main);
        }

        .candidate-party {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .platform-preview {
            font-size: 0.85rem;
            color: var(--text-dim);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .view-platform-btn {
            font-size: 0.8rem;
            color: var(--primary);
            text-decoration: underline;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
        }

        .vote-submit-container {
            position: sticky;
            bottom: 2rem;
            z-index: 100;
            display: flex;
            justify-content: center;
            margin-top: 3rem;
            pointer-events: none;
        }

        .vote-submit-container .btn {
            pointer-events: auto;
            min-width: 250px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            font-size: 1.1rem;
            padding: 1rem 2rem;
        }

        /* Modal styling match */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            width: 90%;
            max-width: 600px;
            padding: 2rem;
            position: relative;
            color: var(--text-main);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .review-list {
            list-style: none;
            max-height: 50vh;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }

        .review-list li {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .review-list li:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('Vote Now'); ?>

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
                <li class="nav-item active"><a href="vote.php" class="nav-link"><i class="fas fa-vote-yea"></i>
                        <span>Vote
                            Now</span></a></li>
                <li class="nav-item"><a href="view.php" class="nav-link"><i class="fas fa-users"></i> <span>View
                            Candidates</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Cast Your Vote</h1>
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
                <div class="voting-form-container">
                    <?php if ($voting_status_message && !$voting_allowed): ?>
                        <div class="status-card pending" style="border: 1px solid var(--warning);">
                            <i class="fas fa-clock" style="color: var(--warning);"></i>
                            <div class="status-content">
                                <h3>Voting Unavailable</h3>
                                <p><?php echo htmlspecialchars($voting_status_message); ?></p>
                            </div>
                        </div>
                    <?php elseif (empty($candidates)): ?>
                        <div class="status-card pending">
                            <i class="fas fa-folder-open"></i>
                            <div class="status-content">
                                <h3>No Candidates</h3>
                                <p>There are no candidates available for this election yet.</p>
                            </div>
                        </div>
                    <?php else: ?>

                        <div class="info-card mb-4"
                            style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2);">
                            <div class="card-header" style="margin-bottom: 0.5rem;">
                                <i class="fas fa-info-circle text-success" style="color: var(--success);"></i>
                                <h3 style="color: var(--success);">Voting Instructions</h3>
                            </div>
                            <p style="color: var(--text-muted);">Please select one candidate for each position below. Once
                                you verify your choices, click the "Submit Vote" button at the bottom.</p>
                        </div>

                        <form method="POST" action="" id="votingForm">
                            <?php foreach ($candidates_by_position as $position => $candidates_list): ?>
                                <div class="position-group">
                                    <h3><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($position); ?></h3>
                                    <div class="candidates-list">
                                        <?php foreach ($candidates_list as $candidate): ?>
                                            <label class="candidate-card">
                                                <input type="radio" name="<?php echo htmlspecialchars($position); ?>"
                                                    value="<?php echo $candidate['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                                    data-position="<?php echo htmlspecialchars($position); ?>" required>
                                                <div class="candidate-info">
                                                    <img src="<?php echo !empty($candidate['photo_path']) ? fixCandidatePhotoPath($candidate['photo_path'], '../') : '../assets/img/default-avatar.png'; ?>"
                                                        alt="Candidate" class="candidate-photo"
                                                        onerror="this.src='pic/srclogo.png'">
                                                    <div class="candidate-name">
                                                        <?php echo htmlspecialchars($candidate['full_name']); ?>
                                                    </div>
                                                    <div class="candidate-party">
                                                        <?php echo htmlspecialchars($candidate['partylist'] ?? 'Independent'); ?>
                                                    </div>
                                                    <div class="platform-preview">
                                                        <?php echo htmlspecialchars($candidate['platform']); ?>
                                                    </div>
                                                    <button type="button" class="view-platform-btn"
                                                        onclick="showBufferModal('<?php echo htmlspecialchars($candidate['full_name']); ?>', '<?php echo htmlspecialchars(addslashes($candidate['platform'])); ?>')">
                                                        Read Platform
                                                    </button>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="vote-submit-container">
                                <button type="button" class="premium-btn btn" onclick="confirmVote()">
                                    <i class="fas fa-paper-plane"></i> Submit Vote
                                </button>
                            </div>

                            <!-- Hidden submit button for form submission after verification -->
                            <button type="submit" name="submit_vote" id="realSubmitBtn" style="display: none;"></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Modal -->
    <div id="platformModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalCandidateName">Candidate Name</h3>
                <button type="button" class="close-modal" onclick="closeModal('platformModal')">&times;</button>
            </div>
            <div id="modalPlatformContent" style="white-space: pre-wrap; color: var(--text-dim); line-height: 1.6;">
            </div>
        </div>
    </div>

    <!-- Review Vote Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Review Your Votes</h3>
                <button type="button" class="close-modal" onclick="closeModal('reviewModal')">&times;</button>
            </div>
            <p style="color: var(--text-muted); margin-bottom: 1rem;">Please double check your selections before
                submitting.</p>
            <ul class="review-list" id="reviewList"></ul>
            <div class="modal-header"
                style="border-bottom: none; border-top: 1px solid var(--border); padding-top: 1rem; margin-bottom: 0;">
                <button type="button" class="premium-btn" onclick="submitFinalVote()" style="width: 100%;">Confirm &
                    Submit</button>
            </div>
        </div>
    </div>

    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('student'); ?>

    <script>
        function showBufferModal(name, platform) {
            document.getElementById('modalCandidateName').innerText = name;
            document.getElementById('modalPlatformContent').innerText = platform || "No platform information provided.";
            document.getElementById('platformModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function confirmVote() {
            const form = document.getElementById('votingForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const reviewList = document.getElementById('reviewList');
            reviewList.innerHTML = '';

            const positionGroups = document.querySelectorAll('.position-group');
            let hasSelection = false;

            positionGroups.forEach(group => {
                const position = group.querySelector('h3').innerText.trim();
                const selected = group.querySelector('input[type="radio"]:checked');

                const li = document.createElement('li');
                if (selected) {
                    li.innerHTML = `<strong>${position}</strong> <span>${selected.dataset.name}</span>`;
                    hasSelection = true;
                } else {
                    li.innerHTML = `<strong>${position}</strong> <span style="color: var(--danger);">No selection</span>`;
                }
                reviewList.appendChild(li);
            });

            document.getElementById('reviewModal').classList.add('show');
        }

        function submitFinalVote() {
            document.getElementById('realSubmitBtn').click();
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>

</html>