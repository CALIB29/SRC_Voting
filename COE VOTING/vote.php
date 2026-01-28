<?php
session_start();

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ------ LOGIC 1: Get the ID of the currently active election. ------
$active_election_id = $pdo->query("SELECT id FROM elections WHERE is_active = 1")->fetchColumn();

// ------ LOGIC 2: Check if the user has already voted. ------
$user_id = $_SESSION['user_id']; // students.student_id
$stmt_user_voted = $pdo->prepare("SELECT has_voted FROM students WHERE student_id = ?");
$stmt_user_voted->execute([$user_id]);
$user = $stmt_user_voted->fetch();

if ($user && $user['has_voted']) {
    header("Location: dashboard.php");
    exit;
}

// ------ LOGIC 3: Check the voting schedule. ------
$schedule_stmt = $pdo->query("SELECT start_datetime, end_datetime FROM voting_schedule LIMIT 1");
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
        FROM candidates
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
            $stmt_insert = $pdo->prepare("INSERT INTO votes (user_id, candidate_id, position, election_id) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$user_id, $candidate_id, $position, $active_election_id]);
            $stmt_update = $pdo->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ?");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #eef1fd;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --success-light: #e8f8fd;
            --danger: #f72585;
            --danger-light: #fdebf3;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.03);
            padding: 20px 0;
            transition: var(--transition);
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 100%;
            height: auto;
            max-height: 70px;
            margin-bottom: 10px;
        }

        .logo h5 {
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 5px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0 15px;
        }

        .sidebar li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            border-radius: var(--border-radius-sm);
            margin-bottom: 5px;
            font-weight: 500;
        }

        .sidebar li a:hover {
            background-color: var(--primary-light);
            color: var(--primary);
            transform: translateX(3px);
        }

        .sidebar li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar li.active a {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--primary);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-left: 280px;
        }

        .top-bar {
            background: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.03);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
            font-size: 0.9rem;
        }

        .user-info span {
            margin-right: 15px;
            font-weight: 500;
            color: var(--dark);
        }

        .logout-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .logout-btn i {
            margin-right: 8px;
        }

        .logout-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
        }

        .content-area {
            padding: 30px;
            flex: 1;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .content-header h2 {
            color: var(--dark);
            font-weight: 700;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }

        .content-header h2 i {
            margin-right: 12px;
            color: var(--primary);
        }

        .alert {
            padding: 16px 20px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            background-color: white;
            border-left: 4px solid transparent;
        }

        .alert.error {
            border-left-color: var(--danger);
            background-color: var(--danger-light);
            color: #721c24;
        }

        .alert.warning {
            border-left-color: var(--warning);
            background-color: rgba(248, 150, 30, 0.1);
            color: #856404;
        }

        .alert.success {
            border-left-color: var(--info);
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }

        .alert i {
            margin-right: 12px;
            font-size: 1.3rem;
        }

        .voting-instructions {
            background-color: white;
            padding: 20px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            border-left: 4px solid var(--info);
            background-color: var(--primary-light);
            border-left-color: var(--primary);
        }

        .voting-instructions p {
            display: flex;
            align-items: center;
            color: var(--dark);
            font-weight: 500;
            margin-bottom: 0;
        }

        .voting-instructions i {
            margin-right: 12px;
            color: var(--primary);
            font-size: 1.2rem;
        }

        .voting-form {
            margin-top: 20px;
        }

        .position-group {
            background-color: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            transition: var(--transition);
            animation: fadeIn 0.5s ease forwards;
        }

        .position-group.disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        .position-group:hover {
            box-shadow: var(--box-shadow-lg);
        }

        .position-group h3 {
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
            font-weight: 600;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }

        .position-group h3 i {
            margin-right: 10px;
            color: var(--primary);
        }

        .candidates-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .candidate-card {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
        }

        .candidate-card:hover {
            transform: translateY(-5px);
        }

        .candidate-card label {
            display: block;
            cursor: pointer;
        }

        .candidate-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .candidate-card input[type="radio"]:checked+.candidate-info {
            border: 2px solid var(--primary);
            background-color: var(--primary-light);
        }

        .candidate-card input[type="radio"]:checked+.candidate-info::after {
            content: '';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 15px;
            right: 15px;
            color: var(--primary);
            font-size: 1.5rem;
            background: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .candidate-info {
            display: flex;
            border: 2px solid var(--light-gray);
            border-radius: var(--border-radius);
            padding: 20px;
            transition: var(--transition);
            position: relative;
            height: 100%;
            background: white;
        }

        .candidate-photo-container {
            position: relative;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .candidate-photo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--light-gray);
            transition: var(--transition);
        }

        .candidate-card:hover .candidate-photo {
            transform: scale(1.05);
            border-color: var(--primary);
        }

        .candidate-details {
            flex: 1;
        }

        .candidate-details h4 {
            color: var(--dark);
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .candidate-party {
            display: inline-block;
            background-color: var(--primary-light);
            color: var(--primary);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .platform {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .platform-title {
            font-weight: 500;
            color: var(--dark);
            margin-top: 8px;
            display: block;
        }

        .vote-submit {
            text-align: center;
            margin-top: 40px;
            position: sticky;
            bottom: 30px;
            z-index: 5;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 200px;
        }

        .btn i {
            margin-right: 10px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            background-color: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .progress-indicator {
            background: white;
            padding: 15px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
        }

        .progress-text {
            margin-right: 20px;
            font-weight: 500;
            color: var(--dark);
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background-color: var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--primary);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--gray);
            font-weight: 500;
            margin-bottom: 10px;
        }

        /* New Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 0;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-lg);
            animation: fadeIn 0.3s;
        }

        .modal-header {
            padding: 15px 25px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--dark);
            display: flex;
            align-items: center;
        }

        .close-modal-btn {
            color: var(--gray);
            font-size: 1.8rem;
            font-weight: bold;
            background: none;
            border: none;
            cursor: pointer;
        }

        .close-modal-btn:hover,
        .close-modal-btn:focus {
            color: #000;
            text-decoration: none;
        }

        .modal-body {
            padding: 20px 25px;
        }

        .modal-body p {
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .review-list {
            list-style: none;
            padding: 0;
            max-height: 40vh;
            overflow-y: auto;
        }

        .review-list li {
            padding: 12px;
            background: var(--light-gray);
            border-radius: var(--border-radius-sm);
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .review-list li strong {
            color: var(--primary-dark);
            display: block;
            margin-bottom: 4px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn.btn-secondary {
            background-color: var(--gray);
            box-shadow: none;
            color: white;
        }

        .btn.btn-secondary:hover {
            background-color: var(--dark);
        }

        @media (max-width: 1200px) {
            .candidates-list {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
            }

            .main-content {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 10px 0;
            }

            .sidebar ul {
                display: flex;
                overflow-x: auto;
                padding: 0 10px;
            }

            .sidebar li {
                flex: 0 0 auto;
            }

            .sidebar li a {
                padding: 10px 15px;
                border-left: none;
                border-bottom: 3px solid transparent;
                white-space: nowrap;
            }

            .sidebar li.active a {
                border-left: none;
                border-bottom: 3px solid var(--primary);
            }

            .main-content {
                margin-left: 0;
            }

            .candidate-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 25px 15px;
            }

            .candidate-photo-container {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .candidate-card input[type="radio"]:checked+.candidate-info::after {
                top: 10px;
                right: 10px;
            }
        }

        @media (max-width: 576px) {
            .content-area {
                padding: 20px;
            }

            .top-bar {
                padding: 15px 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .user-info {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
            }

            .position-group {
                padding: 20px 15px;
            }

            .candidates-list {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
                padding: 14px;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .position-group {
            animation: fadeIn 0.5s ease forwards;
        }

        .position-group:nth-child(1) {
            animation-delay: 0.1s;
        }

        .position-group:nth-child(2) {
            animation-delay: 0.2s;
        }

        .position-group:nth-child(3) {
            animation-delay: 0.3s;
        }

        .position-group:nth-child(4) {
            animation-delay: 0.4s;
        }

        .position-group:nth-child(5) {
            animation-delay: 0.5s;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo"><img src="pic/srclogo.png" alt="SRC Logo">
                <h5>Santa Rita College</h5>
            </div>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="active"><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote Now</a></li>
                <li><a href="view.php"><i class="fas fa-users"></i> View Candidates</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">Student Portal</div>
                <div class="user-info">
                    <div class="user-avatar"><?php echo substr($_SESSION['first_name'], 0, 1); ?></div>
                    <span><?php echo $_SESSION['first_name']; ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            <div class="content-area">
                <div class="content-header">
                    <h2><i class="fas fa-vote-yea"></i> Cast Your Vote</h2>
                    <div class="progress-indicator">
                        <div class="progress-text">0 of <?php echo count($candidates_by_position); ?> positions
                            completed</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- ANNOUNCEMENT/STATUS BAR  -->
                <?php if (isset($error)): ?>
                    <div class="alert error"><i class="fas fa-exclamation-circle"></i><span><?php echo $error; ?></span>
                    </div>
                <?php elseif (!empty($voting_status_message)): ?>
                    <div class="alert <?php echo $voting_allowed ? 'success' : 'warning'; ?>">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $voting_status_message; ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" class="voting-form" id="votingForm">
                    <?php if (empty($candidates_by_position)): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h4>No Candidates Available</h4>
                            <p>There are currently no candidates for the active election. Please check back later.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($candidates_by_position as $position => $position_candidates): ?>
                            <div class="position-group <?php if (!$voting_allowed)
                                echo 'disabled'; ?>">
                                <h3><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($position); ?></h3>
                                <div class="candidates-list">
                                    <?php foreach ($position_candidates as $candidate): ?>
                                        <div class="candidate-card">
                                            <label>
                                                <input type="radio" name="<?php echo htmlspecialchars($position); ?>"
                                                    value="<?php echo $candidate['id']; ?>"
                                                    data-position-name="<?php echo htmlspecialchars($position); ?>"
                                                    data-candidate-name="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                                    required <?php if (!$voting_allowed)
                                                        echo 'disabled'; ?>>
                                                <div class="candidate-info">
                                                    <div class="candidate-photo-container">
                                                        <img src="<?php echo htmlspecialchars($candidate['photo_path']); ?>"
                                                            alt="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                                            class="candidate-photo">
                                                    </div>
                                                    <div class="candidate-details">
                                                        <h4><?php echo htmlspecialchars($candidate['full_name']); ?></h4>
                                                        <?php if (!empty($candidate['party'])): ?>
                                                            <span
                                                                class="candidate-party"><?php echo htmlspecialchars($candidate['party']); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($candidate['platform'])): ?>
                                                            <span class="platform-title">Platform:</span>
                                                            <p class="platform">
                                                                <?php echo nl2br(htmlspecialchars($candidate['platform'])); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($voting_allowed): ?>
                            <div class="vote-submit">
                                <button type="button" class="btn" id="submitVote">
                                    <i class="fas fa-paper-plane"></i> Review & Submit Votes
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Review and Confirm Modal -->
    <div id="reviewModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-tasks"></i> Review Your Selections</h3>
                <button class="close-modal-btn" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Please review your choices below. Once submitted, your vote cannot be changed.</p>
                <ul id="reviewList" class="review-list">
                    <!-- Selections will be dynamically inserted here by JavaScript -->
                </ul>
            </div>
            <div class="modal-footer">
                <button id="cancelReviewBtn" class="btn btn-secondary"><i class="fas fa-edit"></i> Go Back &
                    Edit</button>
                <button id="confirmSubmitBtn" class="btn"><i class="fas fa-check-circle"></i> Confirm & Submit
                    Vote</button>
            </div>
        </div>
    </div>

    <script>
        <?php if ($voting_allowed): ?>
            // --- PART 1: PROGRESS BAR UPDATE LOGIC ---
            const totalPositions = <?php echo count($candidates_by_position); ?>;
            const progressText = document.querySelector('.progress-text');
            const progressFill = document.querySelector('.progress-fill');

            function updateProgress() {
                const votedPositions = document.querySelectorAll('.position-group input[type="radio"]:checked').length;
                const percentage = totalPositions > 0 ? (votedPositions / totalPositions) * 100 : 0;

                if (progressText) {
                    progressText.textContent = `${votedPositions} of ${totalPositions} positions completed`;
                }
                if (progressFill) {
                    progressFill.style.width = `${percentage}%`;
                }
            }

            document.querySelectorAll('.candidate-card input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', updateProgress);
            });

            // --- PART 2: REVIEW MODAL LOGIC ---
            const votingForm = document.getElementById('votingForm');
            const submitBtn = document.getElementById('submitVote');
            const reviewModal = document.getElementById('reviewModal');
            const reviewList = document.getElementById('reviewList');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const cancelReviewBtn = document.getElementById('cancelReviewBtn');
            const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');

            submitBtn.addEventListener('click', function (e) {
                e.preventDefault();

                // Check if form is valid (all required fields are filled)
                if (!votingForm.checkValidity()) {
                    alert('Please select a candidate for every position before submitting.');
                    votingForm.reportValidity(); // This will show the browser's default validation bubbles
                    return;
                }

                // Clear previous review items
                reviewList.innerHTML = '';

                // Get all selected candidates
                const selectedCandidates = document.querySelectorAll('.position-group input[type="radio"]:checked');

                // Populate the review list
                selectedCandidates.forEach(radio => {
                    const position = radio.getAttribute('data-position-name');
                    const candidateName = radio.getAttribute('data-candidate-name');

                    const listItem = document.createElement('li');
                    listItem.innerHTML = `<strong>${position}</strong> ${candidateName}`;
                    reviewList.appendChild(listItem);
                });

                // Show the modal
                reviewModal.classList.add('active');
            });

            function closeModal() {
                reviewModal.classList.remove('active');
            }

            closeModalBtn.addEventListener('click', closeModal);
            cancelReviewBtn.addEventListener('click', closeModal);

            // Finally, submit the form when confirmed
            confirmSubmitBtn.addEventListener('click', function () {
                votingForm.submit();
            });

        <?php endif; ?>
    </script>
</body>

</html>