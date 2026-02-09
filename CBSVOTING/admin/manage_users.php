<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Get all students
try {
    $users = $pdo->query("SELECT student_id, first_name, middle_name, last_name, email, is_approved, has_voted FROM students ORDER BY is_approved, first_name, middle_name, last_name")->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load students: " . $e->getMessage();
    $users = array();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Management | Santa Rita College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script type="text/javascript">
        (function () {
            emailjs.init("OIX5KA4zIfVfAWlsV");
        })();
    </script>
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">
    <style>
        .gmail-cell {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .confirmation-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10, 15, 29, 0.85);
            backdrop-filter: blur(10px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .modal-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-body {
            padding: 2rem;
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .badge-primary {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/mobile_base.css">
</head>

<body>
    <?php if (function_exists('renderMobileTopBar'))
        renderMobileTopBar('Voters'); ?>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../pic/srclogo.png" alt="Logo">
                <span>SRC Admin</span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item"><a href="dashboard.php"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
                </div>
                <div class="nav-item active"><a href="manage_users.php"><i
                            class="fas fa-users"></i><span>Voters</span></a></div>
                <div class="nav-item"><a href="manage_candidates.php"><i
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
                    <h1>Voter Management</h1>
                    <p>Approve or manage student registrations</p>
                </div>
            </header>

            <div class="modern-card" style="margin-bottom: 2rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <div style="position: relative;">
                        <input type="text" id="voterSearch" placeholder="Search by name, ID or email..."
                            onkeyup="filterTable()" style="padding-left: 3rem;">
                        <i class="fas fa-search"
                            style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                </div>
            </div>

            <div class="modern-card" style="padding: 0;">
                <div class="table-responsive">
                    <table class="modern-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th class="d-none-mobile">Email</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php foreach ($users as $user): ?>
                                <tr id="user-row-<?php echo htmlspecialchars($user['student_id']); ?>">
                                    <td><code
                                            style="color:var(--primary); font-weight:700;"><?php echo htmlspecialchars($user['student_id']); ?></code>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-main);">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td class="gmail-cell d-none-mobile">
                                        <?php echo htmlspecialchars($user['email'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span
                                            class="status-badge <?php echo $user['is_approved'] ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $user['is_approved'] ? '<i class="fas fa-check"></i> Approved' : '<i class="fas fa-clock"></i> Pending'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (!$user['is_approved']): ?>
                                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                                <button
                                                    onclick="showConfirmation('approve', '<?php echo $user['student_id']; ?>', '<?php echo $user['first_name'] . ' ' . $user['last_name']; ?>', '<?php echo $user['email']; ?>')"
                                                    class="modern-btn modern-btn-primary" style="padding: 0.5rem 0.75rem;">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button
                                                    onclick="showConfirmation('reject', '<?php echo $user['student_id']; ?>', '<?php echo $user['first_name'] . ' ' . $user['last_name']; ?>', '<?php echo $user['email']; ?>')"
                                                    class="modern-btn"
                                                    style="padding: 0.5rem 0.75rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2);">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--success); font-weight: 700; font-size: 0.8rem;"><i
                                                    class="fas fa-verified"></i> VERIFIED</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="modal-card">
            <div class="modal-body">
                <div id="modalIconContainer"
                    style="width: 60px; height: 60px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.5rem;">
                    <i id="modalIcon"></i>
                </div>
                <h3 id="modalTitle" style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem;"></h3>
                <p id="modalMessage" style="color: var(--text-muted); margin-bottom: 1.5rem;"></p>
                <div
                    style="background: rgba(255,255,255,0.03); padding: 1.25rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                    <small
                        style="color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Recipient</small>
                    <div id="modalEmail" style="font-weight: 700; color: var(--text-main); margin-top: 0.25rem;"></div>
                </div>
                <div id="emailStatus"
                    style="display: none; margin-top: 1rem; padding: 1rem; border-radius: var(--radius-md);"></div>
            </div>
            <div
                style="padding: 1.5rem 2rem; background: rgba(255,255,255,0.02); display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--border);">
                <button type="button" onclick="hideConfirmation()" class="modern-btn modern-btn-outline">Cancel</button>
                <button type="button" id="confirmButton" class="modern-btn modern-btn-primary">Confirm Action</button>
            </div>
        </div>
    </div>

    <script>
        var currentAction, currentUserId, currentUserEmail, isProcessing = false;
        const EMAILJS_CONFIG = { SERVICE_ID: 'service_xdmda8p', TEMPLATE_ID: 'template_2u0glca', PUBLIC_KEY: 'OIX5KA4zIfVfAWlsV' };

        function filterTable() {
            const term = document.getElementById('voterSearch').value.toLowerCase();
            document.querySelectorAll('#usersTableBody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        }

        function showConfirmation(action, id, name, email) {
            currentAction = action; currentUserId = id; currentUserEmail = email;
            const icon = document.getElementById('modalIcon');
            const iconBox = document.getElementById('modalIconContainer');
            if (action === 'approve') {
                icon.className = 'fas fa-user-check'; iconBox.style.background = 'rgba(16, 185, 129, 0.1)'; iconBox.style.color = 'var(--success)';
                document.getElementById('modalTitle').textContent = 'Approve Voter';
                document.getElementById('modalMessage').innerHTML = `Authorize <strong>${name}</strong> to participate in the election?`;
            } else {
                icon.className = 'fas fa-user-times'; iconBox.style.background = 'rgba(239, 68, 68, 0.1)'; iconBox.style.color = 'var(--danger)';
                document.getElementById('modalTitle').textContent = 'Reject Registration';
                document.getElementById('modalMessage').innerHTML = `Are you sure you want to reject and remove <strong>${name}</strong>?`;
            }
            document.getElementById('modalEmail').textContent = email || 'N/A';
            document.getElementById('confirmationModal').style.display = 'flex';
        }

        function hideConfirmation() { if (!isProcessing) document.getElementById('confirmationModal').style.display = 'none'; }

        async function executeAction() {
            if (isProcessing) return; isProcessing = true;
            const btn = document.getElementById('confirmButton');
            btn.disabled = true; btn.textContent = 'Processing...';

            try {
                const res = await fetch('manage_users_ajax.php', {
                    method: 'POST',
                    body: new URLSearchParams({ user_id: currentUserId, action: currentAction, ajax: 'true' })
                });
                const data = await res.json();
                if (data.success) {
                    if (currentUserEmail) {
                        btn.textContent = 'Notifying Student...';
                        await emailjs.send(EMAILJS_CONFIG.SERVICE_ID, EMAILJS_CONFIG.TEMPLATE_ID, {
                            to_email: currentUserEmail, to_name: data.user_data.first_name,
                            action: currentAction, action_text: currentAction === 'approve' ? 'approved' : 'rejected',
                            login_url: window.location.origin + '/src_votingsystem/Login/index.php'
                        }, EMAILJS_CONFIG.PUBLIC_KEY);
                    }
                    location.reload();
                } else throw new Error(data.message);
            } catch (e) {
                alert('Error: ' + e.message);
                btn.disabled = false; btn.textContent = 'Retry'; isProcessing = false;
            }
        }
        document.getElementById('confirmButton').addEventListener('click', executeAction);
        window.onclick = e => { if (e.target.id === 'confirmationModal') hideConfirmation(); }
    </script>
    <?php if (function_exists('renderMobileBottomNav'))
        renderMobileBottomNav('admin'); ?>
</body>

</html>