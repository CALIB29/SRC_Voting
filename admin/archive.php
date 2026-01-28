<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Kunin lang ang mga naka-ARCHIVE na halalan (is_active = 0)
$stmt = $pdo->prepare("SELECT * FROM elections WHERE is_active = 0 ORDER BY created_at DESC");
$stmt->execute();
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Archives</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* ----- KINOPYA ANG BUONG CSS MULA SA RESULTS.PHP PARA SIGURADONG PAREHO ----- */
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

        .content-header h2 {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1.75rem;
        }

        .table-container {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .table-container h3 {
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

        /* Para sa logo sa sidebar */
        .logo img {
            max-width: 100%;
            height: auto;
            max-height: 80px;
        }

        /* Para sa logo sa sidebar */
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
            text-decoration: none;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-view:hover {
            background-color: var(--primary-dark);
        }

        .status-archived {
            color: var(--gray-medium);
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="admin-dashboard-container">
        <!-- TOP BAR (Eksaktong kopya) -->
        <div class="admin-top-bar">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <span>Santa Rita College</span>
            </div>
            <div class="admin-info">
                <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>

        <div class="admin-main-content">
            <!-- SIDEBAR (Eksaktong kopya ang structure at classes) -->
            <div class="admin-sidebar">
                <center>
                    <div class="logo">
                        <img src="../pic/srclogo.png" alt="Voting System Logo">
                        <span style="color: white">Voting System</span>
                    </div>
                </center>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users-cog"></i><span>Manage Users</span></a></li>
                    <li><a href="manage_candidates.php"><i class="fas fa-user-tie"></i><span>Manage
                                Candidates</span></a></li>

                    <li><a href="manage_history.php"><i class="fas fa-history"></i><span>Manage history</span></a></li>
                    <li><a href="results.php"><i class="fas fa-chart-pie"></i><span>Results Analytics</span></a></li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="admin-content-area">
                <div class="content-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Election Archives</h2>
                    <a href="results.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Current
                        Results</a>
                </div>

                <p style="margin-bottom: 2rem; color: var(--gray-medium);">Here is a list of all finished and archived
                    elections.</p>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Election Name</th>
                                <th>Date Archived</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($elections)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--gray-medium);">
                                        <i class="fas fa-box-open fa-2x"
                                            style="margin-bottom: 1rem; color: var(--gray-medium);"></i><br>
                                        No archived elections found yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($elections as $election): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($election['title']); ?></strong></td>
                                        <td><?php echo date("F j, Y, g:i a", strtotime($election['created_at'])); ?></td>
                                        <td>
                                            <span class="status-archived"><i class="fas fa-archive"></i> Archived</span>
                                        </td>
                                        <td>
                                            <a href="results.php?id=<?php echo $election['id']; ?>" class="btn btn-view">
                                                <i class="fas fa-eye"></i> View Results
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>