<?php
session_start();

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
    <title>Manage Users - Santa Rita College Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- EmailJS SDK -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script type="text/javascript">
        (function () {
            emailjs.init("OIX5KA4zIfVfAWlsV"); // Your public key
        })();
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern_admin.css">

    <style>
        .gmail-cell {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Modern Modal Styles Overrides */
        .confirmation-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .confirmation-modal-content {
            background: var(--bg-sidebar);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            box-shadow: var(--shadow);
            animation: modalFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            background: rgba(255, 255, 255, 0.02);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .email-status {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-top: 1.5rem;
            display: none;
            font-size: 0.875rem;
        }

        .email-status.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .email-status.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
    </style>
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
                <div class="nav-item active">
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
                    <h1>Voter Management</h1>
                    <p>Approve or manage voter registrations</p>
                </div>
            </header>

            <div id="dynamicAlert" style="display: none; margin-bottom: 2rem;" class="modern-card"></div>

            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="voterSearch" class="modern-input" placeholder="Search by name, ID or email..."
                    onkeyup="filterTable()">
            </div>

            <div class="modern-table-container">
                <table class="modern-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Voted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php foreach ($users as $user): ?>
                            <tr id="user-row-<?php echo htmlspecialchars($user['student_id']); ?>">
                                <td style="font-family: monospace; font-weight: 600;">
                                    <?php echo htmlspecialchars($user['student_id']); ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-main);">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="gmail-cell">
                                    <?php if (!empty($user['email'])): ?>
                                        <span title="<?php echo htmlspecialchars($user['email']); ?>">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style: italic;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span
                                        class="status-badge <?php echo $user['is_approved'] ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $user['is_approved'] ? '<i class="fas fa-check"></i> Approved' : '<i class="fas fa-clock"></i> Pending'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="status-badge <?php echo $user['has_voted'] ? 'badge-primary' : 'badge-danger'; ?>">
                                        <?php echo $user['has_voted'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if (!$user['is_approved']): ?>
                                            <button
                                                onclick="showConfirmation('approve', '<?php echo htmlspecialchars($user['student_id']); ?>', '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>', '<?php echo htmlspecialchars($user['email'] ?? ''); ?>')"
                                                class="modern-btn modern-btn-primary" style="padding: 0.5rem 1rem;">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button
                                                onclick="showConfirmation('reject', '<?php echo htmlspecialchars($user['student_id']); ?>', '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>', '<?php echo htmlspecialchars($user['email'] ?? ''); ?>')"
                                                class="modern-btn modern-btn-danger" style="padding: 0.5rem 1rem;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php else: ?>
                                            <span style="color: var(--success); font-weight: 600; font-size: 0.8rem;">
                                                <i class="fas fa-verified"></i> Active
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-modal-content">
            <div class="modal-header" id="modalHeader">
                <div id="modalIconContainer"
                    style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i id="modalIcon" class="fas"></i>
                </div>
                <h3 id="modalTitle" style="font-weight: 800; letter-spacing: -0.5px;"></h3>
            </div>
            <div class="modal-body">
                <div id="modalMessage" style="color: var(--text-muted); line-height: 1.6;"></div>
                <div
                    style="margin-top: 1.5rem; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div
                        style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 0.5rem;">
                        Recipient Email</div>
                    <div id="modalEmail" style="font-weight: 600; color: var(--text-main); font-family: monospace;">
                    </div>
                </div>
                <div id="emailStatus" class="email-status"></div>
            </div>
            <div class="modal-footer">
                <button onclick="hideConfirmation()" class="modern-btn">Cancel</button>
                <button id="confirmButton" class="modern-btn modern-btn-primary">
                    <span id="confirmText">Confirm</span>
                    <div class="loading-spinner" id="loadingSpinner"></div>
                </button>
            </div>
        </div>
    </div>

    <script>
        var currentAction = '';
        var currentUserId = '';
        var currentUserEmail = '';
        var isProcessing = false;

        // EmailJS logic remains same
        const EMAILJS_CONFIG = {
            SERVICE_ID: 'service_xdmda8p',
            TEMPLATE_ID: 'template_2u0glca',
            PUBLIC_KEY: 'OIX5KA4zIfVfAWlsV'
        };

        function filterTable() {
            const input = document.getElementById('voterSearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('usersTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let found = false;
                const tds = tr[i].getElementsByTagName('td');
                for (let j = 0; j < tds.length - 1; j++) {
                    if (tds[j].textContent.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                tr[i].style.display = found ? '' : 'none';
            }
        }

        function showConfirmation(action, userId, userName, userEmail) {
            if (isProcessing) return;
            currentAction = action;
            currentUserId = userId;
            currentUserEmail = userEmail;

            const modal = document.getElementById('confirmationModal');
            const icon = document.getElementById('modalIcon');
            const iconContainer = document.getElementById('modalIconContainer');
            const title = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');
            const email = document.getElementById('modalEmail');
            const btn = document.getElementById('confirmButton');

            email.textContent = userEmail || 'No email provided';

            if (action === 'approve') {
                icon.className = 'fas fa-user-check';
                iconContainer.style.background = 'rgba(16, 185, 129, 0.1)';
                iconContainer.style.color = 'var(--success)';
                title.textContent = 'Approve Voter';
                message.innerHTML = `Are you sure you want to approve <strong>${userName}</strong>? They will be notified via email and allowed to vote.`;
                btn.className = 'modern-btn modern-btn-primary';
            } else {
                icon.className = 'fas fa-user-times';
                iconContainer.style.background = 'rgba(239, 68, 68, 0.1)';
                iconContainer.style.color = 'var(--danger)';
                title.textContent = 'Reject Registration';
                message.innerHTML = `Are you sure you want to reject <strong>${userName}</strong>? This action will permanentely remove their registration data.`;
                btn.className = 'modern-btn modern-btn-danger';
            }

            modal.style.display = 'flex';
        }

        function hideConfirmation() {
            if (isProcessing) return;
            document.getElementById('confirmationModal').style.display = 'none';
        }

        function executeAction() {
            if (isProcessing) return;
            isProcessing = true;

            const btn = document.getElementById('confirmButton');
            const text = document.getElementById('confirmText');
            const spinner = document.getElementById('loadingSpinner');
            const status = document.getElementById('emailStatus');

            btn.disabled = true;
            text.textContent = 'Processing...';
            spinner.style.display = 'block';

            const formData = new FormData();
            formData.append('user_id', currentUserId);
            formData.append('action', currentAction);
            formData.append('ajax', 'true');

            fetch('manage_users_ajax.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (currentUserEmail) {
                            text.textContent = 'Sending notification...';
                            return sendEmail(currentAction, data.user_data);
                        }
                        return { success: true, message: 'Updated without email' };
                    }
                    throw new Error(data.message);
                })
                .then(emailResult => {
                    updateUI(emailResult.success);
                    setTimeout(() => {
                        hideConfirmation();
                        location.reload(); // Simple way to refresh table
                    }, 1500);
                })
                .catch(e => {
                    alert('Error: ' + e.message);
                    isProcessing = false;
                    btn.disabled = false;
                    text.textContent = 'Retry';
                    spinner.style.display = 'none';
                });
        }

        function sendEmail(action, userData) {
            const params = {
                to_email: userData.email,
                to_name: `${userData.first_name} ${userData.last_name}`,
                action: action,
                action_text: action === 'approve' ? 'approved' : 'rejected',
                login_url: window.location.origin + '/src_votingsystem/Login/index.php'
            };

            return emailjs.send(EMAILJS_CONFIG.SERVICE_ID, EMAILJS_CONFIG.TEMPLATE_ID, params, EMAILJS_CONFIG.PUBLIC_KEY);
        }

        function updateUI(emailSuccess) {
            const status = document.getElementById('emailStatus');
            status.style.display = 'block';
            if (emailSuccess) {
                status.className = 'email-status success';
                status.innerHTML = '<i class="fas fa-check-circle"></i> Operation successful and notification sent.';
            } else {
                status.className = 'email-status error';
                status.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Database updated but notification failed.';
            }
            document.getElementById('confirmText').textContent = 'Completed';
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        document.getElementById('confirmButton').addEventListener('click', executeAction);

        // Close on click outside
        window.onclick = e => {
            if (e.target.id === 'confirmationModal') hideConfirmation();
        }
    </script>
</body>

</html>