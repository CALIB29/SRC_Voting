<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Department folder mapping
$department_map = [
    1 => 'CCS VOTING',
    2 => 'CBS VOTING',
    3 => 'COE VOTING',
    9 => 'ELEMENTARY',
    4 => 'INTEGRATED' // Assuming Senior High is in the INTEGRATED folder
];

// If user is already logged in, try to redirect them
if (isset($_SESSION['user_id']) && isset($_SESSION['department_id'])) {
    $folder = $department_map[$_SESSION['department_id']] ?? null;
    if ($folder) {
        header("Location: {$folder}/dashboard.php");
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = sanitizeInput($_POST['student_id']);
    $password = $_POST['password'];

    if (empty($student_id) || empty($password)) {
        $error = 'Please enter both student ID and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT student_id, first_name, password, is_approved, department_id FROM students WHERE student_id = ?");
            $stmt->execute([trim($student_id)]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) {
                if ((int) $user['is_approved'] === 1) {
                    $_SESSION['user_id'] = $user['student_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['department_id'] = $user['department_id'];

                    $department_folder = $department_map[$user['department_id']] ?? null;

                    if ($department_folder) {
                        $dashboard_url = "{$department_folder}/dashboard.php";
                        // Use a robust JS redirect
                        echo '<script type="text/javascript">window.location.href = "' . $dashboard_url . '";</script>';
                        exit;
                    } else {
                        $error = 'Your department is not configured correctly. Please contact an administrator.';
                    }
                } else {
                    $error = 'Your account is pending admin approval.';
                }
            } else {
                $error = 'Invalid student ID or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login</title>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modern Light Blue Theme */
        :root {
            --primary: #3B82F6;
            --primary-glow: rgba(59, 130, 246, 0.4);
            --secondary: #06B6D4;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --gold: #D4AF37;
            --bg-body: #0F172A;
            --bg-card: rgba(30, 41, 59, 0.7);
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --border: rgba(255, 255, 255, 0.1);
            --radius-lg: 24px;
            --radius-md: 16px;
            --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background-image:
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.1) 0px, transparent 50%);
        }

        /* Animated Background Orbs */
        body::before,
        body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
            z-index: 0;
        }

        body::before {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--primary), transparent);
            top: -250px;
            left: -250px;
            animation-delay: 0s;
        }

        body::after {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--secondary), transparent);
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .container {
            width: 100%;
            max-width: 420px;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .login-form {
            background: var(--bg-card);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: var(--radius-lg);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .login-form::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--gold));
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        .logo {
            margin-bottom: 1rem;
        }

        .logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px var(--primary-glow));
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            margin-bottom: 0.25rem;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 50px;
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 600;
            margin: 0 auto 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-align: left;
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #FCA5A5;
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        label i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group i.field-icon {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            font-size: 1rem;
            transition: var(--transition);
        }

        .input-group input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.8rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: rgba(15, 23, 42, 0.5);
            color: var(--text-main);
            font-size: 0.95rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: var(--transition);
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: rgba(15, 23, 42, 0.7);
        }

        .input-group input:focus + i.field-icon {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--text-main);
        }

        .btn {
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
            box-shadow: 0 4px 15px var(--primary-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-transform: none;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--primary-glow);
            filter: brightness(1.1);
        }

        .btn:active { transform: translateY(0); }

        .footer-links {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        p {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container { padding: 1rem; }
            .login-form { padding: 2rem 1.5rem; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-form">
            <div class="logo">
                <img src="logo/srclogo.png" alt="System Logo">
            </div>
            <h2>Student Portal</h2>
            <p class="subtitle">Santa Rita College Voting System</p>
            
            <div class="secure-badge">
                <i class="fas fa-shield-alt"></i>
                Secure Access
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="student_id">
                        <i class="fas fa-user-circle"></i> 
                        Student ID
                    </label>
                    <div class="input-group">
                        <i class="fas fa-envelope field-icon"></i>
                        <input type="text" id="student_id" name="student_id" required autofocus placeholder="Enter your ID">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> 
                        Password
                    </label>
                    <div class="input-group">
                        <i class="fas fa-key field-icon"></i>
                        <input type="password" id="password" name="password" required placeholder="Enter password">
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In to Portal
                </button>
            </form>

            <div class="footer-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="update_account.php">Update Account by RFID</a></p>
                <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>