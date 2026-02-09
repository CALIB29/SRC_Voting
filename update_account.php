<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Part 1: Handle AJAX request to fetch student data
if (isset($_POST['action']) && $_POST['action'] == 'fetch_student') {
    header('Content-Type: application/json');
    $rfid = sanitizeInput($_POST['rfid_number']);

    if (empty($rfid)) {
        echo json_encode(['success' => false, 'message' => 'RFID number is required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT student_id, rfid_number, first_name, last_name, profile_picture, course_id, department_id FROM students WHERE rfid_number = ?");
        $stmt->execute([$rfid]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Also fetch all courses and departments for the dropdowns
            $courses_stmt = $pdo->query("SELECT course_id, course_name, department_id FROM courses ORDER BY course_name ASC");
            $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

            $depts_stmt = $pdo->query("SELECT department_id, department_name FROM departments");
            $departments = $depts_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            echo json_encode([
                'success' => true,
                'student' => $student,
                'courses' => $courses,
                'departments' => $departments
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No student found with this RFID.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit; // Terminate script after AJAX response
}

// Part 2: Handle form submission for account update
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_account') {
    $rfid_number = sanitizeInput($_POST['rfid_number']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $profile_picture = $_FILES['profile_picture'];
    $course_id = sanitizeInput($_POST['course_id'] ?? '');

    if (empty($rfid_number)) {
        $error = 'An RFID number is required to update an account.';
    } elseif (empty($new_password) && empty($course_id) && (!isset($profile_picture) || $profile_picture['error'] === UPLOAD_ERR_NO_FILE)) {
        $error = 'Please provide at least one field to update (password, profile picture, or course).';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    }

    if (empty($error)) {
        try {
            $update_parts = [];
            $params = [];

            // Handle profile picture upload
            if (isset($profile_picture) && $profile_picture['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/profiles/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);

                $file_extension = pathinfo($profile_picture['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('profile_', true) . '.' . $file_extension;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($profile_picture['tmp_name'], $target_path)) {
                    $update_parts[] = "profile_picture = ?";
                    $params[] = $target_path;
                } else {
                    $error = 'Failed to upload profile picture.';
                }
            }

            // Handle password update
            if (!empty($new_password)) {
                $update_parts[] = "password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            // Handle course and departments update
            if (!empty($course_id)) {
                // First, find the department_id for the given course_id
                $course_stmt = $pdo->prepare("SELECT department_id FROM courses WHERE course_id = ?");
                $course_stmt->execute([$course_id]);
                $course_data = $course_stmt->fetch(PDO::FETCH_ASSOC);

                if ($course_data) {
                    $update_parts[] = "course_id = ?";
                    $params[] = $course_id;
                    $update_parts[] = "department_id = ?";
                    $params[] = $course_data['department_id'];
                } else {
                    $error = 'Invalid course selected.';
                }
            }

            if (!empty($update_parts) && empty($error)) {
                $query = "UPDATE students SET " . implode(', ', $update_parts) . " WHERE rfid_number = ?";
                $params[] = $rfid_number;

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);

                $success = 'Account for RFID ' . htmlspecialchars($rfid_number) . ' updated successfully! Redirecting to login page...';
                // Set a flag for redirection
                $redirect_to_login = true;

            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Account by RFID</title>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Modern Light Blue Theme */
        :root {
            --primary: #3B82F6;
            --primary-glow: rgba(59, 130, 246, 0.4);
            --secondary: #06B6D4;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --gold: #D4AF37;
            --bg-body: #0F172A;
            --bg-card: rgba(30, 41, 59, 0.7);
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --border: rgba(255, 255, 255, 0.1);
            --radius-lg: 24px;
            --radius-md: 16px;
            --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 2rem 0;
            background-image:
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.1) 0px, transparent 50%);
        }

        /* Animated Background Orbs */
        body::before,
        body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
            z-index: 0;
        }

        body::before {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--primary), transparent);
            top: -250px;
            left: -250px;
        }

        body::after {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--secondary), transparent);
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(30px, -30px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .container {
            width: 100%;
            max-width: 450px;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .registration-form {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .registration-form::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--gold));
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px var(--primary-glow));
        }

        h2 {
            font-family: 'Playfair Display', serif;
            text-align: center;
            color: var(--text-main);
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-align: left;
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #FCA5A5;
        }

        .alert.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6EE7B7;
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.85rem;
        }

        input[type="text"],
        input[type="password"],
        input[type="file"],
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: rgba(15, 23, 42, 0.5);
            color: var(--text-main);
            font-size: 0.95rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: var(--transition);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
            background: rgba(15, 23, 42, 0.7);
        }

        input[readonly] {
            opacity: 0.7;
            cursor: not-allowed;
            background: rgba(15, 23, 42, 0.3);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .toggle-icon:hover {
            color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px var(--primary-glow);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--primary-glow);
        }

        p {
            text-align: center;
            margin-top: 1.25rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }

        #student-details-container {
            display: none;
            margin-top: 1.5rem;
            text-align: center;
            background: rgba(59, 130, 246, 0.05);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        #student-profile-pic {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        #student-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 1.1rem;
        }

        #update-form-container {
            display: none;
            margin-top: 1.5rem;
        }

        h3,
        h4 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 1rem;
            color: var(--text-main);
        }

        hr {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        #fetch-status {
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="registration-form">
            <div class="logo"><img src="logo/srclogo.png" alt="System Logo"></div>
            <h2>Update Account by RFID</h2>

            <!-- Step 1: RFID Input -->
            <form id="rfid-fetch-form">
                <div class="form-group">
                    <label for="rfid_input">Scan RFID to Fetch Student</label>
                    <input type="text" id="rfid_input" name="rfid_number" required autofocus
                        placeholder="Scan RFID here...">
                </div>
                <button type="submit" class="btn">Fetch Student</button>
            </form>
            <div id="fetch-status"></div>

            <!-- Step 2: Display Student Info and Update Form -->
            <div id="student-details-container">
                <h3>Student Found</h3>
                <img id="student-profile-pic" src="" alt="Profile Picture">
                <p id="student-name"></p>
            </div>

            <div id="update-form-container">
                <hr>
                <h4>Update Information</h4>
                <?php if (!empty($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div><?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert success"><?php echo $success; ?></div><?php endif; ?>
                <form action="update_account.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_account">
                    <input type="hidden" id="hidden_rfid" name="rfid_number">
                    <div class="form-group">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id">
                            <option value="">Select Course</option>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department_name">departments</label>
                        <input type="text" id="department_name" name="department_name" readonly>
                    </div>
                    <div class="form-group">
                        <label for="profile_picture">New Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password">
                            <i class="fa-solid fa-eye toggle-icon" id="togglePassword"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password">
                            <i class="fa-solid fa-eye toggle-icon" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn">Update Account</button>
                </form>
            </div>

            <p style="margin-top: 20px;"><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <script>
        document.getElementById('rfid-fetch-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const rfidInput = document.getElementById('rfid_input');
            const formData = new FormData();
            formData.append('action', 'fetch_student');
            formData.append('rfid_number', rfidInput.value);

            const statusDiv = document.getElementById('fetch-status');
            const detailsContainer = document.getElementById('student-details-container');
            const updateFormContainer = document.getElementById('update-form-container');

            statusDiv.innerHTML = 'Fetching...';
            detailsContainer.style.display = 'none';
            updateFormContainer.style.display = 'none';

            fetch('update_account.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const { student, courses, departments } = data;
                        statusDiv.innerHTML = '';

                        // Populate student details
                        document.getElementById('student-name').textContent = `Name: ${student.first_name} ${student.last_name}`;
                        const profilePic = document.getElementById('student-profile-pic');
                        profilePic.src = student.profile_picture ? student.profile_picture : 'assets/images/default-avatar.png'; // A better fallback
                        profilePic.alt = `${student.first_name}'s Profile Picture`;
                        document.getElementById('hidden_rfid').value = student.rfid_number;

                        // Populate course dropdown
                        const courseSelect = document.getElementById('course_id');
                        courseSelect.innerHTML = '<option value="">Select Course</option>'; // Clear existing options
                        courses.forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.course_id;
                            option.textContent = course.course_name;
                            option.dataset.departmentId = course.department_id;
                            if (course.course_id == student.course_id) {
                                option.selected = true;
                            }
                            courseSelect.appendChild(option);
                        });

                        // Function to update departments based on selected course
                        const updateDepartmentDisplay = () => {
                            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
                            const departmentId = selectedOption.dataset.departmentId;
                            const departmentNameInput = document.getElementById('department_name');
                            if (departmentId && departments[departmentId]) {
                                departmentNameInput.value = departments[departmentId];
                            } else {
                                departmentNameInput.value = '';
                            }
                        };

                        // Add event listener and trigger it once to set initial state
                        courseSelect.addEventListener('change', updateDepartmentDisplay);
                        updateDepartmentDisplay();

                        // Show the forms
                        detailsContainer.style.display = 'block';
                        updateFormContainer.style.display = 'block';
                        rfidInput.value = ''; // Clear input for next scan
                    } else {
                        statusDiv.innerHTML = `<div class="alert error">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    statusDiv.innerHTML = `<div class="alert error">An error occurred. Please check the console.</div>`;
                    console.error('Error:', error);
                });
        });

        function setupToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            if (toggle && input) {
                toggle.addEventListener("click", function () {
                    const type = input.getAttribute("type") === "password" ? "text" : "password";
                    input.setAttribute("type", type);
                    this.classList.toggle("fa-eye");
                    this.classList.toggle("fa-eye-slash");
                });
            }
        }
        setupToggle("togglePassword", "new_password");
        setupToggle("toggleConfirmPassword", "confirm_password");

        <?php if (isset($redirect_to_login) && $redirect_to_login): ?>
            setTimeout(function () {
                window.location.href = 'login.php';
            }, 3000); // 3-second delay before redirecting
        <?php endif; ?>
    </script>
</body>

</html>