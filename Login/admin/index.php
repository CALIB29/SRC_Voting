<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';

$department_paths = [
    'College of Computer Studies' => 'CCSVOTING/admin/dashboard.php',
    'College of Business Studies' => 'CBSVOTING/admin/dashboard.php',
    'College of Education'        => 'COEVOTING/admin/dashboard.php',
    'Elementary Department'       => 'ELEMENTARY/admin/dashboard.php',
    'Integrated High School'      => 'INTEGRATED/admin/dashboard.php',
    'Registrar'                   => 'registrar/dashboard.php'
];

if (isset($_SESSION['admin_id']) && isset($_SESSION['department_name']) && isset($department_paths[$_SESSION['department_name']])) {
    header("Location: " . BASE_URL . $department_paths[$_SESSION['department_name']]);
    exit;
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
        $dept = $_SESSION['department_name'] ?? '';
        if (isset($department_paths[$dept])) {
            header('Location: ' . BASE_URL . $department_paths[$dept]);
        } else {
            header('Location: ' . BASE_URL . 'admin/login.php');
        }
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Admin Login Redirector</title></head>
<body>
    <div style="max-width:400px; margin: 100px auto; font-family: sans-serif; padding: 20px; border: 1px solid #ccc;">
        <h2>Admin Authentication</h2>
        <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST">
            <p>Email: <br><input type="text" name="username" style="width:100%; padding:8px;"></p>
            <p>Password: <br><input type="password" name="password" style="width:100%; padding:8px;"></p>
            <p><button type="submit" style="width:100%; padding:10px; background:#4CAF50; color:white; border:none; border-radius:4px;">Login</button></p>
        </form>
    </div>
</body>
</html>