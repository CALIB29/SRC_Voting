<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Get all registered students; use email column as gmail alias for display
try {
    $users = $pdo->query("SELECT student_id, first_name, middle_name, last_name, email AS gmail, has_voted FROM students ORDER BY first_name, middle_name, last_name")
                 ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load users: " . $e->getMessage();
    $users = array();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Santa Rita College Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- EmailJS SDK -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script type="text/javascript">
        (function(){
            emailjs.init("OIX5KA4zIfVfAWlsV"); // Your public key
        })();
    </script>

    <style>
        /* Gmail column styling */
        .gmail-cell {
            max-width: 200px;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .gmail-text {
            color: #333;
            font-size: 13px;
        }

        .no-gmail {
            color: #999;
            font-style: italic;
        }

        /* Loading spinner for email sending */
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Confirmation Modal Styles */
        .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .confirmation-modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 550px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: modalopen 0.3s ease-out;
        }

        @keyframes modalopen {
            from { opacity: 0; transform: translateY(-50px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .confirmation-modal-header {
            padding: 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
            border-radius: 12px 12px 0 0;
        }

        .confirmation-modal-header.approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .confirmation-modal-header.reject {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
        }

        .confirmation-modal-body {
            padding: 30px 25px;
            text-align: center;
        }

        .confirmation-modal-footer {
            padding: 25px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            background: #f8fafc;
            border-radius: 0 0 12px 12px;
        }

        .modal-icon {
            font-size: 28px;
        }

        .modal-title {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .email-preview {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: left;
        }

        .email-preview strong {
            color: #1e40af;
        }

        .confirm-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .confirm-btn.approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .confirm-btn.approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .confirm-btn.reject {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }

        .confirm-btn.reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }

        .confirm-btn.cancel {
            background: #6b7280;
            color: white;
        }

        .confirm-btn.cancel:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .confirm-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

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

        /* Top Navigation Bar */
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

        /* Main Content Layout */
        .admin-main-content {
            display: flex;
            flex: 1;
        }

        /* Sidebar */
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
            background-color: var(--gray-light);
            color: gray;
        }

        .admin-sidebar a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .admin-sidebar .active a {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--light-color);
            font-weight: 600;
        }

        .sidebar-header {
            padding: 0 1.25rem 1.25rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-medium);
            font-weight: 600;
        }

        /* Content Area */
        .admin-content-area {
            flex: 1;
            padding: 2rem;
            background-color: #f3f4f6;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Table Styles */
        .user-management {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Status Styles */
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status.approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status.voted {
            background-color: #d4edda;
            color: #155724;
        }

        .status.not-voted {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Button Styles */
        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            transition: var(--transition);
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn.approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
        }

        .btn.approve:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn.reject {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
        }

        .btn.reject:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .actions {
            white-space: nowrap;
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

        /* Email status messages */
        .email-status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
        }

        .email-status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .email-status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Content Header */
        .content-header {
            margin-bottom: 2rem;
        }

        .content-header h2 {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .admin-sidebar {
                width: 240px;
            }

            .hide-lg {
                display: none;
            }
        }

        @media (max-width: 992px) {
            .admin-sidebar {
                width: 80px;
                padding: 1rem 0;
                overflow: hidden;
            }

            .admin-sidebar a span,
            .sidebar-header {
                display: none;
            }

            .admin-sidebar a {
                justify-content: center;
                padding: 0.75rem 0;
            }

            .hide-md {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .admin-top-bar {
                padding: 0 1rem;
            }

            .admin-content-area {
                padding: 1.5rem;
            }

            .user-management {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }

            .hide-sm {
                display: none;
            }

            .confirmation-modal-content {
                width: 95%;
                margin: 5% auto;
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

            .hide-xs {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="admin-dashboard-container">
    <!-- Top Bar -->
    <div class="admin-top-bar">
        <div class="logo">
            <i class="fas fa-vote-yea"></i>
            <span>Santa Rita College</span>
        </div>
        <div class="admin-info">
            <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="admin-main-content">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <center><div class="logo">
                <img src="../pic/srclogo.png" alt="Voting System Logo">
                <span style="color: white">Voting System</span>
            </div></center>
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="active">
                    <a href="manage_users.php">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li>
                    <a href="manage_candidates.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Manage Candidates</span>
                    </a>
                </li>
                <li>
                    <a href="manage_history.php">
                        <i class="fas fa-history"></i>
                        <span>Manage history</span>
                    </a>
                </li>
                <li>
                    <a href="results.php">
                        <i class="fas fa-chart-pie"></i>
                        <span>Results Analytics</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="admin-content-area">
            <!-- Content Header -->
            <div class="content-header">
                <h2>
                    <i class="fas fa-users-cog"></i>
                    Manage Users
                </h2>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Dynamic alert for AJAX responses -->
            <div id="dynamicAlert" style="display: none;"></div>

            <div class="user-management">
                <table id="usersTable">
                    <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>First Name</th>
                        <th class="hide-sm">Middle Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th class="hide-sm">Voted</th>
                    </tr>
                    </thead>
                    <tbody id="usersTableBody">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                <td class="hide-sm"><?php echo htmlspecialchars($user['middle_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                <td class="gmail-cell">
                                    <?php if (!empty($user['gmail'])): ?>
                                        <span class="gmail-text"><?php echo htmlspecialchars($user['gmail']); ?></span>
                                    <?php else: ?>
                                        <span class="no-gmail">No email provided</span>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-sm">
                                    <span class="status <?php echo $user['has_voted'] ? 'voted' : 'not-voted'; ?>">
                                        <?php echo $user['has_voted'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; font-style: italic; color: #6b7280;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
