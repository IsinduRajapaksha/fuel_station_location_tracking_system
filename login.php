<?php
require_once("auth_config.php");

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: ' . ($auth->isAdmin() ? 'admin_dashboard.php' : 'index.php'));
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields';
        } else {
            $result = $auth->login($username, $password);
            if ($result['success']) {
                header('Location: ' . ($auth->isAdmin() ? 'admin_dashboard.php' : 'index.php'));
                exit();
            } else {
                $error = $result['message'];
            }
        }
    } elseif (isset($_POST['register'])) {
        $username = trim($_POST['reg_username']);
        $email = trim($_POST['reg_email']);
        $password = $_POST['reg_password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            $error = 'Please fill in all required fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } else {
            $result = $auth->register($username, $email, $password, $full_name, $phone);
            if ($result['success']) {
                $success = $result['message'] . ' You can now login.';
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fuel Finder LK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #1f2937;
            --dark-bg: #111827;
            --card-bg: #1f2937;
            --text-light: #ffffff;
            --text-muted: #d1d5db;
            --success: #10b981;
            --error: #ef4444;
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--dark-bg);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 3rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            border: 1px solid rgba(220, 38, 38, 0.2);
            position: relative;
            overflow: hidden;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: white;
        }

        .logo-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 2rem;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 4px;
        }

        .auth-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: var(--text-muted);
        }

        .auth-tab.active {
            background: var(--gradient);
            color: white;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.2);
        }

        .form-input::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .input-group .form-input {
            padding-left: 45px;
        }

        .auth-btn {
            width: 100%;
            padding: 15px;
            background: var(--gradient);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4);
        }

        .auth-btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            color: var(--text-light);
        }

        .form-container {
            display: none;
        }

        .form-container.active {
            display: block;
        }

        .password-requirements {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .auth-container {
                margin: 1rem;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-gas-pump"></i>
            </div>
            <h1 class="logo-title">Fuel Finder LK</h1>
            <p class="logo-subtitle">Access your fuel station management portal</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="auth-tabs">
            <div class="auth-tab active" onclick="switchTab('login')">Login</div>
            <div class="auth-tab" onclick="switchTab('register')">Register</div>
        </div>

        <!-- Login Form -->
        <div id="login-form" class="form-container active">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username or Email</label>
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" class="form-input" placeholder="Enter your username or email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" name="login" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="register-form" class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <div class="input-group">
                        <i class="fas fa-at input-icon"></i>
                        <input type="text" name="reg_username" class="form-input" placeholder="Choose a username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="reg_email" class="form-input" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" name="phone" class="form-input" placeholder="Enter your phone number">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="reg_password" class="form-input" placeholder="Create a password" required>
                    </div>
                    <div class="password-requirements">
                        Password must be at least 6 characters long
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                    </div>
                </div>

                <button type="submit" name="register" class="auth-btn">
                    <i class="fas fa-user-plus"></i>
                    Register
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Fuel Finder
            </a>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Update tabs
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.querySelector('.auth-tab').classList.add('active');
            
            // Update forms
            document.querySelectorAll('.form-container').forEach(f => f.classList.remove('active'));
            
            if (tab === 'login') {
                document.querySelectorAll('.auth-tab')[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                document.querySelectorAll('.auth-tab')[1].classList.add('active');
                document.getElementById('register-form').classList.add('active');
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const passwords = document.querySelectorAll('input[type="password"]');
            if (passwords.length === 2) {
                if (passwords[0].value !== passwords[1].value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            }
        });
    </script>
</body>
</html>