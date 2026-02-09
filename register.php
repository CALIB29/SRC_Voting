<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Fetch courses from the database to populate the dropdown
$courses = [];
try {
    $stmt = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error if courses can't be fetched
    $error = "Could not load courses. Please try again later.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = sanitizeInput($_POST['student_id']);
    $email = sanitizeInput($_POST['email']);
    $first_name = sanitizeInput($_POST['first_name']);
    $middle_name = sanitizeInput($_POST['middle_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $course_id = sanitizeInput($_POST['course_id']);
    $password = $_POST['password']; // No hashing as requested
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }

    if (empty($student_id) || empty($email) || empty($first_name) || empty($last_name) || empty($course_id) || empty($password)) {
        $error = "Please fill in all required fields.";
    }

    // If validation passes, proceed to insert into the database
    if (empty($error)) {
        try {
            // First, get the department_id from the selected course_id
            $stmt = $pdo->prepare("SELECT department_id FROM courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $course_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($course_data) {
                $department_id = $course_data['department_id'];

                // Now, insert the new student into the students table
                $stmt = $pdo->prepare(
                    "INSERT INTO students (student_id, rfid_number, first_name, middle_name, last_name, email, password, course_id, department_id, is_approved, has_voted) " .
                    "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)"
                );
                // Using student_id as a placeholder for rfid_number
                $stmt->execute([$student_id, $student_id, $first_name, $middle_name, $last_name, $email, $password, $course_id, $department_id]);

                $success = "Registration successful! Please wait for admin approval.";
            } else {
                $error = "Invalid course selected.";
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Handle duplicate entry
                $error = "A student with this ID or email already exists.";
            } else {
                $error = "Registration failed: An error occurred.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
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
            max-width: 500px;
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
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.85rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
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
            margin-top: 1rem;
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

        /* select option styling for some browsers */
        option {
            background-color: #1E293B;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="registration-form">
            <div class="logo">
                <img src="logo/srclogo.png" alt="System Logo">
            </div>
            <h2>Student Registration</h2>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form id="regForm" action="register.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="student_id">Student ID No. <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="student_id" name="student_id" required pattern="[0-9]+" inputmode="numeric"
                        placeholder="e.g. 220000000" title="Enter your Student ID (numbers only)">
                    <small id="student_id_error" style="color:var(--danger); display:none;">Numbers only are
                        allowed.</small>
                </div>
                <div class="form-group">
                    <label for="email">Email <span style="color:var(--danger);">*</span></label>
                    <input type="email" id="email" name="email" required placeholder="e.g. student@gmail.com">
                </div>
                <div class="form-group">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label for="first_name">First Name <span style="color:var(--danger);">*</span></label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div>
                            <label for="last_name">Last Name <span style="color:var(--danger);">*</span></label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name">
                </div>
                <div class="form-group">
                    <label for="course_id">Course <span style="color:var(--danger);">*</span></label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Select your course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="password">Password <span style="color:var(--danger);">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <i class="fa-solid fa-eye toggle-icon" id="togglePassword"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span style="color:var(--danger);">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fa-solid fa-eye toggle-icon" id="toggleConfirmPassword"></i>
                    </div>
                    <small id="password_error" style="color:var(--danger); display:none;">Passwords do not
                        match.</small>
                </div>
                <button type="submit" class="btn">Register</button>
            </form>
            <p><small><span style="color:var(--danger);">*</span> Required fields</small></p>
            <p>Already registered? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <!-- JS Validation -->
    <script>
        // Student ID validation
        document.getElementById('student_id').addEventListener('input', function (e) {
            const value = e.target.value;
            const errorMsg = document.getElementById('student_id_error');
            if (/[^0-9]/.test(value)) {
                errorMsg.style.display = "block";
                e.target.value = value.replace(/[^0-9]/g, '');
            } else {
                errorMsg.style.display = "none";
            }
        });

        // Confirm password validation
        const password = document.getElementById('password');
        const confirm_password = document.getElementById('confirm_password');
        const passError = document.getElementById('password_error');
        const form = document.getElementById('regForm');

        function validatePassword() {
            if (confirm_password.value.length === 0) {
                passError.style.display = "none";
                return false;
            }
            if (password.value !== confirm_password.value) {
                passError.style.display = "block";
                return false;
            } else {
                passError.style.display = "none";
                return true;
            }
        }

        password.addEventListener('input', validatePassword);
        confirm_password.addEventListener('input', validatePassword);

        form.addEventListener('submit', function (e) {
            if (!validatePassword()) {
                e.preventDefault();
                alert("Passwords do not match. Please try again.");
            }
        });

        // Eye icon toggle
        function setupToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);

            toggle.addEventListener("click", function () {
                const type = input.getAttribute("type") === "password" ? "text" : "password";
                input.setAttribute("type", type);

                if (type === "password") {
                    this.classList.remove("fa-eye-slash");
                    this.classList.add("fa-eye");
                } else {
                    this.classList.remove("fa-eye");
                    this.classList.add("fa-eye-slash");
                }
            });
        }

        setupToggle("togglePassword", "password");
        setupToggle("toggleConfirmPassword", "confirm_password");
    </script>
</body>

</html>