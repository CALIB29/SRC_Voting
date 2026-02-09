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
    <title>Manage Candidates | Santa Rita College</title>
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
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.primary {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
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
            box-shadow: var(--shadow-lg);
            backdrop-filter: var(--glass);
        }

        .suggestions-list li {
            padding: 12px 15px;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 1px solid var(--border);
        }

        .suggestions-list li:hover {
            background: rgba(255, 255, 255, 0.05);
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
            font-weight: 700;
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
            background: rgba(10, 15, 29, 0.8);
            backdrop-filter: blur(8px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
        }

        /* Custom File Upload Styling for Admin */
        .photo-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: var(--radius-lg);
            border: 1px dashed var(--border);
        }

        .photo-preview-box {
            width: 64px;
            height: 64px;
            background: var(--border);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .photo-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/mobile_base.css">
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('Candidates'); ?>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../pic/srclogo.png" alt="Logo">
                <span>SRC Admin</span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item"><a href="dashboard.php"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
                </div>
                <div class="nav-item"><a href="manage_users.php"><i class="fas fa-users"></i><span>Voters</span></a>
                </div>
                <div class="nav-item active"><a href="manage_candidates.php"><i
                            class="fas fa-user-tie"></i><span>Candidates</span></a></div>
                <div class="nav-item"><a href="results.php"><i class="fas fa-poll-h"></i><span>Poll Results</span></a>
                </div>
                <div class="nav-item"><a href="archive.php"><i class="fas fa-archive"></i><span>Archive</span></a></div>
                <div class="nav-item" style="margin-top: auto;"><a href="logout.php" style="color:var(--danger)"><i
                            class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div class="welcome-text">
                    <h1>Candidate Management</h1>
                    <p>Santa Rita College Election Panel</p>
                </div>
                <button class="modern-btn modern-btn-primary" type="button" onclick="toggleAddForm()">
                    <i class="fas fa-plus"></i><span id="toggleText">Add Candidate</span>
                </button>
            </header>

            <div class="notifications">
                <?php if ($message): ?>
                    <div class="modern-card"
                        style="border-left: 4px solid var(--success); background: rgba(16, 185, 129, 0.05); color: var(--success);">
                        <i class="fas fa-check-circle" style="margin-right:0.5rem"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="modern-card"
                        style="border-left: 4px solid var(--danger); background: rgba(239, 68, 68, 0.05); color: var(--danger);">
                        <i class="fas fa-exclamation-circle" style="margin-right:0.5rem"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="modern-card" id="addCandidateFormContainer"
                style="display: <?php echo (!$active_election_id || !empty($error)) ? 'block' : 'none'; ?>;">
                <?php if (!$active_election_id): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-exclamation-triangle"
                            style="font-size: 3rem; color: var(--warning); margin-bottom: 1rem;"></i>
                        <h3>No Active Election</h3>
                        <p>Please start an election first to add candidates.</p>
                    </div>
                <?php else: ?>
                    <h3 style="margin-bottom: 1.5rem;">New Candidate Form</h3>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group" style="position: relative;">
                            <label>Search Existing Student</label>
                            <div style="position: relative;">
                                <input type="text" id="search_student" class="modern-input"
                                    placeholder="Type name to autofill..." autocomplete="off">
                                <i class="fas fa-search"
                                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            </div>
                            <ul id="suggestions" class="suggestions-list" style="display: none;"></ul>
                        </div>

                        <div class="form-grid">
                            <div class="form-group"><label>First Name</label><input type="text" name="first_name" required>
                            </div>
                            <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name"></div>
                            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Year Level</label>
                                <select name="year" required>
                                    <option value="">Select Year</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Section</label><input type="text" name="section" required
                                    placeholder="e.g. BSCS-3A"></div>
                            <div class="form-group">
                                <label>Position</label>
                                <select name="position" required>
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

                        <div class="form-group"><label>Platform / Vision</label><textarea name="platform"
                                rows="4"></textarea></div>

                        <div class="form-group">
                            <label>Candidate Profile Photo</label>
                            <div class="photo-upload-wrapper">
                                <div id="photoPreview" class="photo-preview-box">
                                    <i class="fas fa-user-circle" style="font-size: 2rem; color: var(--text-muted);"></i>
                                </div>
                                <div style="flex: 1;">
                                    <input type="file" name="photo" id="candidatePhoto" accept="image/*" required
                                        style="display: none;">
                                    <button type="button" class="modern-btn modern-btn-outline"
                                        onclick="document.getElementById('candidatePhoto').click()">
                                        <i class="fas fa-image"></i> Select Photo
                                    </button>
                                    <span id="fileName"
                                        style="margin-left: 1rem; font-size: 0.85rem; color: var(--text-muted);">No file
                                        selected</span>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                            <button type="reset" class="modern-btn modern-btn-outline">Reset</button>
                            <button type="submit" name="add_candidate" class="modern-btn modern-btn-primary">Save
                                Candidate</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="modern-card">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <h3><i class="fas fa-users" style="color:var(--primary)"></i> Running Candidates</h3>
                    <div style="position: relative; width: 300px; max-width: 100%;">
                        <i class="fas fa-search"
                            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" id="search-candidates" placeholder="Filter candidates..."
                            style="padding-left: 45px;">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Profile</th>
                                <th>Name</th>
                                <th>Year/Section</th>
                                <th>Position</th>
                                <th style="text-align: center;">Votes</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="candidates-tbody">
                            <?php if (empty($candidates)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-muted);">No
                                        candidates registered yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr class="candidate-row">
                                        <td>
                                            <div class="candidate-photo-circle">
                                                <img src="<?php echo fixCandidatePhotoPath($candidate['photo_path'], '../../'); ?>"
                                                    alt="Photo" onerror="this.src='../../logo/srclogo.png'">
                                            </div>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($candidate['year'] . ' - ' . $candidate['section']); ?>
                                        </td>
                                        <td><span
                                                class="status-badge primary"><?php echo htmlspecialchars($candidate['position']); ?></span>
                                        </td>
                                        <td style="text-align: center; font-weight: 800; color: var(--success);"><i
                                                class="fas fa-vote-yea"></i> <?php echo $candidate['votes'] ?? 0; ?></td>
                                        <td style="text-align: right;">
                                            <button type="button" class="modern-btn modern-btn-outline"
                                                onclick="editCandidate(<?php echo $candidate['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Candidate Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div
                style="padding: 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.25rem; font-weight: 800;"><i class="fas fa-edit"
                        style="color:var(--primary)"></i> Edit Candidate</h2>
                <button type="button" onclick="closeEditModal()"
                    style="background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>
            <div style="padding: 2rem;">
                <form id="editCandidateForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="candidate_id" id="edit_candidate_id">
                    <input type="hidden" name="current_photo_path" id="edit_current_photo_path">

                    <div class="form-grid">
                        <div class="form-group"><label>First Name</label><input type="text" id="edit_first_name"
                                name="edit_first_name" required></div>
                        <div class="form-group"><label>Middle Name</label><input type="text" id="edit_middle_name"
                                name="edit_middle_name"></div>
                        <div class="form-group"><label>Last Name</label><input type="text" id="edit_last_name"
                                name="edit_last_name" required></div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Year Level</label>
                            <select id="edit_year" name="edit_year" required>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Section</label><input type="text" id="edit_section"
                                name="edit_section" required></div>
                        <div class="form-group">
                            <label>Position</label>
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
                    </div>

                    <div class="form-group"><label>Platform / Vision</label><textarea id="edit_platform"
                            name="edit_platform" rows="4"></textarea></div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Current Photo</label>
                            <div class="photo-preview-box" style="width:100%; height:120px;">
                                <img id="edit_photo_preview" src="" alt="Preview">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Update Photo</label>
                            <input type="file" id="edit_photo" name="edit_photo" accept="image/*"
                                style="display: none;">
                            <button type="button" class="modern-btn modern-btn-outline"
                                style="width: 100%; height: 120px;"
                                onclick="document.getElementById('edit_photo').click()">
                                <i class="fas fa-cloud-upload-alt"
                                    style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                Choose New Image
                            </button>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="button" class="modern-btn modern-btn-outline"
                            onclick="closeEditModal()">Cancel</button>
                        <button type="submit" name="update_candidate" class="modern-btn modern-btn-primary">Save
                            Changes</button>
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
                text.textContent = 'Add Candidate';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const addFileInput = document.getElementById('candidatePhoto');
            const addFileName = document.getElementById('fileName');
            const addPreview = document.getElementById('photoPreview');
            if (addFileInput) {
                addFileInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        addFileName.textContent = this.files[0].name;
                        const reader = new FileReader();
                        reader.onload = e => addPreview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }

            const editFileInput = document.getElementById('edit_photo');
            const editPreview = document.getElementById('edit_photo_preview');
            if (editFileInput) {
                editFileInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = e => editPreview.src = e.target.result;
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }

            const searchInput = document.getElementById('search-candidates');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = this.value.toLowerCase();
                    document.querySelectorAll('.candidate-row').forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
                    });
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
            document.getElementById('edit_photo_preview').src = '../' + (candidate.photo_path || 'pic/default-avatar.png');
            document.getElementById('editModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.body.style.overflow = '';
        }

        // Student Database Search
        const searchStudentInput = document.getElementById('search_student');
        const suggestionsList = document.getElementById('suggestions');
        if (searchStudentInput) {
            let debounceTimer;
            searchStudentInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                const query = this.value;
                if (query.length < 2) { suggestionsList.style.display = 'none'; return; }

                debounceTimer = setTimeout(() => {
                    fetch(`get_potential_candidates.php?search=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            suggestionsList.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(student => {
                                    const li = document.createElement('li');
                                    li.innerHTML = `<strong>${student.first_name} ${student.last_name}</strong><br><small>ID: ${student.student_id}</small>`;
                                    li.addEventListener('click', () => {
                                        document.querySelector('input[name="first_name"]').value = student.first_name;
                                        document.querySelector('input[name="middle_name"]').value = student.middle_name || '';
                                        document.querySelector('input[name="last_name"]').value = student.last_name;
                                        searchStudentInput.value = '';
                                        suggestionsList.style.display = 'none';
                                    });
                                    suggestionsList.appendChild(li);
                                });
                                suggestionsList.style.display = 'block';
                            } else { suggestionsList.style.display = 'none'; }
                        });
                }, 300);
            });
            document.addEventListener('click', e => { if (e.target !== searchStudentInput) suggestionsList.style.display = 'none'; });
        }
    </script>
    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('admin'); ?>
</body>

</html>