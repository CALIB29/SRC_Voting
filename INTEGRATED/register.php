<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id   = sanitizeInput($_POST['student_id']);
    $gmail        = sanitizeInput($_POST['gmail']); // ADD GMAIL
    $first_name   = sanitizeInput($_POST['first_name']);
    $middle_name  = sanitizeInput($_POST['middle_name']);
    $last_name    = sanitizeInput($_POST['last_name']);
    $course       = sanitizeInput($_POST['course']);
    $year_section = sanitizeInput($_POST['year_section']);
    $password     = $_POST['password']; // No hashing as requested
    $confirm_password = $_POST['confirm_password'];

    // Student ID validation
    if (!ctype_digit($student_id)) {
        $error = "Student ID must contain numbers only.";
    }

    // Password match check
    if (empty($error) && $password !== $confirm_password) {
        $error = "Passwords do not match.";
    }

    // Gmail validation
    if (empty($error) && !filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid Gmail address.";
    } elseif (empty($error) && !preg_match('/@gmail\.com$/', $gmail)) {
        $error = "Only Gmail addresses are allowed.";
    }

    // File upload
    $school_id_path = '';
    if (empty($error)) {
        if (isset($_FILES['school_id']) && $_FILES['school_id']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = UPLOAD_DIR . 'school_ids/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = pathinfo($_FILES['school_id']['name'], PATHINFO_EXTENSION);
            $school_id_path = $upload_dir . $student_id . '.' . $file_ext;
            
            if (!move_uploaded_file($_FILES['school_id']['tmp_name'], $school_id_path)) {
                $error = "Failed to upload school ID.";
            }
        } else {
            $error = "Please upload a valid school ID.";
        }
    }

    // Save to DB
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (student_id, gmail, first_name, middle_name, last_name, course, year_section, password, school_id_path) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $gmail, $first_name, $middle_name, $last_name, $course, $year_section, $password, $school_id_path]);
            
            $success = "Registration successful! Please wait for admin approval.";
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome CDN for eye icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            width: 100%;
            padding-right: 40px; /* space para sa icon */
        }
        .toggle-icon {
            position: absolute;
            right: 10px;
            font-size: 16px;
            color: #555;
            cursor: pointer;
        }
        .toggle-icon:hover {
            color: #1877f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2>User Registration</h2>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form id="regForm" action="register.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="student_id">Student ID No. <span style="color:red;">*</span></label>
                    <input type="text" id="student_id" name="student_id" required 
                           pattern="[0-9]+" inputmode="numeric" 
                           placeholder="e.g. 220000000"
                           title="Enter your Student ID (numbers only)">
                    <small id="student_id_error" style="color:red; display:none;">Numbers only are allowed.</small>
                </div>
                <div class="form-group">
                    <label for="gmail">Email <span style="color:red;">*</span></label>
                    <input type="text" id="gmail" name="gmail" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name <span style="color:red;">*</span></label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name <span style="color:red;">*</span></label>
                    <input type="text" id="last_name" name="last_name" required>
                </iv>
                <div class="form-group">
                    <label for="course">Department <span style="color:red;">*</span></label>
                    <select id="course" name="course" required>
                        <option value="">Select Department</option>
                        <option value="ELEMENTARY">Elementary Department</option>
                        <option value="IHS">Integrated High School</option>
                        <option value="CBS">College of Business Study</option>
                        <option value="CCS">College of Computer Study</option>
                        <option value="COE">College of Education</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year_section">Year and Section <span style="color:red;">*</span></label>
                    <input type="text" id="year_section" name="year_section" required>
                </div>
                <div class="form-group">
                    <label for="password">Password <span style="color:red;">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <i class="fa-solid fa-eye toggle-icon" id="togglePassword"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span style="color:red;">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fa-solid fa-eye toggle-icon" id="toggleConfirmPassword"></i>
                    </div>
                    <small id="password_error" style="color:red; display:none;">Passwords do not match.</small>
                </div>
                <div class="form-group">
                    <label for="school_id">Upload School ID (this year) <span style="color:red;">*</span></label>
                    <input type="file" id="school_id" name="school_id" accept="image/*" required>
                </div>
                <button type="submit" class="btn">Register</button>
            </form>
            <p><small><span style="color:red;">*</span> Required fields</small></p>
            <p>Already registered? <a href="index.php">Login here</a></p>
        </div>
    </div>

    <!-- JS Validation -->
    <script>
    // Student ID validation
    document.getElementById('student_id').addEventListener('input', function(e) {
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

    form.addEventListener('submit', function(e) {
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
