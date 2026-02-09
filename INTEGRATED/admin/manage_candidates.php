<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Get the ID of the currently active election.
$active_election_id = $pdo->query("SELECT id FROM vot_elections WHERE is_active = 1")->fetchColumn();

// Initialize message variables
$message = '';
$error = '';

// Handle add candidate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    if (!$active_election_id) {
        $error = "Cannot add candidate. No election is currently active. Please start a new election from the Results page first.";
    } else {
        $first_name = sanitizeInput($_POST['first_name']);
        $middle_name = sanitizeInput($_POST['middle_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $year = sanitizeInput($_POST['year']);
        $section = sanitizeInput($_POST['section']);
        $position = sanitizeInput($_POST['position']);
        $platform = sanitizeInput($_POST['platform']);

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
                $stmt = $pdo->prepare("INSERT INTO vot_candidates 
                    (first_name, middle_name, last_name, year, section, position, platform, photo_path, election_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$first_name, $middle_name, $last_name, $year, $section, $position, $platform, $photo_path, $active_election_id]);
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
            $stmt = $pdo->prepare("UPDATE vot_candidates SET 
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


// Get candidates only for the active election.
$candidates = [];
if ($active_election_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM vot_candidates WHERE election_id = :election_id ORDER BY id DESC");
        $stmt->execute(['election_id' => $active_election_id]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Query failed: " . $e->getMessage();
        $candidates = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">
    <style>
        .candidate-photo-circle {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--border);
        }

        .candidate-photo-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.primary {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
        }

        .status-badge.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal-content {
            position: relative;
            background: var(--bg-card);
            margin: auto;
            border: 1px solid var(--border);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideDown 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border-radius: 20px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .close-modal {
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            color: var(--danger) !important;
            transform: rotate(90deg);
        }

        /* Scrollbar for modal */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: var(--secondary);
        }

        /* Suggestions List */
        .suggestions-list {
            position: absolute;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 0 0 12px 12px;
            width: 100%;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            list-style: none;
            padding: 0;
            margin-top: 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .suggestions-list li {
            padding: 12px 15px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .suggestions-list li:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .suggestions-list li:last-child {
            border-bottom: none;
        }

        .past-candidate-badge {
            font-size: 0.7rem;
            background: var(--warning);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
            display: inline-block;
        }
    </style>

    <link rel="stylesheet" href="../../assets/css/mobile_base.css"></head>

<body>
<?php if (function_exists('renderMobileTopBar')) renderMobileTopBar('Candidates'); ?>
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
                <div class="nav-item active">
                    <a href="manage_candidates.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Candidates</span>
                    </a>
                </div>
                <div class="nav-item">
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
                    <h1>Candidate Management</h1>
                    <p>Add, edit, or remove election candidates</p>
                </div>
            </header>

            <div class="notification-container">
                <?php if (!empty($message)): ?>
                    <div class="modern-card"
                        style="background: rgba(16, 185, 129, 0.1); border-color: var(--success); color: var(--success); margin-bottom: 2rem;">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="modern-card"
                        style="background: rgba(239, 68, 68, 0.1); border-color: var(--danger); color: var(--danger); margin-bottom: 2rem;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="modern-card" style="margin-bottom: 2rem;">
                <div class="card-header"
                    style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div
                            style="padding: 0.5rem; background: rgba(79,70,229,0.1); border-radius: 8px; color: var(--primary);">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h2 style="font-size: 1.25rem; font-weight: 700;">Add New Candidate</h2>
                    </div><button class="modern-btn secondary" type="button" onclick="toggleAddForm()"><i
                            class="fas fa-plus"></i><span id="toggleText">Show Form</span></button>
                </div>
                <div class="card-body" id="addCandidateFormContainer"
                    style="display: <?php echo (!$active_election_id || !empty($error)) ? 'block' : 'none'; ?>; padding: 2rem;">
                    <?php if (!$active_election_id): ?>
                        <div
                            style="text-align: center; padding: 3rem; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px dashed var(--border);">
                            <div style="font-size: 3rem; color: var(--warning); margin-bottom: 1rem;"><i
                                    class="fas fa-exclamation-triangle"></i></div>
                            <h3 style="font-weight: 700; margin-bottom: 0.5rem;">No Active Election</h3>
                            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">You cannot add
                                candidates without an active election. Please start an election from the <a
                                    href="results.php" style="color: var(--primary); font-weight: 600;">Results
                                    Analytics</a> first. </p>
                        </div>
                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data" class="modern-form">
                            <div class="form-group" style="margin-bottom: 1.5rem; position: relative;">
                                <label class="form-label">Search Student Database</label>
                                <div style="position: relative;">
                                    <input type="text" id="search_student" class="modern-input"
                                        placeholder="Type name to search existing students..." autocomplete="off">
                                    <i class="fas fa-search"
                                        style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                                </div>
                                <ul id="suggestions" class="suggestions-list" style="display: none;"></ul>
                            </div>
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                                <div class="form-group"><label class="form-label">First Name</label><input type="text"
                                        name="first_name" class="modern-input" required placeholder="e.g. Juan"></div>
                                <div class="form-group"><label class="form-label">Middle Name (Optional)</label><input
                                        type="text" name="middle_name" class="modern-input" placeholder="e.g. Protacio">
                                </div>
                                <div class="form-group"><label class="form-label">Last Name</label><input type="text"
                                        name="last_name" class="modern-input" required placeholder="e.g. Dela Cruz"></div>
                            </div>
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                                <div class="form-group"><label class="form-label">Year Level</label>
                                    <select name="year" class="modern-input" required>
                                        <option value="">Select Year</option>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                    </select>
                                </div>
                                <div class="form-group"><label class="form-label">Section</label><input type="text"
                                        name="section" class="modern-input" required placeholder="e.g. BSCS-3A"></div>
                                <div class="form-group"><label class="form-label">Position</label>
                                    <select name="position" class="modern-input" required>
                                        <option value="">Select Position</option>
                                        <option value="President">President</option>
                                        <option value="Vice President">Vice President</option>
                                        <option value="Secretary">Secretary</option>
                                        <option value="Treasurer">Treasurer</option>
                                        <option value="Auditor">Auditor</option>
                                        <option value="PIO">PIO</option>
                                        <option value="Representative">Representative</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" style="margin-top: 1.5rem;"><label class="form-label">Platform /
                                    Statement</label><textarea name="platform" class="modern-input" rows="4"
                                    placeholder="Describe the candidate's goals and vision..."></textarea></div>
                            <div class="form-group" style="margin-top: 1.5rem;"><label class="form-label">Candidate
                                    Photo</label>
                                <div
                                    style="display: flex; align-items: center; gap: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid var(--border);">
                                    <div id="photoPreview"
                                        style="width: 64px; height: 64px; background: var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        <i class="fas fa-image" style="font-size: 1.5rem; color: var(--text-muted);"></i>
                                    </div>
                                    <div style="flex: 1;"><input type="file" name="photo" id="candidatePhoto"
                                            accept="image/*" required style="display: none;"><button type="button"
                                            class="modern-btn secondary"
                                            onclick="document.getElementById('candidatePhoto').click()"><i
                                                class="fas fa-upload"></i> Choose Image </button><span id="fileName"
                                            style="margin-left: 1rem; font-size: 0.875rem; color: var(--text-muted);">No
                                            file chosen</span></div>
                                </div><small
                                    style="display: block; margin-top: 0.5rem; color: var(--text-muted);">Recommended:
                                    300x300px square image (max 2MB)</small>
                            </div>
                            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;"> <button
                                    type="reset" class="modern-btn">Reset Form</button> <button type="submit"
                                    name="add_candidate" class="modern-btn modern-btn-primary"> <i class="fas fa-save"></i>
                                    Save
                                    Candidate </button> </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modern-card">
                <div class="card-header"
                    style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div
                            style="padding: 0.5rem; background: rgba(79,70,229,0.1); border-radius: 8px; color: var(--primary);">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div>
                            <h2 style="font-size: 1.25rem; font-weight: 700;">Current Candidates</h2>
                            <p style="font-size: 0.875rem; color: var(--text-muted);"><span
                                    id="candidate-count"><?php echo count($candidates); ?></span> candidates
                                total</p>
                        </div>
                    </div>
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search vot_candidates..." id="search-candidates"
                            class="modern-input">
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Photo</th>
                                    <th>Candidate Info</th>
                                    <th>Level & Section</th>
                                    <th style="text-align: center;">Position</th>
                                    <th style="text-align: center;">Votes</th>
                                    <th style="text-align: right; padding-right: 2rem;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="candidates-tbody">
                                <?php if (empty($candidates)): ?>
                                    <tr id="no-candidates-row">
                                        <td colspan="6" style="text-align: center; padding: 4rem;">
                                            <div style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;">
                                                <i class="fas fa-user-slash"></i>
                                            </div>
                                            <p style="color: var(--text-muted); font-size: 1rem;">
                                                <?php echo $active_election_id ? "No candidates added for this election yet." : "No active election found."; ?>
                                            </p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($candidates as $candidate): ?>
                                        <tr class="candidate-row">
                                            <td>
                                                <div class="candidate-photo-circle">
                                                    <img src="../<?php echo htmlspecialchars($candidate['photo_path']); ?>"
                                                        alt="Photo" onerror="this.src='../pic/default-avatar.png'">
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: var(--text-main);">
                                                    <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                </div>
                                                <?php if (!empty($candidate['middle_name'])): ?>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                        <?php echo htmlspecialchars($candidate['middle_name']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-weight: 500; color: var(--text-main);">
                                                    <?php echo htmlspecialchars($candidate['year']); ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                    <?php echo htmlspecialchars($candidate['section']); ?>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="status-badge primary">
                                                    <?php echo htmlspecialchars($candidate['position']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <div
                                                    style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 700; color: var(--success);">
                                                    <i class="fas fa-vote-yea"></i>
                                                    <?php echo isset($candidate['votes']) ? $candidate['votes'] : 0; ?>
                                                </div>
                                            </td>
                                            <td style="text-align: right; padding-right: 2rem;">
                                                <button type="button" class="modern-btn secondary" style="padding: 0.5rem;"
                                                    onclick="editCandidate(<?php echo $candidate['id']; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <tr id="no-search-results" style="display: none;">
                                    <td colspan="6" style="text-align: center; padding: 4rem;">
                                        <div style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <p style="color: var(--text-muted);">No candidates found matching
                                            your search.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Candidate Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"
                style="padding: 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div
                        style="padding: 0.5rem; background: rgba(79,70,229,0.1); border-radius: 8px; color: var(--primary);">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Edit Candidate</h2>
                </div>
                <button type="button" class="close-modal" onclick="closeEditModal()"
                    style="background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <form id="editCandidateForm" method="post" enctype="multipart/form-data" class="modern-form">
                    <input type="hidden" name="candidate_id" id="edit_candidate_id">
                    <input type="hidden" name="current_photo_path" id="edit_current_photo_path">

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" id="edit_first_name" name="edit_first_name" class="modern-input"
                                required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" id="edit_middle_name" name="edit_middle_name" class="modern-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" id="edit_last_name" name="edit_last_name" class="modern-input" required>
                        </div>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label">Year Level</label>
                            <select id="edit_year" name="edit_year" class="modern-input" required>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Section</label>
                            <input type="text" id="edit_section" name="edit_section" class="modern-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Position</label>
                            <select id="edit_position" name="edit_position" class="modern-input" required>
                                <option value="President">President</option>
                                <option value="Vice President">Vice President</option>
                                <option value="Secretary">Secretary</option>
                                <option value="Treasurer">Treasurer</option>
                                <option value="Auditor">Auditor</option>
                                <option value="PIO">PIO</option>
                                <option value="Representative">Representative</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label class="form-label">Platform / Statement</label>
                        <textarea id="edit_platform" name="edit_platform" class="modern-input" rows="4"></textarea>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-top: 1.5rem; align-items: start;">
                        <div class="form-group">
                            <label class="form-label">Current Photo</label>
                            <div
                                style="width: 100%; aspect-ratio: 1; background: rgba(255,255,255,0.02); border-radius: 12px; overflow: hidden; border: 1px solid var(--border);">
                                <img id="edit_photo_preview" src="" alt="Preview"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Update Photo (Optional)</label>
                            <div
                                style="padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px dashed var(--border); text-align: center;">
                                <input type="file" id="edit_photo" name="edit_photo" accept="image/*"
                                    style="display: none;">
                                <i class="fas fa-cloud-upload-alt"
                                    style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem; display: block;"></i>
                                <button type="button" class="modern-btn secondary"
                                    onclick="document.getElementById('edit_photo').click()">
                                    Select New Image
                                </button>
                                <div id="edit_file_name"
                                    style="margin-top: 0.75rem; font-size: 0.875rem; color: var(--text-muted);">No
                                    file chosen</div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="button" class="modern-btn" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" name="update_candidate" class="modern-btn modern-btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const candidatesData = <?php echo json_encode($candidates); ?>;

        function toggleAddForm() {
            const container = document.getElementById('addCandidateFormContainer');
            const text = document.getElementById('toggleText');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                text.textContent = 'Hide Form';
            } else {
                container.style.display = 'none';
                text.textContent = 'Show Form';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // File input logic for add form
            const addFileInput = document.getElementById('candidatePhoto');
            const addFileName = document.getElementById('fileName');
            const addPreview = document.getElementById('photoPreview');

            if (addFileInput) {
                addFileInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        addFileName.textContent = this.files[0].name;
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            addPreview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }

            // File input logic for edit form
            const editFileInput = document.getElementById('edit_photo');
            const editFileName = document.getElementById('edit_file_name');
            const editPreview = document.getElementById('edit_photo_preview');

            if (editFileInput) {
                editFileInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        editFileName.textContent = this.files[0].name;
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            editPreview.src = e.target.result;
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }

            // Search functionality
            const searchInput = document.getElementById('search-candidates');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = this.value.toLowerCase();
                    const candidateRows = document.querySelectorAll('.candidate-row');
                    const noResults = document.getElementById('no-search-results');
                    let count = 0;

                    candidateRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(term)) {
                            row.style.display = '';
                            count++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    document.getElementById('candidate-count').textContent = count;
                    noResults.style.display = count === 0 ? '' : 'none';
                });
            }
        });

        function editCandidate(id) {
            const candidate = candidatesData.find(c => c.id == id);
            if (!candidate) return;

            document.getElementById('edit_candidate_id').value = candidate.id;
            document.getElementById('edit_current_photo_path').value = candidate.photo_path;
            document.getElementById('edit_first_name').value = candidate.first_name;
            document.getElementById('edit_middle_name').value = candidate.middle_name;
            document.getElementById('edit_last_name').value = candidate.last_name;
            document.getElementById('edit_year').value = candidate.year;
            document.getElementById('edit_section').value = candidate.section;
            document.getElementById('edit_position').value = candidate.position;
            document.getElementById('edit_platform').value = candidate.platform;

            const preview = document.getElementById('edit_photo_preview');
            preview.src = '../' + (candidate.photo_path || 'pic/default-avatar.png');

            document.getElementById('editModal').classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
        }

        // Close modal on outside click
        window.onclick = function (event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Student Search and Suggestion
        const searchStudentInput = document.getElementById('search_student');
        const suggestionsList = document.getElementById('suggestions');

        if (searchStudentInput) {
            let debounceTimer;

            searchStudentInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                const query = this.value;

                if (query.length < 2) {
                    suggestionsList.style.display = 'none';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`get_potential_candidates.php?search=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            suggestionsList.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(student => {
                                    const li = document.createElement('li');
                                    const pastBadge = student.is_past_candidate ?
                                        `<span class="past-candidate-badge"><i class="fas fa-history"></i> Past: ${student.past_position}</span>` : '';

                                    li.innerHTML = `
                                        <div style="font-weight: 600; color: var(--text-main);">
                                            ${student.first_name} ${student.middle_name || ''} ${student.last_name}
                                            ${pastBadge}
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">ID: ${student.student_id ? student.student_id : 'N/A'}</div>
                                    `;

                                    li.addEventListener('click', () => {
                                        document.querySelector('input[name="first_name"]').value = student.first_name;
                                        document.querySelector('input[name="middle_name"]').value = student.middle_name || '';
                                        document.querySelector('input[name="last_name"]').value = student.last_name;
                                        // Year and Section still need manual input as not in student table (or reliable)

                                        searchStudentInput.value = '';
                                        suggestionsList.style.display = 'none';
                                    });

                                    suggestionsList.appendChild(li);
                                });
                                suggestionsList.style.display = 'block';
                            } else {
                                suggestionsList.style.display = 'none';
                            }
                        })
                        .catch(err => console.error('Error fetching suggestions:', err));
                }, 300);
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function (e) {
                if (e.target !== searchStudentInput && !suggestionsList.contains(e.target)) {
                    suggestionsList.style.display = 'none';
                }
            });
        }
    </script>

<?php if (function_exists('renderMobileBottomNav')) renderMobileBottomNav('admin'); ?>
</body>

</html>