<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include config
require_once 'includes/config_final.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['db_name']) && isset($databases[$_SESSION['db_name']])) {
    header("Location: " . $databases[$_SESSION['db_name']]);
    exit;
}

$error = "";

// Login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_form'])) {
    $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
    $password   = isset($_POST['password']) ? $_POST['password'] : '';

    try {
        $stmt = $pdo->prepare("SELECT student_id, first_name, last_name, password, is_approved, department FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $userData = $stmt->fetch();

        if ($userData && password_verify($password, $userData['password'])) {
            if ((int)$userData['is_approved'] === 0) {
                $error = "Your account is pending. Please wait for Admin approval.";
            } else {
                $_SESSION['user_id'] = $userData['student_id'];
                $_SESSION['full_name'] = trim($userData['first_name'] . ' ' . $userData['last_name']);

                // Map department to path
                $deptKey = $userData['department'] ?? null;
                $mappedDb = $deptKey && isset($department_db_map[$deptKey]) ? $department_db_map[$deptKey] : array_key_first($databases);

                $_SESSION['db_name'] = $mappedDb;
                header("Location: " . $databases[$mappedDb]);
                exit;
            }
        } else {
            $error = "Invalid Student ID or Password.";
        }
    } catch (PDOException $e) {
        $error = "A system error occurred. Please try again later.";
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
        :root{--primary-color:#4361ee;--primary-dark:#3a56d4;--secondary-color:#3f37c9;--accent-color:#4895ef;--light-color:#f8f9fa;--dark-color:#212529;--danger-color:#e63946;--success-color:#2a9d8f;--border-radius:8px;--box-shadow:0 10px 20px rgba(0,0,0,0.1);--transition:all .3s ease}*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Poppins',sans-serif;background-color:#f5f7ff;color:var(--dark-color);line-height:1.6;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px}.container{width:100%;max-width:480px;position:relative}.login-form{background:white;padding:40px;border-radius:var(--border-radius);box-shadow:var(--box-shadow)}.login-form h2{color:var(--primary-color);text-align:center;margin-bottom:30px;font-weight:600;font-size:28px}.form-group{margin-bottom:25px}.form-group label{display:block;margin-bottom:8px;font-weight:500}.form-group input{width:100%;padding:12px 15px;border:1px solid #ddd;border-radius:var(--border-radius);font-size:16px}.btn{width:100%;padding:14px;background-color:var(--primary-color);color:white;border:none;border-radius:var(--border-radius);font-size:16px;font-weight:500;cursor:pointer;margin-top:10px}.btn:hover{background-color:var(--primary-dark)}.alert{padding:12px 15px;border-radius:var(--border-radius);margin-bottom:20px;font-size:14px;text-align:center;display:none;}.alert.error{background-color:rgba(230,57,70,0.1);color:var(--danger-color);border-left:4px solid var(--danger-color);text-align:left}.alert.success{background-color:rgba(42,157,143,.1);color:var(--success-color);border-left:4px solid var(--success-color);text-align:left}.login-form p{text-align:center;margin-top:25px;color:#666;font-size:14px}.login-form a{color:var(--primary-color);text-decoration:none;font-weight:500;cursor:pointer}.login-form a:hover{text-decoration:underline}.logo{text-align:center;margin-bottom:30px}.logo img{height:90px}.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);display:flex;justify-content:center;align-items:center;opacity:0;visibility:hidden;transition:opacity .3s ease,visibility 0s .3s;z-index:1000}.modal-overlay.active{opacity:1;visibility:visible;transition:opacity .3s ease}.modal-content{background:white;padding:40px;border-radius:var(--border-radius);width:100%;max-width:480px;margin:20px;transform:translateY(-50px);transition:transform .3s ease;position:relative}.modal-overlay.active .modal-content{transform:translateY(0)}.modal-content h2{color:var(--primary-color);text-align:center;margin-bottom:15px;font-weight:600;font-size:28px}.modal-content .instructions{text-align:center;color:#666;margin-bottom:30px}.close-btn{position:absolute;top:15px;right:15px;background:none;border:none;font-size:24px;color:#aaa;cursor:pointer;transition:color .3s ease}.close-btn:hover{color:var(--dark-color)}
    </style>
</head>
<body>
<div class="container">
    <div class="login-form">
        <div class="logo"><img src="/CCS VOTING/pic/srclogo.png" alt="University Logo"></div>
        <h2>Student Login</h2>
        <?php if (!empty($error)): ?>
            <div class="alert error" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <input type="hidden" name="login_form" value="1">
            <div class="form-group"><label for="student_id">Student ID</label><input type="text" id="student_id" name="student_id" required placeholder="Enter your Student ID" value="<?php echo isset($student_id) ? htmlspecialchars($student_id) : ''; ?>"></div>
            <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" required placeholder="Enter your password"></div>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <p>Forgot your password? <a id="reset-link">Reset it here</a></p>
    </div>
</div>

<div class="modal-overlay" id="reset-modal">
    <div class="modal-content">
        <button class="close-btn" id="close-modal">&times;</button>
        <div id="step-1-email"><h2>Reset Password</h2><p class="instructions">Enter the email address associated with your account.</p><div id="step-1-message" class="alert"></div><form id="email-form"><div class="form-group"><label for="email">Email Address</label><input type="email" id="email" required placeholder="your.email@example.com"></div><button type="submit" class="btn" id="send-otp-btn">Send OTP</button></form></div>
        <div id="step-2-otp" style="display:none;"><h2>Enter OTP</h2><p class="instructions">An OTP was sent to <strong id="email-display"></strong>.</p><div id="step-2-message" class="alert"></div><form id="otp-form"><div class="form-group"><label for="otp">OTP</label><input type="text" id="otp" required maxlength="6" placeholder="6-digit code"></div><button type="submit" class="btn">Verify OTP</button></form></div>
        <div id="step-3-password" style="display:none;"><h2>New Password</h2><p class="instructions">Your new password must be at least 8 characters long.</p><div id="step-3-message" class="alert"></div><form id="password-form"><div class="form-group"><label for="new_password">New Password</label><input type="password" id="new_password" required minlength="8"></div><div class="form-group"><label for="confirm_password">Confirm New Password</label><input type="password" id="confirm_password" required></div><button type="submit" class="btn">Reset Password</button></form></div>
    </div>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
<script>
    const EMAILJS_SERVICE_ID = 'service_xdmda8p';
    const EMAILJS_TEMPLATE_ID = 'template_2u0glca';
    const EMAILJS_PUBLIC_KEY = 'OIX5KA4zIfVfAWlsV';
    emailjs.init(EMAILJS_PUBLIC_KEY);

    document.addEventListener('DOMContentLoaded', function() {
        const resetModal = document.getElementById('reset-modal');
        const step1Div = document.getElementById('step-1-email'), step2Div = document.getElementById('step-2-otp'), step3Div = document.getElementById('step-3-password');
        let userEmail = '', userOtp = '';

        function showMessage(step, type, msg) {
            const el = document.getElementById(`step-${step}-message`);
            el.className = `alert ${type}`;
            el.textContent = msg;
            el.style.display = 'block';
        }
        function hideMessages() {
            for(let i=1; i<=3; i++) document.getElementById(`step-${i}-message`).style.display = 'none';
        }
        
        document.getElementById('reset-link')?.addEventListener('click', e => { e.preventDefault(); resetModal.classList.add('active'); });
        document.getElementById('close-modal').addEventListener('click', () => { resetModal.classList.remove('active'); hideMessages(); });
        resetModal.addEventListener('click', e => { if (e.target === resetModal) resetModal.classList.remove('active'); });

        // Step 1: Send OTP (Logic Updated as per your request)
        document.getElementById('email-form').addEventListener('submit', async e => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.disabled = true;
            btn.textContent = 'Processing...';
            hideMessages();

            userEmail = document.getElementById('email').value;
            const otpCode = Math.floor(100000 + Math.random() * 900000);

            try {
                // First, check with the backend if the user exists
                const res = await fetch('generate_otp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ email: userEmail, otp: otpCode })
                });

                if (!res.ok) { // This handles server errors like 500
                    throw new Error(`Server responded with status: ${res.status}`);
                }

                const result = await res.json();

                // **NEW LOGIC STARTS HERE**
                if (result.success) {
                    // User was found in the database, now we send the email.
                    btn.textContent = 'Sending Email...';
                    try {
                        const templateParams = { to_email: userEmail, otp_code: otpCode };
                        await emailjs.send(EMAILJS_SERVICE_ID, EMAILJS_TEMPLATE_ID, templateParams);
                        
                        // If email sending is successful, move to the next step.
                        step1Div.style.display = 'none';
                        step2Div.style.display = 'block';
                        document.getElementById('email-display').textContent = userEmail;

                    } catch (emailjsError) {
                        console.error("EMAILJS FAILED:", emailjsError);
                        showMessage(1, 'error', `Could not send email. Please check your connection or try again.`);
                    }
                } else {
                    // User was NOT found, show the error message from PHP and DO NOT send email.
                    showMessage(1, 'error', result.message || 'Account not found.');
                }
                // **NEW LOGIC ENDS HERE**

            } catch (error) {
                // This catches network errors (Failed to fetch) or server errors (500)
                console.error("Error during Step 1:", error);
                showMessage(1, 'error', 'An unexpected error occurred. Please try again.');
            } finally {
                // Always re-enable the button, regardless of success or failure
                btn.disabled = false;
                btn.textContent = 'Send OTP';
            }
        });

        // Step 2: Verify OTP
        document.getElementById('otp-form').addEventListener('submit', async e => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.disabled = true; btn.textContent = 'Verifying...';
            hideMessages();
            userOtp = document.getElementById('otp').value;

            try {
                const res = await fetch('verify_otp.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ email: userEmail, otp: userOtp }) });
                if (!res.ok) throw new Error('Server connection error.');
                const result = await res.json();
                if (!result.success) throw new Error(result.message || 'Invalid OTP.');
                step2Div.style.display = 'none';
                step3Div.style.display = 'block';
            } catch (err) {
                showMessage(2, 'error', err.message);
            } finally {
                btn.disabled = false; btn.textContent = 'Verify OTP';
            }
        });

        // Step 3: Reset Password
        document.getElementById('password-form').addEventListener('submit', async e => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            hideMessages();
            
            if (newPass !== confirmPass) { return showMessage(3, 'error', 'Passwords do not match.'); }
            if (newPass.length < 8) { return showMessage(3, 'error', 'Password must be at least 8 characters long.'); }
            
            btn.disabled = true; btn.textContent = 'Resetting...';
            try {
                const res = await fetch('reset_password_final.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ email: userEmail, otp: userOtp, new_password: newPass }) });
                if (!res.ok) throw new Error('Server connection error.');
                const result = await res.json();
                if (!result.success) throw new Error(result.message || 'Failed to reset password.');
                
                showMessage(3, 'success', 'Password reset successfully! You can now log in with your new password.');
                setTimeout(() => {
                    resetModal.classList.remove('active');
                    step3Div.style.display = 'none'; step1Div.style.display = 'block';
                    e.target.reset(); document.getElementById('email-form').reset(); document.getElementById('otp-form').reset();
                }, 4000);
            } catch (err) {
                showMessage(3, 'error', err.message);
            } finally {
                if(document.getElementById('step-3-password').style.display !== 'none') {
                    btn.disabled = false;
                    btn.textContent = 'Reset Password';
                }
            }
        });
    });
</script>
</body>
</html>