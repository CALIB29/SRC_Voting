<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; // No hashing as requested
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE email = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            $_SESSION['admin_id'] = $admin['employee_id'];
            $_SESSION['admin_name'] = $admin['firstname'] . ' ' . $admin['lastname'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error = "Login failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --danger-color: #e63946;
            --success-color: #4cc9f0;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .admin-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        
        .admin-login-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        
        .admin-login-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .admin-login-header h2 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .admin-login-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .admin-login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            height: 90px;
        }
        
        @media (max-width: 480px) {
            .admin-container {
                padding: 1rem;
            }
            
            .admin-login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
            <div class="logo">
                <!-- Replace with your actual logo -->
                <img src="../pic/srclogo.png" alt="University Logo">
            </div>
                <h2>Admin Portal</h2>
                <p>Sign in to access the dashboard</p>
            </div>
            <div class="admin-login-body">
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="index.php" method="post">
                    <div class="form-group">
                        <label for="username">Email</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                    <button type="submit" class="btn">Sign In</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>