<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            background-color: white;
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
            color: var(--gray-dark);
            text-decoration: none;
            transition: var(--transition);
            gap: 0.75rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .admin-sidebar a:hover {
            background-color: var(--light-color);
            color: var(--primary-color);
        }
        
        .admin-sidebar a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .admin-sidebar .active a {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
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
        
        .content-actions {
            display: flex;
            gap: 1rem;
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
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--gray-light);
        }
        
        .btn-outline:hover {
            background-color: var(--light-color);
            border-color: var(--gray-medium);
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-md);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: var(--gray-medium);
            margin-bottom: 0.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-change {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            gap: 0.25rem;
        }
        
        .stat-card .stat-change.positive {
            color: var(--success-color);
        }
        
        .stat-card .stat-change.negative {
            color: var(--danger-color);
        }
        
        /* Different card colors */
        .stat-card:nth-child(2)::before {
            background-color: var(--success-color);
        }
        
        .stat-card:nth-child(3)::before {
            background-color: var(--warning-color);
        }
        
        .stat-card:nth-child(4)::before {
            background-color: var(--accent-color);
        }
        
        .stat-card:nth-child(5)::before {
            background-color: var(--secondary-color);
        }
        
        /* Recent Activity Section */
        .recent-activity {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-header h3 {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.25rem;
        }
        
        .section-header a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(79, 70, 229, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            flex-shrink: 0;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
            color: var(--dark-color);
        }
        
        .activity-description {
            font-size: 0.875rem;
            color: var(--gray-medium);
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            font-size: 0.75rem;
            color: var(--gray-medium);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        /* Responsive Design */
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
            .sidebar-header {
                display: none;
            }
            
            .admin-sidebar a {
                justify-content: center;
                padding: 0.75rem 0;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
            
            .content-actions {
                width: 100%;
                justify-content: flex-end;
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
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .admin-info span {
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
                <span>VoteAdmin Pro</span>
            </div>
            <div class="admin-info">
                <span><?php echo $_SESSION['admin_name']; ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <div class="admin-main-content">
            <!-- Sidebar -->
            <div class="admin-sidebar">
                <div class="sidebar-header">Navigation</div>
                <ul>
                    <li class="active">
                        <a href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
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
                        <a href="vot_elections.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Elections</span>
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
            </div>
        </div>
    </div>
</body>
</html>