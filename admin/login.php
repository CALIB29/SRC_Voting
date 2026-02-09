<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Department folder mapping for admins (relative to BASE_URL)
$department_admin_map = [
    2 => 'CCSVOTING/admin/dashboard.php',
    4 => 'CBSVOTING/admin/dashboard.php',
    3 => 'COEVOTING/admin/dashboard.php',
    6 => 'ELEMENTARY/admin/dashboard.php',
    5 => 'INTEGRATED/admin/dashboard.php'
];

// If already logged in → redirect to dashboard
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
            exit;
        } else {
            $error = "Your department dashboard is not configured.";
        }
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
    <title>Admin Portal | Santa Rita College Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #3B82F6;
            --primary-glow: rgba(59, 130, 246, 0.4);
            --secondary: #06B6D4;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --bg-body: #0F172A;
            --bg-card: rgba(30, 41, 59, 0.7);
            --bg-sidebar: #1E293B;
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --border: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.05);
            --radius-lg: 24px;
            --radius-md: 16px;
            --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            background-image:
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.1) 0px, transparent 50%);
        }

        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
        }

        .bg-orb-1 {
            width: 400px;
            height: 400px;
            background: var(--primary);
            top: -200px;
            left: -200px;
        }

        .bg-orb-2 {
            width: 350px;
            height: 350px;
            background: var(--secondary);
            bottom: -150px;
            right: -150px;
            animation-delay: 5s;
        }

        .bg-orb-3 {
            width: 300px;
            height: 300px;
            background: var(--warning);
            top: 50%;
            right: 10%;
            animation-delay: 10s;
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

        .login-container {
            width: 100%;
            max-width: 480px;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            position: relative;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--warning));
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
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

        .login-header {
            text-align: center;
            padding: 3rem 2rem 2rem;
        }

        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 140px;
            height: 140px;
            background: var(--primary-glow);
            border-radius: 50%;
            filter: blur(30px);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 0.5;
                transform: translate(-50%, -50%) scale(1);
            }

            50% {
                opacity: 0.8;
                transform: translate(-50%, -50%) scale(1.1);
            }
        }

        .logo-container img {
            width: 100px;
            height: 100px;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 0 20px var(--primary-glow));
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .login-body {
            padding: 0 2rem 3rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #FCA5A5;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            color: var(--text-main);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(15, 23, 42, 0.7);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
        }

        .btn-login {
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
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px var(--primary-glow);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .login-footer a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-footer a:hover {
            color: var(--primary);
        }

        @media (max-width: 480px) {
            .bg-orb {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <div class="logo-glow"></div>
                    <img src="../logo/srclogo.png" alt="Logo">
                </div>
                <h1>Admin Portal</h1>
                <p>Santa Rita College Voting System</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="form-group">
                        <label for="username">Email Address</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="form-control"
                                placeholder="admin@santarita.edu" required>
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Enter password" required>
                            <i class="fas fa-key input-icon"></i>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Sign In</span>
                    </button>
                </form>

                <div class="login-footer">
                    <a href="../index.php"><i class="fas fa-arrow-left"></i> <span>Back Home</span></a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>