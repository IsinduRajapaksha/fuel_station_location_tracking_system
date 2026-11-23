<?php
// auth_config.php
session_start();
require_once("db_connect.php");

class Auth {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role, full_name, status FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['logged_in'] = true;
                    
                    // Update last login
                    $this->updateLastLogin($user['id']);
                    
                    // Log activity
                    $this->logActivity($user['id'], 'login', 'User logged in successfully');
                    
                    return ['success' => true, 'user' => $user];
                } else {
                    return ['success' => false, 'message' => 'Invalid password'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found or inactive'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function register($username, $email, $password, $full_name, $phone = '') {
        try {
            // Check if user exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $full_name, $phone);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['role'] === 'admin';
    }
    
    public function isManager() {
        return $this->isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager');
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: index.php?error=access_denied');
            exit();
        }
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    public function logActivity($userId, $action, $description) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        $stmt = $this->conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $action, $description, $ip, $userAgent);
        $stmt->execute();
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role'],
                'full_name' => $_SESSION['full_name']
            ];
        }
        return null;
    }
}

$auth = new Auth($conn);
?>