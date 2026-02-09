<?php
require_once '../../includes/config.php';
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Mapping for all departments
$department_admin_map = [
    1 => 'CCSVOTING/admin/dashboard.php',
    2 => 'CBSVOTING/admin/dashboard.php',
    3 => 'COEVOTING/admin/dashboard.php',
    9 => 'ELEMENTARY/admin/dashboard.php',
    4 => 'INTEGRATED/admin/dashboard.php'
];

// If already logged in, redirect based on department
if (isset($_SESSION['admin_id']) && isset($_SESSION['department_id'])) {
    $path = $department_admin_map[$_SESSION['department_id']] ?? null;
    if ($path) {
        header("Location: " . BASE_URL . $path);
        exit;
    }
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    $result = authenticateAdmin($pdo, $email, $password);
    if ($result['success']) {
        foreach ($result['data'] as $key => $value) {
            $_SESSION[$key] = $value;
        }
        $path = $department_admin_map[$_SESSION['department_id']] ?? null;
        if ($path) {
            header("Location: " . BASE_URL . $path);
        } else {
            header("Location: dashboard.php"); // Fallback to local dashboard
        }
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | COEVOTING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .card h2 { margin-top: 0; text-align: center; color: #333; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .alert { background: #fee2e2; color: #b91c1c; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>COEVOTING Login</h2>
        <?php if ($error): ?><div class="alert"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div style="text-align: center; margin-top: 1rem;">
            <a href="../../admin/login.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">Main Admin Portal</a>
        </div>
    </div>
</body>
</html>