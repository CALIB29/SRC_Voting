<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Department folder mapping for admins
$department_admin_map = [
    1 => '../CCS VOTING/admin/dashboard.php',
    2 => '../CBS VOTING/admin/dashboard.php',
    3 => '../COE VOTING/admin/dashboard.php',
    9 => '../ELEMENTARY/admin/dashboard.php',
    4 => '../INTEGRATED/admin/dashboard.php'
];

// If already logged in → try to redirect them to their department dashboard
if (isset($_SESSION['admin_id']) && isset($_SESSION['department_id'])) {
    $dashboard_path = $department_admin_map[$_SESSION['department_id']] ?? null;
    if ($dashboard_path) {
        header("Location: {$dashboard_path}");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = sanitizeInput($_POST['username']); // email input
    $password = $_POST['password'];             // password input

    try {
        // Fetch employee with department info
        $stmt = $pdo->prepare("
            SELECT e.*, d.department_id, d.department_name
            FROM employees e
            LEFT JOIN department d ON e.department_id = d.department_id
            WHERE e.email = ?
        ");
        $stmt->execute([$email]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {

            // Plain comparison (replace with password_verify if hashed)
            if ($password === $employee['password']) {

                // Store session values
                $_SESSION['admin_id'] = $employee['employee_id'];
                $_SESSION['admin_name'] = $employee['firstname'] . " " . $employee['lastname'];
                $_SESSION['admin_role'] = $employee['role'];

                // Store department_id for filtering dashboard stats
                $_SESSION['department_id'] = $employee['department_id'];
                $_SESSION['department_name'] = $employee['department_name'];

                // Redirect to the correct department dashboard
                $dashboard_path = $department_admin_map[$employee['department_id']] ?? null;
                if ($dashboard_path) {
                    header("Location: {$dashboard_path}");
                    exit;
                } else {
                    $error = "Your department dashboard is not configured.";
                }

            } else {
                $error = "Invalid email or password.";
            }

        } else {
            $error = "Invalid email or password.";
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

        /* Animated background orbs */
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
            animation-delay: 0s;
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

        /* Decorative top gradient */
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
            position: relative;
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
            background: linear-gradient(135deg, var(--text-main), var(--text-muted));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .login-header .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            margin-top: 1rem;
            color: var(--primary);
            font-weight: 600;
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
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert i {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group label i {
            color: var(--primary);
            font-size: 0.85rem;
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
            font-size: 1rem;
            pointer-events: none;
            transition: var(--transition);
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
            font-weight: 500;
        }

        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(15, 23, 42, 0.7);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .form-control:focus+.input-icon {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--primary);
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
            position: relative;
            overflow: hidden;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px var(--primary-glow);
        }

        .btn-login:active {
            transform: translateY(0);
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
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-footer a:hover {
            color: var(--primary);
        }

        .login-footer a i {
            transition: var(--transition);
        }

        .login-footer a:hover i {
            transform: translateX(-3px);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 1rem;
            }

            .login-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .login-body {
                padding: 0 1.5rem 2rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .bg-orb {
                display: none;
            }
        }
    </style>
</head>

<body>
    <!-- Animated background orbs -->
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <div class="logo-glow"></div>
                    <img src="../logo/srclogo.png" alt="Santa Rita College Logo">
                </div>
                <h1>Admin Portal</h1>
                <p>Santa Rita College Voting System</p>
                <div class="badge">
                    <i class="fas fa-shield-halved"></i>
                    <span>Secure Access</span>
                </div>
            </div>

            <div class="login-body">
                <?php if (isset($error)): ?>
                    <div class="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user-shield"></i>
                            Email Address
                        </label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="form-control"
                                placeholder="admin@santarita.edu" required autocomplete="username">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Enter your secure password" required autocomplete="current-password">
                            <i class="fas fa-key input-icon"></i>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Sign In to Dashboard</span>
                    </button>
                </form>

                <div class="login-footer">
                    <a href="../index.php">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Voting Portal</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Add focus animation to input icons
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function () {
                this.parentElement.querySelector('.input-icon')?.classList.add('focused');
            });
            input.addEventListener('blur', function () {
                this.parentElement.querySelector('.input-icon')?.classList.remove('focused');
            });
        });
    </script>
</body>

</html>