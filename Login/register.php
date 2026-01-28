<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Mapping ng label → upload folder name
$folderMap = [
    "Elementary Department" => "ELEMENTARY",
    "Integrated High School" => "INTEGRATED",
    "College of Business Studies" => "CBS VOTING",
    "College of Computer Studies" => "CCS VOTING",
    "College of Education" => "COE VOTING"
];

define('UPLOAD_DIR', __DIR__ . '/uploads/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_id = sanitizeInput($_POST['student_id']);
    $gmail = sanitizeInput($_POST['gmail']);
    $first_name = sanitizeInput($_POST['first_name']);
    $middle_name = sanitizeInput($_POST['middle_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $courseLabel = sanitizeInput($_POST['course']);
    $year_section = sanitizeInput($_POST['year_section']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (!ctype_digit($student_id)) {
        $error = "Student ID must contain numbers only.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($gmail, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@gmail\\.com$/', $gmail)) {
        $error = "Please enter a valid Gmail address ending with @gmail.com.";
    } elseif (!array_key_exists($courseLabel, $databaseMap)) {
        $error = "Invalid department selected.";
    }

    // File upload
    $school_id_path = '';
    if (empty($error) && isset($_FILES['school_id']) && $_FILES['school_id']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/assets/images/school_ids/" . $folderMap[$courseLabel] . "/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['school_id']['name'], PATHINFO_EXTENSION);
        $newFileName = $student_id . '.' . $file_ext;
        $file_path = $upload_dir . $newFileName;
        $school_id_path = "assets/images/school_ids/" . $folderMap[$courseLabel] . "/" . $newFileName;
        if (!move_uploaded_file($_FILES['school_id']['tmp_name'], $file_path)) {
            $error = "Failed to upload school ID.";
        }
    } elseif (empty($error)) {
        $error = "Please upload a valid school ID.";
    }

    // Check duplicates in unified src_db.students
    if (empty($error)) {
        try {
            // use $pdo from includes/db_connect.php (src_db)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
            $stmtCheck->execute([$student_id]);
            $countID = $stmtCheck->fetchColumn();

            $stmtCheckEmail = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
            $stmtCheckEmail->execute([$gmail]);
            $countEmail = $stmtCheckEmail->fetchColumn();

            if ($countID > 0) {
                $error = "Student ID already exists.";
            } elseif ($countEmail > 0) {
                $error = "Gmail address already exists.";
            }
        } catch (PDOException $e) {
            $error = "Error checking duplicates: " . $e->getMessage();
        }
    }

    // Insert into unified src_db.students
    if (empty($error)) {
        try {
            // Minimal mapping into students table; extra fields (course/year_section) are not stored here
            $stmt = $pdo->prepare("INSERT INTO students
                (student_id, rfid_number, profile_picture, first_name, middle_name, last_name, suffix, gender, email, password, is_approved, has_voted)
                VALUES (?, '', ?, ?, ?, ?, '', 'Male', ?, ?, 0, 0)");
            $stmt->execute([
                $student_id,
                $school_id_path,
                $first_name,
                $middle_name,
                $last_name,
                $gmail,
                $password
            ]);

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
<title>User Registration - SRC Voting System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
--primary-color: #667eea;
--primary-dark: #5a67d8;
--secondary-color: #764ba2;
--success-color: #10b981;
--error-color: #ef4444;
--warning-color: #f59e0b;
--text-primary: #1f2937;
--text-secondary: #6b7280;
--bg-primary: #f8fafc;
--bg-white: #ffffff;
--border-color: #e5e7eb;
--border-focus: #667eea;
--shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
--shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
--shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
--shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
--radius: 12px;
--radius-sm: 8px;
--transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
box-sizing: border-box;
margin: 0;
padding: 0;
}

body {
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
background-color: var(--bg-primary);
min-height: 100vh;
display: flex;
align-items: center;
justify-content: center;
padding: 20px;
}

.container {
width: 100%;
max-width: 480px;
}

.registration-form {
background: var(--bg-white);
border-radius: var(--radius);
box-shadow: var(--shadow-xl);
overflow: hidden;
}

/* Header Section - Updated to white background */
.form-header {
background-color: var(--bg-white);
padding: 40px 30px 30px;
text-align: center;
border-bottom: 1px solid var(--border-color);
}

.logo-container {
position: relative;
}

.logo {
width: 80px;
height: 80px;
border-radius: 50%;
border: 4px solid var(--border-color);
box-shadow: var(--shadow-lg);
transition: var(--transition);
object-fit: cover;
}

.logo:hover {
transform: scale(1.05);
border-color: var(--primary-color);
}

.school-name {
color: var(--text-primary);
font-size: 18px;
font-weight: 600;
margin-top: 15px;
}

.form-title {
color: var(--text-secondary);
font-size: 14px;
font-weight: 400;
margin-top: 5px;
text-transform: uppercase;
letter-spacing: 1px;
}

/* Form Content */
.form-content {
padding: 40px 30px 30px;
}

.form-grid {
display: grid;
gap: 24px;
}

.form-group {
position: relative;
}

.form-group.half {
display: grid;
grid-template-columns: 1fr 1fr;
gap: 16px;
}

label {
display: block;
font-size: 14px;
font-weight: 500;
color: var(--text-primary);
margin-bottom: 8px;
transition: var(--transition);
}

.required {
color: var(--error-color);
margin-left: 4px;
}

/* Input Styles */
.input-wrapper {
position: relative;
}

input[type="text"],
input[type="password"],
input[type="email"],
select {
width: 100%;
padding: 14px 16px;
border: 2px solid var(--border-color);
border-radius: var(--radius-sm);
font-size: 15px;
font-weight: 400;
color: var(--text-primary);
background: var(--bg-white);
transition: var(--transition);
outline: none;
}

input[type="text"]:focus,
input[type="password"]:focus,
input[type="email"]:focus,
select:focus {
border-color: var(--border-focus);
box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
transform: translateY(-1px);
}

input[type="text"]:hover,
input[type="password"]:hover,
input[type="email"]:hover,
select:hover {
border-color: var(--border-focus);
}

/* Password Toggle */
.password-wrapper {
position: relative;
}

.toggle-icon {
position: absolute;
right: 16px;
top: 50%;
transform: translateY(-50%);
cursor: pointer;
color: var(--text-secondary);
font-size: 16px;
transition: var(--transition);
user-select: none;
}

.toggle-icon:hover {
color: var(--primary-color);
transform: translateY(-50%) scale(1.1);
}

/* File Upload */
input[type="file"] {
padding: 12px 16px;
border: 2px dashed var(--border-color);
border-radius: var(--radius-sm);
background: #f9fafb;
cursor: pointer;
transition: var(--transition);
}

input[type="file"]:hover {
border-color: var(--border-focus);
background: #f3f4f6;
}

input[type="file"]:focus {
border-color: var(--border-focus);
box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Select Dropdown */
select {
cursor: pointer;
appearance: none;
background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
background-position: right 12px center;
background-repeat: no-repeat;
background-size: 16px;
padding-right: 48px;
}

/* Button */
.btn {
width: 100%;
padding: 16px 24px;
background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
border: none;
border-radius: var(--radius-sm);
color: white;
font-size: 16px;
font-weight: 600;
cursor: pointer;
transition: var(--transition);
position: relative;
overflow: hidden;
margin-top: 12px;
}

.btn::before {
content: '';
position: absolute;
top: 0;
left: -100%;
width: 100%;
height: 100%;
background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
transition: var(--transition);
}

.btn:hover::before {
left: 100%;
}

.btn:hover {
transform: translateY(-2px);
box-shadow: var(--shadow-lg);
}

.btn:active {
transform: translateY(0);
}

/* Alerts */
.alert {
padding: 16px 20px;
border-radius: var(--radius-sm);
margin-bottom: 24px;
font-weight: 500;
display: flex;
align-items: center;
gap: 12px;
animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
from {
opacity: 0;
transform: translateY(-10px);
}
to {
opacity: 1;
transform: translateY(0);
}
}

.alert.error {
background: #fef2f2;
color: var(--error-color);
border: 1px solid #fecaca;
}

.alert.success {
background: #ecfdf5;
color: var(--success-color);
border: 1px solid #a7f3d0;
}

.alert::before {
font-family: "Font Awesome 6 Free";
font-weight: 900;
font-size: 16px;
}

.alert.error::before {
content: "\\f071";
}

.alert.success::before {
content: "\\f00c";
}

/* Error Messages */
.error-message {
color: var(--error-color);
font-size: 12px;
margin-top: 6px;
display: none;
animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
from { opacity: 0; }
to { opacity: 1; }
}

/* Footer */
.form-footer {
padding: 0 30px 30px;
text-align: center;
}

.form-footer p {
color: var(--text-secondary);
font-size: 14px;
margin-top: 20px;
}

.form-footer a {
color: var(--primary-color);
text-decoration: none;
font-weight: 500;
transition: var(--transition);
}

.form-footer a:hover {
color: var(--primary-dark);
text-decoration: underline;
}

.required-note {
color: var(--text-secondary);
font-size: 12px;
margin-bottom: 16px;
}

/* Responsive Design */
@media (max-width: 480px) {
.container {
padding: 0 10px;
}

.form-content,
.form-footer {
padding-left: 20px;
padding-right: 20px;
}

.form-header {
padding: 30px 20px 25px;
}

.form-group.half {
grid-template-columns: 1fr;
gap: 20px;
}

.logo {
width: 70px;
height: 70px;
}

.school-name {
font-size: 16px;
}
}

/* Loading Animation */
.btn.loading {
pointer-events: none;
opacity: 0.8;
}

.btn.loading::after {
content: '';
position: absolute;
width: 20px;
height: 20px;
border: 2px solid transparent;
border-top: 2px solid white;
border-radius: 50%;
animation: spin 1s linear infinite;
top: 50%;
left: 50%;
transform: translate(-50%, -50%);
}

@keyframes spin {
0% { transform: translate(-50%, -50%) rotate(0deg); }
100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>
</head>
<body>
<div class="container">
<div class="registration-form">
<!-- Header Section -->
<div class="form-header">
<div class="logo-container">
<img src="/CCS VOTING/pic/srclogo.png" alt="School Logo" class="logo">
<div class="school-name">SRC Voting System</div>
<div class="form-title">Student Registration</div>
</div>
</div>

<!-- Form Content -->
<div class="form-content">
<?php if (isset($error)): ?>
<div class="alert error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (isset($success)): ?>
<div class="alert success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form id="regForm" action="" method="post" enctype="multipart/form-data">
<div class="form-grid">

<!-- Student ID -->
<div class="form-group">
<label for="student_id">Student ID Number <span class="required">*</span></label>
<div class="input-wrapper">
<input type="text" id="student_id" name="student_id" required pattern="[0-9]+" inputmode="numeric" placeholder="e.g. 220000000">
</div>
<div class="error-message" id="student_id_error">
Numbers only are allowed.
</div>
</div>

<!-- Gmail -->
<div class="form-group">
<label for="gmail">Gmail Address <span class="required">*</span></label>
<div class="input-wrapper">
<input type="email" id="gmail" name="gmail" required placeholder="youremail@gmail.com">
</div>
<div class="error-message" id="gmail_error">
Please enter a valid Gmail address ending with @gmail.com.
</div>
</div>

<!-- Name Fields -->
<div class="form-group half">
<div>
<label for="first_name">First Name <span class="required">*</span></label>
<input type="text" id="first_name" name="first_name" required>
</div>
<div>
<label for="last_name">Last Name <span class="required">*</span></label>
<input type="text" id="last_name" name="last_name" required>
</div>
</div>

<!-- Middle Name -->
<div class="form-group">
<label for="middle_name">Middle Name</label>
<input type="text" id="middle_name" name="middle_name">
</div>

<!-- Department -->
<div class="form-group">
<label for="course">Department <span class="required">*</span></label>
<select id="course" name="course" required>
<option value="">Select your department</option>
<option value="Elementary Department">Elementary Department</option>
<option value="Integrated High School">Integrated High School</option>
<option value="College of Business Studies">College of Business Studies</option>
<option value="College of Computer Studies">College of Computer Studies</option>
<option value="College of Education">College of Education</option>
</select>
</div>

<!-- Year and Section -->
<div class="form-group">
<label for="year_section">Year and Section <span class="required">*</span></label>
<input type="text" id="year_section" name="year_section" required placeholder="e.g. 4th Year - Section A">
</div>

<!-- Password Fields -->
<div class="form-group half">
<div>
<label for="password">Password <span class="required">*</span></label>
<div class="password-wrapper">
<input type="password" id="password" name="password" required>
<i class="fa-solid fa-eye toggle-icon" id="togglePassword"></i>
</div>
</div>
<div>
<label for="confirm_password">Confirm Password <span class="required">*</span></label>
<div class="password-wrapper">
<input type="password" id="confirm_password" name="confirm_password" required>
<i class="fa-solid fa-eye toggle-icon" id="toggleConfirmPassword"></i>
</div>
</div>
</div>

<div class="error-message" id="password_error">
Passwords do not match.
</div>

<!-- File Upload -->
<div class="form-group">
<label for="school_id">Upload COR (Current Year) <span class="required">*</span></label>
<input type="file" id="school_id" name="school_id" accept="image/*" required>
</div>

<div class="required-note">
<span class="required">*</span> Required fields
</div>

<button type="submit" class="btn" id="submitBtn">
Create Account
</button>
</div>
</form>
</div>

<!-- Footer -->
<div class="form-footer">
<p>Already have an account? <a href="index.php">Sign in here</a></p>
</div>
</div>
</div>

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

// Gmail validation
document.getElementById('gmail').addEventListener('input', function(e) {
const value = e.target.value;
const errorMsg = document.getElementById('gmail_error');
const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
if (value && !gmailRegex.test(value)) {
errorMsg.style.display = "block";
} else {
errorMsg.style.display = "none";
}
});

// Password match validation
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
}
passError.style.display = "none";
return true;
}

password.addEventListener('input', validatePassword);
confirm_password.addEventListener('input', validatePassword);

// Form submission
form.addEventListener('submit', function(e) {
const submitBtn = document.getElementById('submitBtn');

// Password check
if (!validatePassword()) {
e.preventDefault();
alert("Passwords do not match.");
return false;
}

// Gmail check
const gmailInput = document.getElementById('gmail');
const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
if (!gmailRegex.test(gmailInput.value)) {
e.preventDefault();
alert("Please enter a valid Gmail address ending with @gmail.com.");
return false;
}

// Show loading state
submitBtn.classList.add('loading');
submitBtn.innerHTML = 'Creating Account...';
});

// Password toggle functionality
function setupToggle(toggleId, inputId) {
const toggle = document.getElementById(toggleId);
const input = document.getElementById(inputId);
toggle.addEventListener("click", function() {
const type = input.getAttribute("type") === "password" ? "text" : "password";
input.setAttribute("type", type);
this.classList.toggle("fa-eye-slash");
this.classList.toggle("fa-eye");
});
}

setupToggle("togglePassword", "password");
setupToggle("toggleConfirmPassword", "confirm_password");

// Add smooth focus animations
const inputs = document.querySelectorAll('input, select');
inputs.forEach(input => {
input.addEventListener('focus', function() {
this.parentElement.classList.add('focused');
});

input.addEventListener('blur', function() {
this.parentElement.classList.remove('focused');
});
});
</script>
</body>
</html>