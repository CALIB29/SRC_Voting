<?php
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['path' => '/']); session_start(); }

// -- 1. DATABASE CONNECTION --
require_once 'includes/db_connections.php';

// -- 2. SECURITY CHECK --
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['db_name']) || $_SESSION['db_name'] !== 'srcvotin_registrar') {
    $_SESSION['alert_message'] = 'Unauthorized Access. Please login again.';
    $_SESSION['alert_type']    = 'alert-danger';
    header("Location: dashboard.php");
    exit;
}

// -- 3. FILE UPLOAD VALIDATION --
if (!isset($_FILES['student_csv']) || !is_uploaded_file($_FILES['student_csv']['tmp_name'])) {
    $error_message = "Error: No file was received by the server.";

    if (isset($_FILES['student_csv']['error'])) {
        switch ($_FILES['student_csv']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "Error: The uploaded file exceeds the server's file size limit.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "Error: No file was selected for upload.";
                break;
            default:
                $error_message = "An unknown upload error occurred. Error code: " . $_FILES['student_csv']['error'];
                break;
        }
    }

    $_SESSION['alert_message'] = $error_message;
    $_SESSION['alert_type']    = 'alert-danger';
    header('Location: dashboard.php');
    exit;
}

// -- 4. START IMPORT PROCESS --
$total_rows           = 0;
$imported_count       = 0;
$import_errors        = [];
$has_duplicate_error  = false; // Flag to track duplicate entries
$file_tmp_path        = $_FILES['student_csv']['tmp_name'];

if (($handle = fopen($file_tmp_path, 'r')) !== FALSE) {

    fgetcsv($handle); // Skip header row

    $row_number = 1;
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $row_number++;
        $total_rows++;

        if (count($data) !== 7) {
            $import_errors[] = "Row {$row_number}: Incorrect column count (expected 7).";
            continue;
        }

        $department_name_from_csv = trim($data[5]);
        if (!isset($department_db_map[$department_name_from_csv])) {
            $import_errors[] = "Row {$row_number}: Unknown department in config map: '{$department_name_from_csv}'.";
            continue;
        }

        $target_db_name = $department_db_map[$department_name_from_csv];
        if (!isset($connections[$target_db_name])) {
            $import_errors[] = "Row {$row_number}: No database connection for department: '{$department_name_from_csv}'.";
            continue;
        }

        $pdo = $connections[$target_db_name];

        try {
            $student_id = trim($data[0]);
            $first_name = trim($data[1]);
            $last_name  = trim($data[3]);

            if (empty($student_id) || empty($first_name) || empty($last_name)) {
                $import_errors[] = "Row {$row_number}: Missing required data (Student ID, First Name, or Last Name).";
                continue;
            }

            $hashed_password = password_hash($student_id, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (student_id, gmail, first_name, middle_name, last_name, course, year_section, password, is_approved, has_voted)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $student_id, trim($data[4]), $first_name, trim($data[2]),
                $last_name, $department_name_from_csv, trim($data[6]),
                $hashed_password, 1, 0
            ]);

            $imported_count++;

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $import_errors[] = "Row {$row_number}: Failed due to duplicate Student ID: '{$student_id}'.";
                $has_duplicate_error = true; // Set the duplicate flag
            } else {
                $import_errors[] = "Row {$row_number}: A database error occurred: " . $e->getMessage();
            }
        }
    }
    fclose($handle);

    // -- 5. PREPARE FINAL SESSION MESSAGES BASED ON OUTCOME --
    $_SESSION['import_errors'] = $import_errors;

    if ($has_duplicate_error) {
        // Rule: Any duplicate fails the entire import. RED alert.
        $_SESSION['alert_type'] = 'alert-danger';
        $_SESSION['alert_message'] = "Import Failed. Duplicate student records were found. No records were imported if duplicates were present in the file.";
    } elseif ($imported_count === $total_rows && $total_rows > 0) {
        // Rule: 100% success. GREEN alert.
        $_SESSION['alert_type'] = 'alert-success';
        $_SESSION['alert_message'] = "Import Successful. All <strong>{$imported_count}</strong> student records were imported.";
    } elseif ($imported_count > 0 && $imported_count < $total_rows) {
        // Rule: Partial success with non-duplicate errors. YELLOW alert.
        $_SESSION['alert_type'] = 'alert-warning';
        $_SESSION['alert_message'] = "Import Complete with Warnings. Successfully imported <strong>{$imported_count}</strong> of <strong>{$total_rows}</strong> records.";
    } else {
        // Rule: Zero records imported for various reasons. RED alert.
        $_SESSION['alert_type'] = 'alert-danger';
        $_SESSION['alert_message'] = "Import Failed. No records were imported. Please check the errors below.";
    }

} else {
    $_SESSION['alert_message'] = 'Error: Could not open the uploaded CSV file.';
    $_SESSION['alert_type'] = 'alert-danger';
}

header('Location: dashboard.php');
exit;
?>