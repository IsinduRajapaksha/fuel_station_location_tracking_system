<?php
require_once("db_connect.php");
require_once("auth_config.php");

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$currentUser = $auth->getCurrentUser();
$message = '';
$messageType = '';

// Handle form submission
if ($_POST) {
    $errors = [];
    
    // Validate input
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Check if email is already taken by another user
    $emailCheckQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
    $emailCheckStmt = $conn->prepare($emailCheckQuery);
    $emailCheckStmt->bind_param("si", $email, $currentUser['id']);
    $emailCheckStmt->execute();
    $emailCheckResult = $emailCheckStmt->get_result();
    
    if ($emailCheckResult->num_rows > 0) {
        $errors[] = "Email is already in use by another account";
    }
    
    // Password validation (only if user wants to change password)
    if (!empty($new_password) || !empty($confirm_password) || !empty($current_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $currentUser['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    // If no errors, update the profile
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Update basic info
            if (!empty($new_password)) {
                // Update with new password
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ssssi", $full_name, $email, $phone, $hashedPassword, $currentUser['id']);
            } else {
                // Update without changing password
                $updateQuery = "UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("sssi", $full_name, $email, $phone, $currentUser['id']);
            }
            
            $updateStmt->execute();
            
            // Log the activity
            $logQuery = "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, 'profile_update', 'User updated profile information', ?)";
            $logStmt = $conn->prepare($logQuery);
            $userIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $logStmt->bind_param("is", $currentUser['id'], $userIP);
            $logStmt->execute();
            
            $conn->commit();
            
            // Refresh current user data
            $currentUser = $auth->getCurrentUser();
            
            $message = "Profile updated successfully!";
            $messageType = "success";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error updating profile: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Fuel Finder LK</title>
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
            --warning: #f59e0b;
            --error: #ef4444;
            --shadow: 0 10px 40px rgba(0,0,0,0.5);
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
        }

        .top-nav {
            background: rgba(31, 41, 55, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(220, 38, 38, 0.2);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-light);
            text-decoration: none;
        }

        .nav-brand i {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .nav-link:hover {
            color: var(--text-light);
            background: rgba(220, 38, 38, 0.1);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .profile-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(220, 38, 38, 0.2);
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .avatar-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .avatar {
            width: 100px;
            height: 100px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
        }

        .avatar-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .avatar-info p {
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .role-badge {
            background: var(--gradient);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.2);
            background: rgba(255,255,255,0.08);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .password-section {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(220, 38, 38, 0.1);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .password-section h3 {
            margin-bottom: 1rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.4);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: var(--text-light);
            border: 2px solid rgba(220, 38, 38, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.15);
            border-color: var(--primary-color);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(220, 38, 38, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .profile-card {
                padding: 1.5rem;
            }

            .avatar-section {
                flex-direction: column;
                text-align: center;
            }

            .btn-group {
                flex-direction: column;
            }

            .nav-container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">
                <i class="fas fa-gas-pump"></i>
                Fuel Finder LK
            </a>
            
            <div class="nav-menu">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <?php if ($auth->isAdmin()): ?>
                    <a href="admin_dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        Admin
                    </a>
                <?php endif; ?>
                <a href="station_management.php" class="nav-link">
                    <i class="fas fa-gas-pump"></i>
                    Stations
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Profile Settings</h1>
            <p class="page-subtitle">Manage your account information and preferences</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="avatar-section">
                <div class="avatar">
                    <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                </div>
                <div class="avatar-info">
                    <h2><?= htmlspecialchars($currentUser['full_name']) ?></h2>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($currentUser['email']) ?></p>
                    <p><i class="fas fa-user"></i> <?= htmlspecialchars($currentUser['username']) ?></p>
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Full Name
                        </label>
                        <input type="text" name="full_name" class="form-input" 
                               value="<?= htmlspecialchars($currentUser['full_name']) ?>" 
                               placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" name="email" class="form-input" 
                               value="<?= htmlspecialchars($currentUser['email']) ?>" 
                               placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i>
                            Phone Number
                        </label>
                        <input type="tel" name="phone" class="form-input" 
                               value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" 
                               placeholder="Enter your phone number">
                    </div>
                </div>


                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Profile
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide success messages after 5 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => successAlert.remove(), 300);
                }, 5000);
            }

            // Password strength indicator (basic)
            const newPasswordField = document.querySelector('input[name="new_password"]');
            if (newPasswordField) {
                newPasswordField.addEventListener('input', function() {
                    const password = this.value;
                    const strength = calculatePasswordStrength(password);
                    
                    // Remove existing strength indicator
                    const existingIndicator = this.parentNode.querySelector('.password-strength');
                    if (existingIndicator) {
                        existingIndicator.remove();
                    }

                    if (password.length > 0) {
                        const indicator = document.createElement('div');
                        indicator.className = 'password-strength';
                        indicator.style.cssText = `
                            margin-top: 0.5rem;
                            padding: 0.5rem;
                            border-radius: 5px;
                            font-size: 0.8rem;
                            background: ${strength.color}20;
                            border: 1px solid ${strength.color}40;
                            color: ${strength.color};
                        `;
                        indicator.textContent = `Password Strength: ${strength.text}`;
                        this.parentNode.appendChild(indicator);
                    }
                });
            }
        });

        function calculatePasswordStrength(password) {
            let score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 10) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            if (score < 2) return { text: 'Weak', color: '#ef4444' };
            if (score < 4) return { text: 'Fair', color: '#f59e0b' };
            if (score < 6) return { text: 'Good', color: '#10b981' };
            return { text: 'Strong', color: '#10b981' };
        }
    </script>
</body>
</html>