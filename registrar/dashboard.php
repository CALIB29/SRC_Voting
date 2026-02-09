<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/']);
    session_start();
}

// --- 1. DATABASE CONNECTION & SETUP ---
require_once 'includes/db_connections.php';

// --- 2. SECURITY CHECK ---
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['db_name']) || $_SESSION['db_name'] !== 'srcvotin_registrar') {
    header("Location: /login/admin/index.php");
    exit;
}

// --- 3. PROFESSIONAL ALERT MESSAGE HANDLING ---
$alert_message = $_SESSION['alert_message'] ?? null;
$alert_type = $_SESSION['alert_type'] ?? '';
$import_errors = $_SESSION['import_errors'] ?? [];
unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['import_errors']);


// --- 4. FETCH STUDENTS FROM ALL DEPARTMENT DATABASES ---
$all_students = [];
$db_errors = [];

if (!empty($department_db_map)) {
    foreach ($department_db_map as $department_name => $dbname) {
        try {
            if (isset($connections[$dbname])) {
                $pdo = $connections[$dbname];
                $stmt = $pdo->query("SELECT student_id, first_name, middle_name, last_name, email AS gmail FROM students");
                $students_from_this_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($students_from_this_db as &$student) {
                    $student['department'] = $department_name;
                }
                unset($student);
                if (!empty($students_from_this_db)) {
                    $all_students = array_merge($all_students, $students_from_this_db);
                }
            } else {
                $db_errors[] = "Configuration error: Connection for database '{$dbname}' was not found.";
            }
        } catch (PDOException $e) {
            $db_errors[] = "Could not fetch data from '{$dbname}'. Error: " . $e->getMessage();
        }
    }
}
// --- End of Data Fetching ---

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - Student Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">

    <style>
        :root {
            --primary-color: #4a6cf7;
            --primary-dark: #3a58d4;
            --primary-light: #eff2ff;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --grey-lightest: #f8fafc;
            --grey-light: #f1f5f9;
            --grey-medium: #e2e8f0;
            --dark-color: #1e293b;
            --light-color: #fff;
            --border-color: #e5e7eb;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --radius: 0.5rem;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--grey-light);
            margin: 0;
            color: #334155;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* === HEADER PARA SA TITLE AT LOGOUT BUTTON === */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .logout-btn {
            background-color: #fee2e2;
            color: #b91c1c;
            text-decoration: none;
            padding: 0.6rem 1.1rem;
            border-radius: var(--radius);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease-in-out;
            border: 1px solid #fca5a5;
        }

        .logout-btn:hover {
            background-color: var(--error-color);
            color: var(--light-color);
        }

        /* == End of Header Styles == */

        .card {
            background: var(--light-color);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 2.5rem;
        }

        .card-header {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .instructions-container {
            background: var(--grey-lightest);
            border: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            border-radius: var(--radius);
        }

        .instructions-container h4 {
            margin-top: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .instructions-container ol {
            padding-left: 20px;
            line-height: 1.8;
            margin: 0;
        }

        .instructions-container code {
            background: #dbe1ff;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.7rem 1.25rem;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .alert {
            border: 1px solid;
            animation: fadeIn 0.5s;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: var(--radius);
            font-weight: 500;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .alert-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .alert-success {
            border-color: #a7f3d0;
            background-color: #d1fae5;
            color: #065f46;
        }

        .alert-warning {
            border-color: #fcd34d;
            background-color: #fef9c3;
            color: #b45309;
        }

        .alert-danger {
            border-color: #fca5a5;
            background-color: #fee2e2;
            color: #991b1b;
        }

        #drop-zone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius);
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            background: var(--grey-lightest);
        }

        #drop-zone.drag-over {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
        }

        #drop-zone .drop-zone-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        #file-name-display {
            font-weight: 600;
            margin-top: 1rem;
            color: var(--dark-color);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1>Registrar Dashboard</h1>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <?php if ($alert_message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?>">
                <div class="alert-heading"><?php echo $alert_message; ?></div>
                <?php if (!empty($import_errors)): ?>
                    <ul style="margin:0; padding-left: 20px; font-size: 0.9rem;">
                        <?php foreach ($import_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><i class="fas fa-file-csv"></i> Import Student Accounts</div>
            <div class="instructions-container">
                <h4><i class="fas fa-info-circle"></i> CSV Import Guidelines</h4>
                <p>To ensure a successful bulk import, please format your CSV file according to the following rules:</p>
                <ol>
                    <li><strong>Required Header Row:</strong> The first row must be headers (e.g.,
                        <code>student_id</code>). It will be skipped.
                    </li>
                    <li><strong>Strict Column Order:</strong> Columns must be in this exact sequence:
                        <code>student_id, first_name, middle_name, last_name, gmail, department, year_section</code>
                    </li>
                    <li><strong>Default Password Policy:</strong> The initial password is the student's <strong>Student
                            ID Number</strong>.</li>
                </ol>
            </div>
            <form action="import_students.php" method="post" enctype="multipart/form-data" id="upload-form">
                <div id="drop-zone">
                    <input type="file" name="student_csv" id="student_csv" accept=".csv" hidden>
                    <i class="fas fa-cloud-upload-alt drop-zone-icon"></i>
                    <p>Drag & drop your CSV file here, or click to select.</p>
                    <div id="file-name-display"></div>
                </div>
                <button type="submit" class="btn"><i class="fas fa-upload"></i> Import Accounts</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-users"></i> Registered Student Roster</div>
            <div class="table-responsive">
                <table id="studentsTable" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Course / Department</th>
                            <th>Gmail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars(trim($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['department']); ?></td>
                                <td><?php echo htmlspecialchars($student['gmail']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- jQuery, Bootstrap & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#studentsTable').DataTable({ "pageLength": 10, "lengthMenu": [10, 25, 50, 100] });

            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('student_csv');
            const fileNameDisplay = document.getElementById('file-name-display');

            function showFileName(file) {
                if (file) { fileNameDisplay.innerHTML = `<i class="fas fa-file-alt"></i> <strong>Selected:</strong> ${file.name}`; }
            }

            if (dropZone) {
                dropZone.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', () => showFileName(fileInput.files[0]));
                dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
                ['dragleave', 'dragend'].forEach(type => { dropZone.addEventListener(type, () => dropZone.classList.remove('drag-over')); });
                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('drag-over');
                    if (e.dataTransfer.files.length) {
                        fileInput.files = e.dataTransfer.files;
                        showFileName(e.dataTransfer.files[0]);
                    }
                });
            }
        });
    </script>
</body>

</html>