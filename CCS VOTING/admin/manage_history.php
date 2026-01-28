<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$success_msg = "";
$error_msg = "";
$edit_mode = false;
$edit_data = null;

// Handle edit request
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM election_history WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($edit_data) {
            $edit_mode = true;
        }
    } catch (PDOException $e) {
        $error_msg = "Error fetching record: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_history'])) {
    $fullname = sanitizeInput($_POST['fullname'] ?? '');
    $position = sanitizeInput($_POST['position'] ?? '');
    $year_section = sanitizeInput($_POST['year_section'] ?? '');
    $president_year = sanitizeInput($_POST['president_year'] ?? '');
    $platforms = sanitizeInput($_POST['platforms'] ?? '');
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;

    // Handle main photo upload
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_path = $upload_dir . time() . "_" . uniqid() . "." . $file_ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
    } elseif ($edit_id && isset($_POST['existing_photo'])) {
        $photo_path = $_POST['existing_photo'];
    }

    try {
        if ($edit_id) {
            $stmt = $pdo->prepare("UPDATE election_history SET fullname=?, position=?, year_section=?, year=?, platforms=?, photo_path=? WHERE id=?");
            $stmt->execute([$fullname, $position, $year_section, $president_year, $platforms, $photo_path, $edit_id]);
            $success_msg = "Record updated successfully!";
            $history_id = $edit_id;
        } else {
            $stmt = $pdo->prepare("INSERT INTO election_history (fullname, position, year_section, year, platforms, photo_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fullname, $position, $year_section, $president_year, $platforms, $photo_path]);
            $success_msg = "Record saved successfully!";
            $history_id = $pdo->lastInsertId();
        }

        // Handle extra photo uploads
        if (!empty($history_id) && isset($_FILES['extra_photos']) && !empty($_FILES['extra_photos']['name'][0])) {
            $extra_upload_dir = "uploads/history_photos/";
            if (!is_dir($extra_upload_dir)) {
                mkdir($extra_upload_dir, 0777, true);
            }

            foreach ($_FILES['extra_photos']['name'] as $key => $name) {
                if ($_FILES['extra_photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['extra_photos']['tmp_name'][$key];
                    $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                    $fileName = time() . "_" . uniqid() . "." . $file_ext;
                    $targetFile = $extra_upload_dir . $fileName;

                    if (move_uploaded_file($tmp_name, $targetFile)) {
                        $stmtPhoto = $pdo->prepare("INSERT INTO election_history_photos (history_id, photo_path) VALUES (?, ?)");
                        $stmtPhoto->execute([$history_id, $targetFile]);
                    }
                }
            }
        }

        $_SESSION['success_msg'] = $success_msg;
        header("Location: manage_history.php");
        exit;
    } catch (PDOException $e) {
        $error_msg = "Database error: " . $e->getMessage();
    }
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $stmt = $pdo->prepare("SELECT photo_path FROM election_history WHERE id = ?");
        $stmt->execute([$delete_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['photo_path']) && file_exists($row['photo_path'])) {
            unlink($row['photo_path']);
        }

        $stmt = $pdo->prepare("SELECT photo_path FROM election_history_photos WHERE history_id = ?");
        $stmt->execute([$delete_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $photoRow) {
            if (!empty($photoRow['photo_path']) && file_exists($photoRow['photo_path'])) {
                unlink($photoRow['photo_path']);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM election_history WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success_msg'] = "Record deleted successfully!";
        header("Location: manage_history.php");
        exit;
    } catch (PDOException $e) {
        $error_msg = "Delete error: " . $e->getMessage();
    }
}

if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage History | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">
</head>

<body>
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
                <div class="nav-item">
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
                <div class="nav-item active">
                    <a href="manage_history.php">
                        <i class="fas fa-history"></i>
                        <span>History</span>
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
                    <h1>Election History</h1>
                    <p>Manage and archive past election results and winners</p>
                </div>
            </header>

            <?php if (!empty($success_msg)): ?>
                <div class="modern-card"
                    style="background: rgba(16, 185, 129, 0.1); border-color: var(--success); color: var(--success); margin-bottom: 2rem; padding: 1rem;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="modern-card"
                    style="background: rgba(239, 68, 68, 0.1); border-color: var(--danger); color: var(--danger); margin-bottom: 2rem; padding: 1rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <div class="modern-card" style="margin-bottom: 2rem;">
                <div class="card-header"
                    style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.75rem;">
                    <div
                        style="padding: 0.5rem; background: var(--primary-light); border-radius: 8px; color: var(--primary);">
                        <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus-circle'; ?>"></i>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700;">
                        <?php echo $edit_mode ? 'Edit History Record' : 'Add New History Record'; ?>
                    </h2>
                </div>

                <div class="card-body" style="padding: 2rem;">
                    <form method="post" enctype="multipart/form-data">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                            <input type="hidden" name="existing_photo" value="<?php echo $edit_data['photo_path']; ?>">
                        <?php endif; ?>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label"
                                    style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Full Name</label>
                                <input type="text" name="fullname" class="form-control"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text-main);"
                                    required placeholder="e.g. Juan Dela Cruz"
                                    value="<?php echo $edit_mode ? htmlspecialchars($edit_data['fullname']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"
                                    style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Position
                                    Won</label>
                                <input type="text" name="position" class="form-control"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text-main);"
                                    required placeholder="e.g. President"
                                    value="<?php echo $edit_mode ? htmlspecialchars($edit_data['position']) : ''; ?>">
                            </div>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label"
                                    style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Year &
                                    Section</label>
                                <input type="text" name="year_section" class="form-control"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text-main);"
                                    required placeholder="e.g. BSCS-4A"
                                    value="<?php echo $edit_mode ? htmlspecialchars($edit_data['year_section']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"
                                    style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Year of
                                    Service</label>
                                <input type="text" name="president_year" class="form-control"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text-main);"
                                    required placeholder="e.g. 2023-2024"
                                    value="<?php echo $edit_mode ? htmlspecialchars($edit_data['year']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label class="form-label"
                                style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Main Photo (Winner
                                Portrait)</label>
                            <div
                                style="display: flex; align-items: center; gap: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px solid var(--border);">
                                <div id="photoPreview"
                                    style="width: 80px; height: 80px; background: var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <?php if ($edit_mode && !empty($edit_data['photo_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($edit_data['photo_path']); ?>"
                                            style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <i class="fas fa-image" style="font-size: 1.5rem; color: var(--text-muted);"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1;">
                                    <input type="file" name="photo" id="mainPhoto" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?> style="display: none;">
                                    <button type="button" class="btn btn-schedule"
                                        onclick="document.getElementById('mainPhoto').click()">
                                        <i class="fas fa-upload"></i> Choose Image
                                    </button>
                                    <span id="mainFileName"
                                        style="margin-left: 1rem; font-size: 0.875rem; color: var(--text-muted);">
                                        <?php echo $edit_mode ? basename($edit_data['photo_path']) : 'No file chosen'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label class="form-label"
                                style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Achievements /
                                Platforms</label>
                            <textarea name="platforms" class="form-control"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text-main);"
                                rows="4" placeholder="Describe the achievements or platforms during their term..."
                                required><?php echo $edit_mode ? htmlspecialchars($edit_data['platforms']) : ''; ?></textarea>
                        </div>

                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label class="form-label"
                                style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Extra Photos
                                (Gallery)</label>
                            <div
                                style="padding: 1.5rem; background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px dashed var(--border); text-align: center;">
                                <input type="file" id="extra_photos_input" multiple accept="image/*"
                                    style="display: none;">
                                <input type="file" id="hidden_photos" name="extra_photos[]" multiple
                                    style="display:none;">

                                <i class="fas fa-images"
                                    style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem; display: block;"></i>
                                <button type="button" class="btn btn-schedule"
                                    onclick="document.getElementById('extra_photos_input').click()">
                                    Add Gallery Photos
                                </button>
                                <div id="file-list"
                                    style="margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                            <?php if ($edit_mode): ?>
                                <a href="manage_history.php" class="btn btn-schedule"
                                    style="background: var(--bg-card); color: var(--text-main);">Cancel</a>
                            <?php else: ?>
                                <button type="reset" class="btn btn-schedule"
                                    style="background: var(--bg-card); color: var(--text-main);">Reset</button>
                            <?php endif; ?>
                            <button type="submit" name="save_history" class="btn btn-schedule">
                                <i class="fas fa-save"></i>
                                <?php echo $edit_mode ? 'Update Record' : 'Save Record'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History Table -->
            <div class="modern-card">
                <div class="card-header" style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div
                            style="padding: 0.5rem; background: var(--primary-light); border-radius: 8px; color: var(--primary);">
                            <i class="fas fa-history"></i>
                        </div>
                        <div>
                            <h2 style="font-size: 1.25rem; font-weight: 700;">History Records</h2>
                        </div>
                    </div>
                </div>

                <div class="card-body" style="padding: 0; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Winner</th>
                                <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Name & Position</th>
                                <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Year & Section</th>
                                <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Period</th>
                                <th style="padding: 1rem; border-bottom: 1px solid var(--border); text-align: right;">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM election_history ORDER BY id DESC");
                            while ($row = $stmt->fetch()):
                                ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 1rem;">
                                        <img src="<?php echo htmlspecialchars($row['photo_path']); ?>"
                                            style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border);"
                                            onerror="this.src='../../pic/default-avatar.png'">
                                    </td>
                                    <td style="padding: 1rem;">
                                        <div style="font-weight: 700;">
                                            <?php echo htmlspecialchars($row['fullname']); ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--primary);">
                                            <?php echo htmlspecialchars($row['position']); ?>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <?php echo htmlspecialchars($row['year_section']); ?>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <span
                                            style="background: var(--primary-light); color: var(--primary); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                            <?php echo htmlspecialchars($row['year']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; text-align: right;">
                                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                            <a href="manage_history.php?edit_id=<?php echo $row['id']; ?>"
                                                class="btn btn-schedule" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_history.php?delete_id=<?php echo $row['id']; ?>"
                                                class="btn btn-schedule"
                                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--danger); border-color: var(--danger);"
                                                onclick="return confirm('Delete this record?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Main Photo Preview
        document.getElementById('mainPhoto').addEventListener('change', function () {
            if (this.files && this.files[0]) {
                document.getElementById('mainFileName').textContent = this.files[0].name;
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('photoPreview').innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Extra Photos Gallery Logic
        const extraInput = document.getElementById('extra_photos_input');
        const hiddenInput = document.getElementById('hidden_photos');
        const fileList = document.getElementById('file-list');
        let dataTransfer = new DataTransfer();

        extraInput.addEventListener('change', function (event) {
            Array.from(event.target.files).forEach(file => {
                dataTransfer.items.add(file);
                const badge = document.createElement('div');
                badge.style.background = 'rgba(255,255,255,0.05)';
                badge.style.padding = '5px 12px';
                badge.style.borderRadius = '20px';
                badge.style.fontSize = '0.75rem';
                badge.style.border = '1px solid var(--border)';
                badge.innerHTML = `<i class="fas fa-image"></i> ${file.name}`;
                fileList.appendChild(badge);
            });
            hiddenInput.files = dataTransfer.files;
            extraInput.value = "";
        });
    </script>
</body>

</html>