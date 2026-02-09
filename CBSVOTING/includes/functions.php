<?php
/**
 * Helper functions for the voting system
 */

/**
 * Sanitize input data
 * @param string $data The input data to sanitize
 * @return string The sanitized data
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirect to a specified URL
 * @param string $url The URL to redirect to
 * @param int $statusCode HTTP status code (default: 303)
 */
function redirect($url, $statusCode = 303)
{
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isUserLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 * @return bool True if admin is logged in, false otherwise
 */
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

/**
 * Generate a random string (for potential password resets or tokens)
 * @param int $length Length of the random string
 * @return string The generated random string
 */
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Format date for display
 * @param string $date The date string to format
 * @param string $format The format to use (default: 'F j, Y, g:i a')
 * @return string The formatted date
 */
function formatDate($date, $format = 'F j, Y, g:i a')
{
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * Get the current URL
 * @return string The current URL
 */
function getCurrentUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Validate image upload
 * @param array $file The $_FILES array element
 * @param int $maxSize Maximum file size in bytes (default: 2MB)
 * @return array Array with 'valid' boolean and 'message' string
 */
function validateImageUpload($file, $maxSize = 2097152)
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'message' => 'File upload error'];
    }

    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'message' => 'File is too large. Maximum size is ' . ($maxSize / 1024 / 1024) . 'MB'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        return ['valid' => false, 'message' => 'Only JPG, PNG, and GIF files are allowed'];
    }

    return ['valid' => true, 'message' => 'File is valid'];
}

/**
 * Get user by ID
 * @param PDO $pdo Database connection
 * @param int $id User ID
 * @return array|false User data or false if not found
 */
function getUserById($pdo, $id)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get candidate by ID
 * @param PDO $pdo Database connection
 * @param int $id Candidate ID
 * @return array|false Candidate data or false if not found
 */
function getCandidateById($pdo, $id)
{
    $stmt = $pdo->prepare("SELECT * FROM vot_candidates WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Check if student ID already exists
 * @param PDO $pdo Database connection
 * @param string $student_id Student ID to check
 * @return bool True if exists, false otherwise
 */
function studentIdExists($pdo, $student_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE student_id = ?");
    $stmt->execute([$student_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get all positions from vot_candidates table
 * @param PDO $pdo Database connection
 * @return array Array of distinct positions
 */
function getAllPositions($pdo)
{
    $stmt = $pdo->query("SELECT DISTINCT position FROM vot_candidates ORDER BY position");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Centered Admin Authentication Logic
 */
function authenticateAdmin($pdo, $email, $password)
{
    try {
        $stmt = $pdo->prepare("
            SELECT e.*, d.department_name, r.role_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.department_id
            LEFT JOIN roles r ON e.role_id = r.role_id
            WHERE e.email = ?
        ");
        $stmt->execute([$email]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            if (password_verify($password, $employee['password']) || $password === $employee['password']) {
                return [
                    'success' => true,
                    'data' => [
                        'admin_id' => $employee['employee_id'],
                        'admin_name' => $employee['firstname'] . " " . $employee['lastname'],
                        'admin_role' => $employee['role_name'],
                        'department_id' => $employee['department_id'],
                        'department_name' => $employee['department_name']
                    ]
                ];
            }
        }
        return ['success' => false, 'message' => "Invalid email or password."];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => "Login failed: " . $e->getMessage()];
    }
}

/**
 * Get vote results by position
 * @param PDO $pdo Database connection
 * @param string $position The position to get results for
 * @return array Array of candidates with their vote counts
 */
function getVoteResultsByPosition($pdo, $position)
{
    $stmt = $pdo->prepare("SELECT * FROM vot_candidates WHERE position = ? ORDER BY votes DESC");
    $stmt->execute([$position]);
    return $stmt->fetchAll();
}

/**
 * Render Mobile Top Bar
 */
function renderMobileTopBar($title = "SRC Voting")
{
    $logo_path = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../pic/srclogo.png' : 'logo/srclogo.png';
    // Deep search for logo if path doesn't exist
    if (!file_exists($logo_path)) {
        $logo_path = (strpos($_SERVER['PHP_SELF'], 'VOTING') !== false) ? '../logo/srclogo.png' : 'logo/srclogo.png';
    }

    echo '
    <div class="mobile-top-bar mobile-only">
        <div class="brand">
            <img src="' . $logo_path . '" alt="Logo" class="mobile-logo" onerror="this.src=\'../logo/srclogo.png\'">
            <span>' . htmlspecialchars($title) . '</span>
        </div>
        <div class="actions">
            <i class="fas fa-bell"></i>
        </div>
    </div>';
}

/**
 * Render Mobile Bottom Navigation
 */
function renderMobileBottomNav($context = 'student')
{
    $current_page = basename($_SERVER['PHP_SELF']);

    if ($context === 'admin') {
        $items = [
            ['dashboard.php', 'fa-chart-pie', 'Home'],
            ['manage_users.php', 'fa-users', 'Voters'],
            ['manage_candidates.php', 'fa-user-tie', 'Candidates'],
            ['results.php', 'fa-poll', 'Results'],
            ['logout.php', 'fa-sign-out-alt', 'Out']
        ];
    } else {
        $items = [
            ['dashboard.php', 'fa-home', 'Home'],
            ['vote.php', 'fa-vote-yea', 'Vote'],
            ['view.php', 'fa-users', 'Candidates'],
            ['resultview.php', 'fa-chart-bar', 'Results'],
            ['logout.php', 'fa-sign-out-alt', 'Out']
        ];
    }

    echo '<div class="mobile-bottom-nav mobile-only">';
    foreach ($items as $item) {
        $active = ($current_page == $item[0]) ? 'active' : '';
        echo '
        <a href="' . $item[0] . '" class="mobile-nav-item ' . $active . '">
            <i class="fas ' . $item[1] . '"></i>
            <span>' . $item[2] . '</span>
        </a>';
    }
    echo '</div>';
}

/**
 * Fix candidate photo path for display across different directory depths
 * @param string $path The path from database (may contain ../)
 * @param string $prefix Path prefix to reach project root (e.g., '../../')
 * @return string Corrected path
 */
function fixCandidatePhotoPath($path, $prefix = '../../')
{
    if (empty($path)) {
        return $prefix . 'logo/srclogo.png';
    }
    // Remove all leading relative path segments like ../ or ./
    $cleanPath = preg_replace('/^(\.\.\/|\.\/)+/', '', $path);

    // Some legacy paths might just be the filename, assume they are in uploads/candidates/
    if (strpos($cleanPath, 'uploads/') === false) {
        $cleanPath = 'uploads/candidates/' . $cleanPath;
    }

    return $prefix . $cleanPath;
}
