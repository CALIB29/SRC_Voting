<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ------ LOGIC 1: Kunin ang ID ng kasalukuyang active na election. ------
$active_election_id = $pdo->query("SELECT id FROM elections WHERE is_active = 1")->fetchColumn();


// ------ LOGIC 2: Kunin lang ang mga kandidato para sa ACTIVE na election. ------
$candidates = [];
if ($active_election_id) {
    $stmt = $pdo->prepare("
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- ANG BUONG CSS MO - HINDI GINALAW -->
    <style>
        :root {
            --primary: #3B82F6;
            --primary-glow: rgba(59, 130, 246, 0.2);
            --secondary: #6366f1;
            --bg-sidebar: #ffffff;
            --bg-body: #f8fafc;
            --radius-lg: 20px;
            --radius-md: 12px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
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
            gap: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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

        :root {
            --primary: #4361ee;
            --primary-light: #eef1fd;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --success-light: #e8f8fd;
            --success-dark: #3ab7e0;
            --info: #4895ef;
            --info-light: #e8f2fe;
            --warning: #f72585;
            --warning-light: #fdebf3;
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

        .user-info .user-avatar {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .user-info span {
            margin-right: 15px;
            font-weight: 500;
            color: var(--dark);
        }

        .logout-btn {
            padding: 10px 20px;
        }

        .content-area {
            padding: 30px;
            flex: 1;
        }

        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .welcome-header h2 {
            color: var(--dark);
            font-weight: 700;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }

        .welcome-header h2 i {
            margin-right: 12px;
            color: var(--primary);
        }

        .time-date {
            background: white;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            font-weight: 500;
            color: var(--gray);
        }

        .alert {
            padding: 20px 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            background-color: white;
            border-left: 4px solid transparent;
            transition: var(--transition);
        }

        .alert:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .alert.success {
            border-left-color: var(--success);
            background-color: var(--success-light);
            color: var(--success-dark);
        }

        .alert.info {
            border-left-color: var(--info);
            background-color: var(--info-light);
            color: var(--info);
        }

        .alert i {
            margin-right: 15px;
            font-size: 1.5rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content p {
            margin-bottom: 5px;
        }

        .alert-content a {
            color: inherit;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
        }

        .alert-content a:hover {
            text-decoration: underline;
        }

        .alert-content a i {
            margin-left: 5px;
            font-size: 0.9rem;
        }

        .user-details {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .user-details:hover {
            box-shadow: var(--box-shadow-lg);
        }

        .user-details h3 {
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
            font-weight: 600;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }

        .user-details h3 i {
            margin-right: 12px;
            color: var(--primary);
        }

        .user-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .detail-value {
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .quick-actions {
            margin-top: 40px;
        }

        .quick-actions h3 {
            color: var(--dark);
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }

        .quick-actions h3 i {
            margin-right: 12px;
            color: var(--primary);
        }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            text-align: center;
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .action-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .action-card h4 {
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .action-card p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        @media (max-width: 1200px) {
            .user-details-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggler {
                display: block !important;
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

            .welcome-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .time-date {
                margin-top: 15px;
                width: 100%;
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

            .action-cards {
                grid-template-columns: 1fr;
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

        .alert,
        .user-details,
        .quick-actions {
            animation: fadeIn 0.5s ease forwards;
        }

        .alert {
            animation-delay: 0.1s;
        }

        .user-details {
            animation-delay: 0.2s;
        }

        .quick-actions {
            animation-delay: 0.3s;
        }

        .candidate-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .candidate-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px -10px rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .candidate-img-wrapper {
            position: relative;
            padding-top: 100%;
            overflow: hidden;
            background: #f1f5f9;
        }

        .candidate-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .candidate-card:hover .candidate-img {
            transform: scale(1.08);
        }

        .candidate-info {
            padding: 1.5rem;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 12px;
        }

        .candidate-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            transition: var(--transition);
            line-height: 1.3;
        }

        .candidate-card:hover .candidate-name {
            color: var(--primary);
        }

        .position-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(59, 130, 246, 0.05);
            margin: 0 auto;
        }

        .modal-content {
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        .modal-img {
            max-height: 350px;
            object-fit: cover;
            width: 100%;
        }

        .sidebar-toggler {
            display: none;
            position: fixed;
            left: 15px;
            top: 15px;
            z-index: 1050;
            background: var(--primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.2rem;
        }

        .page-title {
            font-weight: 600;
            color: #333;
        }

        .container-fluid {
            padding: 30px;
        }

        h2 {
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .card-body {
            padding: 20px;
        }

        .modal-header {
            background-color: var(--primary);
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .platform-card {
            border-left: 4px solid var(--primary);
        }
    </style>

<body>
    <button class="sidebar-toggler" id="sidebarToggler"><i class="fas fa-bars"></i></button>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="pic/srclogo.png" alt="Santa Rita College Logo">
                <h5>Santa Rita College</h5>
            </div>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote Now</a></li>
                <li class="active"><a href="view.php"><i class="fas fa-users"></i> View Candidates</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">Student Portal</div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo isset($_SESSION['first_name']) ? substr($_SESSION['first_name'], 0, 1) : 'U'; ?>
                    </div>
                    <span><?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'First_name'; ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <div class="container-fluid">
                <h2 class="mb-4"><i class="fas fa-users me-2"></i> Election Candidates</h2>

                <?php if (empty($candidates_by_position)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No candidates found for the current election. Please check back later.
                    </div>
                <?php else: ?>
                    <!-- Gin-amit ko ang $ordered_candidates_by_position para sa tamang pagkakasunod-sunod -->
                    <?php foreach ($candidates_by_position as $position => $candidates_list): ?>
                        <h3 class="mt-4 mb-3 text-uppercase text-primary"><i
                                class="fas fa-user-tie me-2"></i><?= htmlspecialchars($position) ?></h3>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
                            <?php foreach ($candidates_list as $row): ?>
                                <?php
                                $full_name = trim($row['first_name'] . ' ' . (!empty($row['middle_name']) ? $row['middle_name'] . ' ' : '') . $row['last_name']);
                                ?>
                                <div class="col">
                                    <div class="candidate-card" data-bs-toggle="modal" data-bs-target="#candidateModal"
                                        data-name="<?= htmlspecialchars($full_name) ?>"
                                        data-photo="<?= htmlspecialchars($row['photo_path']) ?>"
                                        data-platform="<?= htmlspecialchars($row['platform']) ?>"
                                        data-position="<?= htmlspecialchars($row['position']) ?>">
                                        <div class="candidate-img-wrapper">
                                            <img src="<?= htmlspecialchars($row['photo_path']) ?>" class="candidate-img"
                                                alt="<?= htmlspecialchars($full_name) ?>">
                                        </div>
                                        <div class="candidate-info">
                                            <h5 class="candidate-name">
                                                <?= htmlspecialchars($full_name) ?>
                                            </h5>
                                            <span class="position-badge"><?= htmlspecialchars($row['position']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Candidate Modal - ETO NA PO ANG BUONG MODAL, HINDI NA INIKLI -->
    <div class="modal fade" id="candidateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="candidateModalLabel">Candidate Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <img id="modalImage" src="" class="img-fluid rounded modal-img" alt="Candidate">
                        </div>
                        <div class="col-md-7">
                            <h3 id="modalName" class="mb-2"></h3>
                            <p class="text-primary mb-4" id="modalPosition"></p>
                            <div class="card platform-card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Campaign Platform</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0" id="modalPlatform"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- IYONG BUONG JAVASCRIPT - HINDI GINALAW -->
    <script>
        document.querySelectorAll('[data-bs-target="#candidateModal"]').forEach(el => {
            el.addEventListener('click', function () {
                const name = this.getAttribute('data-name');
                const photo = this.getAttribute('data-photo');
                const platform = this.getAttribute('data-platform');
                const position = this.getAttribute('data-position');

                document.getElementById('modalName').textContent = name;
                document.getElementById('modalImage').src = photo;
                document.getElementById('modalPlatform').textContent = platform;
                document.getElementById('modalPosition').textContent = position;
            });
        });

        document.getElementById('sidebarToggler').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
        });

        document.addEventListener('click', function (event) {
            const sidebar = document.getElementById('sidebar');
            const toggler = document.getElementById('sidebarToggler');
            if (window.innerWidth <= 992 && !sidebar.contains(event.target) && event.target !== toggler && !toggler.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>

</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>