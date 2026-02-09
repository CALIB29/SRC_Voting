<?php
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['path' => '/']); session_start(); }
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = sanitizeInput($_POST['student_id']);
    $password = $_POST['password']; // No hashing as requested
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND password = ?");
        $stmt->execute([$student_id, $password]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ((int)$user['is_approved'] === 1) {
                $_SESSION['user_id'] = $user['student_id'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['first_name'] = $user['first_name'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Your account is pending admin approval.";
            }
        } else {
            $error = "Invalid student ID or password.";
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
    <title>Student Portal Login</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --danger-color: #e63946;
            --success-color: #2a9d8f;
            --border-radius: 8px;
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7ff;
            color: var(--dark-color);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 480px;
        }

        .login-form {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .login-form:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .login-form h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.2);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert.error {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .login-form p {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }

        .login-form a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-form a:hover {
            text-decoration: underline;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            height: 90px;
        }

        @media (max-width: 576px) {
            .login-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <div class="logo">
                <!-- Replace with your actual logo -->
                <img src="pic/srclogo.png" alt="University Logo">
            </div>
            <h2>Student Login</h2>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="index.php" method="post">
                <div class="form-group">
                    <label for="student_id">Student ID Number</label>
                    <input type="text" id="student_id" name="student_id" required placeholder="Enter your student ID">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn">Sign In</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>