<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Initialize message variables
$message = '';
$error = '';

// Check if elections table exists and get active election
$active_election_id = null;
try {
    // First check if elections table exists
    $table_exists = $pdo->query("SHOW TABLES LIKE 'elections'")->fetchColumn();
    
    if ($table_exists) {
        $active_election_id = $pdo->query("SELECT id FROM elections WHERE is_active = 1")->fetchColumn();
    } else {
        // If no elections table, use a default value (backward compatibility)
        $active_election_id = 1; // Default election ID
        $error = "Elections table not found. Using default election. Please run database setup.";
    }
} catch (PDOException $e) {
    // If query fails, use default election ID
    $active_election_id = 1;
    $error = "Database query failed. Using default election: " . $e->getMessage();
}

// Handle add candidate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    if (!$active_election_id) {
        $error = "Cannot add candidate. No election is currently active. Please start a new election from the Results page first.";
    } else {
        $first_name  = sanitizeInput($_POST['first_name']);
        $middle_name = sanitizeInput($_POST['middle_name']);
        $last_name   = sanitizeInput($_POST['last_name']);
        $year        = sanitizeInput($_POST['year']);
        $section     = sanitizeInput($_POST['section']);
        $position    = sanitizeInput($_POST['position']);
        $platform    = sanitizeInput($_POST['platform']);

        $photo_path = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../' . UPLOAD_DIR . 'candidates/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_path = UPLOAD_DIR . 'candidates/' . uniqid() . '.' . $file_ext;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photo_path)) {
                $error = "Failed to upload candidate photo.";
                $photo_path = ''; // Clear path on failure
            }
        } else {
            $error = "Please upload a candidate photo.";
        }

        if (empty($error)) {
            try {
                // Check if election_id column exists in candidates table
                $column_exists = $pdo->query("SHOW COLUMNS FROM candidates LIKE 'election_id'")->fetchColumn();
                
                if ($column_exists) {
                    $stmt = $pdo->prepare("INSERT INTO candidates 
                        (first_name, middle_name, last_name, year, section, position, platform, photo_path, election_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$first_name, $middle_name, $last_name, $year, $section, $position, $platform, $photo_path, $active_election_id]);
                } else {
                    // If no election_id column, use the old query
                    $stmt = $pdo->prepare("INSERT INTO candidates 
                        (first_name, middle_name, last_name, year, section, position, platform, photo_path) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$first_name, $middle_name, $last_name, $year, $section, $position, $platform, $photo_path]);
                }
                $message = "Candidate added successfully.";
            } catch (PDOException $e) {
                $error = "Failed to add candidate: " . $e->getMessage();
            }
        }
    }
}

// Handle update candidate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_candidate'])) {
    $candidate_id = $_POST['candidate_id'];
    $first_name = sanitizeInput($_POST['edit_first_name']);
    $middle_name = sanitizeInput($_POST['edit_middle_name']);
    $last_name = sanitizeInput($_POST['edit_last_name']);
    $year = sanitizeInput($_POST['edit_year']);
    $section = sanitizeInput($_POST['edit_section']);
    $position = sanitizeInput($_POST['edit_position']);
    $platform = sanitizeInput($_POST['edit_platform']);
    $current_photo_path = $_POST['current_photo_path'];

    $photo_path = $current_photo_path;

    // Check if a new photo has been uploaded
    if (isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../' . UPLOAD_DIR . 'candidates/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['edit_photo']['name'], PATHINFO_EXTENSION);
        $new_photo_filename = uniqid() . '.' . $file_ext;
        $new_photo_path = UPLOAD_DIR . 'candidates/' . $new_photo_filename;

        if (move_uploaded_file($_FILES['edit_photo']['tmp_name'], '../' . $new_photo_path)) {
            // New photo uploaded successfully, delete the old one if it exists
            if (!empty($current_photo_path) && file_exists('../' . $current_photo_path)) {
                unlink('../' . $current_photo_path);
            }
            $photo_path = $new_photo_path; // Update to the new photo path
        } else {
            $error = "Failed to upload new candidate photo.";
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("UPDATE candidates SET 
                first_name = ?, middle_name = ?, last_name = ?, 
                year = ?, section = ?, position = ?, 
                platform = ?, photo_path = ? 
                WHERE id = ?");
            $stmt->execute([$first_name, $middle_name, $last_name, $year, $section, $position, $platform, $photo_path, $candidate_id]);
            $message = "Candidate updated successfully.";
        } catch (PDOException $e) {
            $error = "Failed to update candidate: " . $e->getMessage();
        }
    }
}

// Get candidates (with or without election filter)
$candidates = [];
try {
    // Check if election_id column exists
    $column_exists = $pdo->query("SHOW COLUMNS FROM candidates LIKE 'election_id'")->fetchColumn();
    
    if ($column_exists && $active_election_id) {
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE election_id = :election_id ORDER BY id DESC");
        $stmt->execute(['election_id' => $active_election_id]);
    } else {
        // If no election_id column or no active election, get all candidates
        $stmt = $pdo->prepare("SELECT * FROM candidates ORDER BY id DESC");
        $stmt->execute();
    }
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Query failed: " . $e->getMessage();
    $candidates = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #4f46e5; --primary-light: #6366f1; --primary-dark: #4338ca; --secondary-color: #7c3aed; --accent-color: #2563eb; --light-color: #f9fafb; --dark-color: #111827; --gray-dark: #374151; --gray-medium: #6b7280; --gray-light: #e5e7eb; --danger-color: #dc2626; --success-color: #10b981; --warning-color: #f59e0b; --border-radius: 12px; --border-radius-sm: 8px; --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); --box-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background-color: #f3f4f6; color: var(--dark-color); line-height: 1.5; }
        .admin-dashboard-container { display: flex; flex-direction: column; min-height: 100vh; }
        .admin-top-bar { background-color: white; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--box-shadow); position: sticky; top: 0; z-index: 100; height: 70px; }
        .logo { font-size: 1.25rem; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 0.75rem; }
        .logo i { font-size: 1.5rem; color: var(--primary-color); }
        .admin-info { display: flex; align-items: center; gap: 1.5rem; }
        .admin-info span { font-weight: 500; color: var(--gray-dark); font-size: 0.95rem; }
        .logout-btn { color: var(--gray-medium); text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: var(--transition); font-size: 0.9rem; padding: 0.5rem 1rem; border-radius: var(--border-radius-sm); }
        .logout-btn:hover { color: var(--danger-color); background-color: rgba(220, 38, 38, 0.1); }
        .admin-main-content { display: flex; flex: 1; }
        .admin-sidebar { width: 280px; background-color: rgb(54, 51, 112); padding: 1.5rem 0; box-shadow: var(--box-shadow); height: calc(100vh - 70px); position: sticky; top: 70px; transition: var(--transition); border-right: 1px solid var(--gray-light); }
        .admin-sidebar ul { list-style: none; padding: 0 1rem; }
        .admin-sidebar li { margin-bottom: 0.25rem; }
        .admin-sidebar a { display: flex; align-items: center; padding: 0.75rem 1.25rem; color: white; text-decoration: none; transition: var(--transition); gap: 0.75rem; border-radius: var(--border-radius-sm); font-weight: 500; font-size: 0.95rem; }
        .admin-sidebar a i { width: 20px; text-align: center; font-size: 1.1rem; }
        .admin-sidebar a:hover { background-color: var(--gray-light); color: gray; }
        .admin-sidebar .active a { background-color: rgba(79, 70, 229, 0.1); color: var(--light-color); font-weight: 600; }
        .sidebar-header { padding: 0 1.25rem 1.25rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-medium); font-weight: 600; }
        .admin-content-area { flex: 1; padding: 2rem; background-color: #f3f4f6; }
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .content-header h2 { color: var(--dark-color); font-weight: 700; font-size: 1.75rem; }
        .content-actions { display: flex; gap: 1rem; }
        .btn { padding: 0.625rem 1.25rem; border-radius: var(--border-radius-sm); font-weight: 500; font-size: 0.875rem; cursor: pointer; transition: var(--transition); border: none; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: var(--primary-dark); transform: translateY(-1px); box-shadow: var(--box-shadow); }
        .btn-outline { background-color: transparent; color: var(--primary-color); border: 1px solid var(--gray-light); }
        .btn-outline:hover { background-color: var(--light-color); border-color: var(--gray-medium); }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); margin-bottom: 20px; overflow: hidden; }
        .card-header { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { font-size: 18px; font-weight: 500; color: #2c3e50; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 20px; }
        .toggle-form-btn { background: #6c757d; border: none; color: white; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 16px; transition: all 0.3s; }
        .toggle-form-btn:hover { background: #5a6268; transform: rotate(180deg); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-group input[type="text"], .form-group textarea, .form-group select { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; transition: border-color 0.3s; }
        .form-group input[type="text"]:focus, .form-group textarea:focus, .form-group select:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .select-wrapper { position: relative; flex: 1; }
        .select-wrapper i { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #777; }
        .form-group.full-width { grid-column: span 2; }
        .form-actions { grid-column: span 2; display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
        .position-input-wrapper { display: flex; gap: 10px; align-items: flex-start; }
        .add-position-btn { padding: 10px 15px; height: 42px; display: flex; align-items: center; justify-content: center; min-width: 42px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; transition: all 0.3s; }
        .add-position-btn:hover { background-color: #5a6268; transform: translateY(-1px); }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
        .file-upload .upload-container { display: flex; align-items: center; gap: 10px; }
        .file-upload input[type="file"] { display: none; }
        .file-upload .upload-btn { padding: 8px 15px; background: #e9ecef; border: 1px dashed #adb5bd; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .file-upload .upload-btn:hover { background: #dee2e6; border-color: #6c757d; }
        .file-name { color: #6c757d; font-size: 14px; }
        .table-responsive { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #f8f9fa; padding: 12px 15px; text-align: left; font-weight: 600; color: #495057; border-bottom: 2px solid #dee2e6; }
        .data-table td { padding: 12px 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background-color: #f8f9fa; }
        .candidate-photo { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin: 0 auto; }
        .candidate-photo img { width: 100%; height: 100%; object-fit: cover; }
        .position-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; text-transform: uppercase; }
        .position-badge.president { background: #e3f2fd; color: #1976d2; }
        .position-badge.vice-president { background: #e8f5e9; color: #388e3c; }
        .position-badge.secretary { background: #fff3e0; color: #e64a19; }
        .position-badge.treasurer { background: #f3e5f5; color: #8e24aa; }
        .position-badge.auditor { background: #e0f7fa; color: #00acc1; }
        .position-badge.pio { background: #fff8e1; color: #ff8f00; }
        .position-badge.representative { background: #efebe9; color: #6d4c41; }
        .vote-count { display: flex; align-items: center; gap: 5px; justify-content: center; }
        .action-buttons { display: flex; gap: 8px; justify-content: center; align-items: center; }
        .btn-sm { padding: 8px 12px; font-size: 12px; min-width: 38px; height: 38px; border-radius: 4px; border: none; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
        .btn-edit { background-color: #ffc107; color: #212529; border: 1px solid #ffc107; }
        .btn-edit:hover { background-color: #e0a800; border-color: #d39e00; transform: translateY(-1px); color: #212529; }
        .btn-danger { background-color: #dc3545; color: white; border: 1px solid #dc3545; }
        .btn-danger:hover { background-color: #c82333; border-color: #bd2130; transform: translateY(-1px); color: white; }
        .actions-col { width: 120px; text-align: center; }
        .table-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .table-info { font-size: 14px; color: #6c757d; }
        .pagination { display: flex; gap: 5px; }
        .page-btn { padding: 5px 10px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer; }
        .page-btn.active { background: #3498db; color: white; border-color: #3498db; }
        .page-btn.disabled { opacity: 0.5; cursor: not-allowed; }
        .search-box { position: relative; width: 250px; }
        .search-box input { width: 100%; padding: 8px 15px 8px 35px; border: 1px solid #ddd; border-radius: 4px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #777; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 0; border-radius: 8px; width: 50%; max-width: 700px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); animation: modalopen 0.3s; }
        @keyframes modalopen { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h4 { margin: 0; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .close-modal { font-size: 24px; cursor: pointer; color: #777; background: none; border: none; }
        .close-modal:hover { color: #333; }
        .modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
        .add-position-modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); }
        .add-position-modal-content { background-color: #fff; margin: 15% auto; padding: 0; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); animation: modalopen 0.3s; }
        .add-position-modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; }
        .add-position-modal-header h4 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 10px; color: #2c3e50; }
        .add-position-modal-body { padding: 20px; }
        .add-position-form .form-group { margin-bottom: 15px; }
        .add-position-form input { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .add-position-form .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .notification-container { position: fixed; top: 80px; right: 20px; z-index: 1002; max-width: 400px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); animation: slideInRight 0.3s ease-out; position: relative; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert .close-btn { position: absolute; top: 5px; right: 10px; cursor: pointer; font-size: 18px; font-weight: bold; color: inherit; opacity: 0.7; transition: opacity 0.2s; }
        .alert .close-btn:hover { opacity: 1; }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
        .logo { padding: 20px; text-align: center; }
        .logo img { max-width: 100%; height: auto; max-height: 80px; }
        .current-photo-preview { max-width: 100px; max-height: 100px; border-radius: 4px; border: 1px solid #ddd; margin-top: 10px; }
        @media (max-width: 1200px) { .admin-sidebar { width: 240px; } }
        @media (max-width: 992px) { .admin-sidebar { width: 80px; padding: 1rem 0; overflow: hidden; } .admin-sidebar a span, .sidebar-header { display: none; } .admin-sidebar a { justify-content: center; padding: 0.75rem 0; } .form-grid { grid-template-columns: 1fr; } .form-group.full-width { grid-column: span 1; } .form-actions { grid-column: span 1; } .modal-content { width: 80%; } .position-input-wrapper { flex-direction: column; } .add-position-btn { width: 100%; height: auto; padding: 10px; } }
        @media (max-width: 768px) { .admin-top-bar { padding: 0 1rem; } .admin-content-area { padding: 1.5rem; } .content-header { flex-direction: column; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem; } .content-actions { width: 100%; justify-content: flex-end; } .data-table th, .data-table td { padding: 8px 10px; } .photo-col, .votes-col { display: none; } .actions-col { display: table-cell !important; width: auto; min-width: 100px; } .action-buttons { justify-content: center; gap: 8px; } .btn-sm { padding: 8px; min-width: 36px; height: 36px; } .notification-container { position: fixed; top: 80px; left: 20px; right: 20px; max-width: none; } }
        @media (max-width: 576px) { .admin-sidebar { position: fixed; bottom: 0; left: 0; right: 0; top: auto; height: auto; width: 100%; display: flex; justify-content: center; z-index: 100; padding: 0.5rem 0; border-top: 1px solid var(--gray-light); } .admin-sidebar ul { display: flex; width: 100%; justify-content: space-around; padding: 0; } .admin-sidebar li { margin-bottom: 0; } .admin-main-content { padding-bottom: 80px; } .admin-info span { display: none; } }
    </style>
</head>
<body>
    <div class="admin-dashboard-container">
        <div class="admin-top-bar">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <span>Santa Rita College</span>
            </div>
            <div class="admin-info">
                <span><?php if(isset($_SESSION['admin_name'])) echo $_SESSION['admin_name']; ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        <div class="admin-main-content">
            <div class="admin-sidebar">
                <center><div class="logo">
                <img src="../pic/srclogo.png" alt="Voting System Logo">
                <span style="color: white">Voting System</span>
            </div></center>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users-cog"></i><span>Manage Users</span></a></li>
                    <li class="active"><a href="manage_candidates.php"><i class="fas fa-user-tie"></i><span>Manage Candidates</span></a></li>
                    <li><a href="manage_history.php"><i class="fas fa-history"></i><span>Manage history</span></a></li>
                    <li><a href="results.php"><i class="fas fa-chart-pie"></i><span>Results Analytics</span></a></li>
                </ul>
            </div>
            <div class="admin-content-area">
                <div class="notification-container">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                            <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="add-candidate-form card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Add New Candidate</h3>
                        <button class="toggle-form-btn" type="button" onclick="this.closest('.card-body').querySelector('form').style.display = 'grid'">&plus;</button>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data" class="form-grid" <?php if (!$active_election_id) echo 'style="display:none;"'; ?>>
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" required placeholder="Enter candidate's first name">
                            </div>
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" placeholder="Enter candidate's middle name (optional)">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required placeholder="Enter candidate's last name">
                            </div>
                            <div class="form-group">
                                <label for="year">Year</label>
                                <select id="year" name="year" required>
                                    <option value="">Select Year</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="section">Section</label>
                                <input type="text" id="section" name="section" required placeholder="Enter candidate's section">
                            </div>
                            <div class="form-group">
                                <label for="position">Position</label>
                                <div class="position-input-wrapper">
                                    <div class="select-wrapper">
                                        <select id="position" name="position" required>
                                            <option value="">Select Position</option>
                                            <option value="President">President</option>
                                            <option value="Vice President">Vice President</option>
                                            <option value="Secretary">Secretary</option>
                                            <option value="Treasurer">Treasurer</option>
                                            <option value="Auditor">Auditor</option>
                                            <option value="PIO">PIO</option>
                                            <option value="Representative">Representative</option>
                                        </select>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <button type="button" class="btn btn-secondary add-position-btn" onclick="showAddPositionModal()"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label for="platform">Platform Statement</label>
                                <textarea id="platform" name="platform" rows="3" placeholder="Brief description of the candidate's platform"></textarea>
                            </div>
                            <div class="form-group file-upload full-width">
                                <label for="photo">Candidate Photo</label>
                                <div class="upload-container">
                                    <input type="file" id="photo" name="photo" accept="image/*" required>
                                    <label for="photo" class="upload-btn"><i class="fas fa-cloud-upload-alt"></i><span>Choose Image</span></label>
                                    <span class="file-name">No file chosen</span>
                                </div>
                                <small>Recommended: 300x300px square image (max 2MB)</small>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_candidate" class="btn btn-primary"><i class="fas fa-save"></i> Add Candidate</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset Form</button>
                            </div>
                        </form>
                         <?php if (!$active_election_id): ?>
                            <div style="text-align: center; padding: 2rem; background-color: #fff3cd; border-radius: 4px;">
                                <h4 style="color: #664d03;">Form is Currently Disabled</h4>
                                <p style="color: #664d03; margin-top: 0.5rem;">
                                    Cannot add candidates because no election is active. Please start a new election from the 
                                    <a href="results.php" style="font-weight: bold; color: #004085;">Results Page</a>.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Candidates List -->
                <div class="candidates-list card">
                    <div class="card-header">
                        <h3><i class="fas fa-list-ul"></i> Current Candidates (<span id="candidate-count"><?php echo count($candidates); ?></span>)</h3>
                        <div class="search-box">
                            <input type="text" placeholder="Search candidates..." id="search-candidates">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="photo-col">Photo</th>
                                        <th>Name</th>
                                        <th>Year</th>
                                        <th>Section</th>
                                        <th class="position-col">Position</th>
                                        <th>Platform</th>
                                        <th class="votes-col">Votes</th>
                                        <th class="actions-col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="candidates-tbody">
                                    <?php if (empty($candidates)): ?>
                                        <tr id="no-candidates-row">
                                            <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                                                <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                                <?php if ($active_election_id): ?>
                                                    No candidates have been added for the current election yet.
                                                <?php else: ?>
                                                    There is no active election to show candidates from.
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($candidates as $candidate): ?>
                                            <tr class="candidate-row">
                                                <td class="photo-col">
                                                    <div class="candidate-photo">
                                                        <img src="../<?php echo htmlspecialchars($candidate['photo_path']); ?>" 
                                                             alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>"
                                                             onerror="this.src='../pic/default-avatar.png'">
                                                    </div>
                                                </td>
                                                <td data-label="Name">
                                                    <strong><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></strong>
                                                    <?php if (!empty($candidate['middle_name'])): ?>
                                                        <br><small style="color: #6c757d;"><?php echo htmlspecialchars($candidate['middle_name']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Year"><?php echo htmlspecialchars($candidate['year']); ?></td>
                                                <td data-label="Section"><?php echo htmlspecialchars($candidate['section']); ?></td>
                                                <td data-label="Position" class="position-col">
                                                    <span class="position-badge <?php echo strtolower(str_replace(' ', '-', $candidate['position'])); ?>">
                                                        <?php echo htmlspecialchars($candidate['position']); ?>
                                                    </span>
                                                </td>
                                                <td data-label="Platform" class="platform-text">
                                                    <?php 
                                                    $platform = htmlspecialchars($candidate['platform']);
                                                    echo strlen($platform) > 100 ? substr($platform, 0, 100) . '...' : $platform;
                                                    ?>
                                                </td>
                                                <td data-label="Votes" class="votes-col">
                                                    <div class="vote-count">
                                                        <span style="font-weight: bold; color: #28a745;">
                                                            <?php echo isset($candidate['votes']) ? $candidate['votes'] : 0; ?>
                                                        </span>
                                                        <i class="fas fa-vote-yea" style="color: #28a745;"></i>
                                                    </div>
                                                </td>
                                                <td data-label="Actions" class="actions-col">
                                                    <div class="action-buttons">
                                                        <button type="button" class="btn btn-sm btn-edit" onclick="editCandidate(<?php echo $candidate['id']; ?>)" title="Edit <?php echo htmlspecialchars($candidate['first_name']); ?>"><i class="fas fa-edit"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                     <tr id="no-search-results" style="display: none;">
                                        <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                                            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                            No candidates found matching your search.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                           <div class="table-info">Showing <span id="showing-count"><?php echo count($candidates); ?></span> of <?php echo count($candidates); ?> candidate<?php echo count($candidates) != 1 ? 's' : ''; ?></div>
                           <div class="pagination">...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Candidate Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-edit"></i> Edit Candidate</h4>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editCandidateForm" method="post" enctype="multipart/form-data" class="form-grid">
                    <input type="hidden" name="candidate_id" id="edit_candidate_id">
                    <input type="hidden" name="current_photo_path" id="edit_current_photo_path">

                    <div class="form-group">
                        <label for="edit_first_name">First Name</label>
                        <input type="text" id="edit_first_name" name="edit_first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_middle_name">Middle Name</label>
                        <input type="text" id="edit_middle_name" name="edit_middle_name">
                    </div>
                    <div class="form-group">
                        <label for="edit_last_name">Last Name</label>
                        <input type="text" id="edit_last_name" name="edit_last_name" required>
                    </div>
                     <div class="form-group">
                        <label for="edit_year">Year</label>
                        <select id="edit_year" name="edit_year" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_section">Section</label>
                        <input type="text" id="edit_section" name="edit_section" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_position">Position</label>
                        <select id="edit_position" name="edit_position" required>
                            <option value="President">President</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Treasurer">Treasurer</option>
                            <option value="Auditor">Auditor</option>
                            <option value="PIO">PIO</option>
                            <option value="Representative">Representative</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="edit_platform">Platform</label>
                        <textarea id="edit_platform" name="edit_platform" rows="4"></textarea>
                    </div>

                    <div class="form-group file-upload full-width">
                        <label>Current Photo</label>
                        <img id="edit_photo_preview" src="" alt="Current Photo" class="current-photo-preview">
                    </div>

                     <div class="form-group file-upload full-width">
                        <label for="edit_photo">Upload New Photo (Optional)</label>
                        <div class="upload-container">
                            <input type="file" id="edit_photo" name="edit_photo" accept="image/*">
                            <label for="edit_photo" class="upload-btn"><i class="fas fa-cloud-upload-alt"></i><span>Choose New Image</span></label>
                            <span class="file-name" id="edit_file_name">No file chosen</span>
                        </div>
                        <small>If you don't choose a new image, the current one will be kept.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_candidate" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="addPositionModal" class="add-position-modal">...</div>
    
    <script>
        // Store candidates data passed from PHP
        const candidatesData = <?php echo json_encode($candidates); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Your existing JS code for file uploads
            const fileInput = document.getElementById('photo');
            const fileNameSpan = document.querySelector('.file-name');
            if(fileInput && fileNameSpan) {
                fileInput.addEventListener('change', function() {
                    fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'No file chosen';
                });
            }

            const editFileInput = document.getElementById('edit_photo');
            const editFileNameSpan = document.getElementById('edit_file_name');
            if(editFileInput && editFileNameSpan){
                 editFileInput.addEventListener('change', function() {
                    editFileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'No file chosen';
                });
            }

            // --- NEW: SEARCH CANDIDATES FUNCTIONALITY ---
            const searchInput = document.getElementById('search-candidates');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const candidateRows = document.querySelectorAll('#candidates-tbody tr.candidate-row');
                    const noResultsRow = document.getElementById('no-search-results');
                    const candidateCountSpan = document.getElementById('candidate-count');
                    const showingCountSpan = document.getElementById('showing-count');
                    
                    let visibleCount = 0;

                    candidateRows.forEach(row => {
                        // Get the text content from all relevant cells in the row
                        const rowText = row.textContent.toLowerCase();

                        // Check if the row text includes the search term
                        if (rowText.includes(searchTerm)) {
                            row.style.display = ''; // Show the row
                            visibleCount++;
                        } else {
                            row.style.display = 'none'; // Hide the row
                        }
                    });

                    // Update the count of visible candidates
                    if(showingCountSpan) {
                        showingCountSpan.textContent = visibleCount;
                    }
                     if(candidateCountSpan) {
                        candidateCountSpan.textContent = visibleCount;
                    }


                    // Show or hide the "no results" message
                    if (noResultsRow) {
                        if (visibleCount === 0 && candidateRows.length > 0) {
                            noResultsRow.style.display = 'table-row';
                        } else {
                            noResultsRow.style.display = 'none';
                        }
                    }
                });
            }
        });

        function editCandidate(candidateId) {
            const candidate = candidatesData.find(c => c.id == candidateId);
            if (!candidate) {
                console.error("Candidate not found!");
                return;
            }

            // Populate form
            document.getElementById('edit_candidate_id').value = candidate.id;
            document.getElementById('edit_current_photo_path').value = candidate.photo_path;
            document.getElementById('edit_first_name').value = candidate.first_name;
            document.getElementById('edit_middle_name').value = candidate.middle_name;
            document.getElementById('edit_last_name').value = candidate.last_name;
            document.getElementById('edit_year').value = candidate.year;
            document.getElementById('edit_section').value = candidate.section;
            document.getElementById('edit_position').value = candidate.position;
            document.getElementById('edit_platform').value = candidate.platform;
            
            // Photo preview
            const photoPreview = document.getElementById('edit_photo_preview');
            photoPreview.src = '../' + (candidate.photo_path ? candidate.photo_path : 'pic/default-avatar.png');

            // Show modal
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
    </script>
</body>
</html>