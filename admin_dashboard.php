<?php
require_once("auth_config.php");

// Require admin access
$auth->requireAdmin();

// Get dashboard statistics
$stats = [];

// Total stations
$result = $conn->query("SELECT COUNT(*) as total FROM fuel_stations");
$stats['total_stations'] = $result->fetch_assoc()['total'];

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Active users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stats['active_users'] = $result->fetch_assoc()['total'];

// Recent activity count
$result = $conn->query("SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$stats['today_activity'] = $result->fetch_assoc()['total'];

// Districts coverage
$result = $conn->query("SELECT COUNT(DISTINCT district) as total FROM fuel_stations");
$stats['districts_covered'] = $result->fetch_assoc()['total'];

// Recent activities
$recent_activities = [];
$result = $conn->query("
    SELECT al.*, u.username, u.full_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = $row;
}

// Recent users
$recent_users = [];
$result = $conn->query("
    SELECT id, username, email, full_name, role, status, created_at, last_login 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}

// Fuel station statistics by brand
$brand_stats = [];
$result = $conn->query("
    SELECT brand, COUNT(*) as count 
    FROM fuel_stations 
    GROUP BY brand 
    ORDER BY count DESC
");
while ($row = $result->fetch_assoc()) {
    $brand_stats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fuel Finder LK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #1f2937;
            --dark-bg: #111827;
            --card-bg: #1f2937;
            --sidebar-bg: #0f172a;
            --text-light: #ffffff;
            --text-muted: #d1d5db;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
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
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(220, 38, 38, 0.2);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-logo {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .sidebar-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-subtitle {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 2rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(220, 38, 38, 0.1);
            color: var(--text-light);
            border-right-color: var(--primary-color);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
        }

        .user-info {
            padding: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            font-weight: 600;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .user-role {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .logout-btn {
            width: 100%;
            padding: 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            color: var(--error);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-light);
        }

        .page-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: var(--text-light);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        /* Dashboard Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(220, 38, 38, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .card-icon.primary { background: var(--gradient); }
        .card-icon.success { background: linear-gradient(135deg, var(--success), #059669); }
        .card-icon.warning { background: linear-gradient(135deg, var(--warning), #d97706); }
        .card-icon.info { background: linear-gradient(135deg, var(--info), #2563eb); }

        .card-title {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Content Panels */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .panel {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(220, 38, 38, 0.2);
            position: relative;
            overflow: hidden;
        }

        .panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .panel-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .panel-action {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .panel-action:hover {
            color: var(--text-light);
        }

        /* Activity Feed */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: white;
            flex-shrink: 0;
        }

        .activity-icon.login { background: var(--success); }
        .activity-icon.logout { background: var(--warning); }
        .activity-icon.create { background: var(--info); }
        .activity-icon.update { background: var(--primary-color); }
        .activity-icon.delete { background: var(--error); }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-light);
        }

        .activity-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Users List */
        .user-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-item-avatar {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }

        .user-item-info {
            flex: 1;
        }

        .user-item-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-light);
        }

        .user-item-email {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .user-item-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .user-item-badge.admin {
            background: rgba(220, 38, 38, 0.2);
            color: var(--primary-color);
        }

        .user-item-badge.manager {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .user-item-badge.user {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        /* Charts Container */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(220, 38, 38, 0.2);
            position: relative;
            overflow: hidden;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .brand-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .brand-item:last-child {
            border-bottom: none;
        }

        .brand-name {
            font-weight: 600;
            color: var(--text-light);
        }

        .brand-count {
            background: var(--gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <button class="mobile-menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-gas-pump"></i>
                </div>
                <h2 class="sidebar-title">Admin Panel</h2>
                <p class="sidebar-subtitle">Fuel Finder LK</p>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="#" class="nav-link active" onclick="showSection('dashboard')">
                        <i class="fas fa-tachometer-alt nav-icon"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('stations')">
                        <i class="fas fa-gas-pump nav-icon"></i>
                        Fuel Stations
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('users')">
                        <i class="fas fa-users nav-icon"></i>
                        User Management
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('analytics')">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        Analytics
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('settings')">
                        <i class="fas fa-cog nav-icon"></i>
                        Settings
                    </a>
                </div>
                <div class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-external-link-alt nav-icon"></i>
                        View Site
                    </a>
                </div>
            </nav>

            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?>
                </div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="user-role"><?= ucfirst($_SESSION['role']) ?> User</div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Dashboard Overview</h1>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Dashboard Cards -->
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-icon primary">
                            <i class="fas fa-gas-pump"></i>
                        </div>
                        <div class="card-title">Total Stations</div>
                        <div class="card-value"><?= number_format($stats['total_stations']) ?></div>
                        <div class="card-subtitle">Fuel stations nationwide</div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-icon success">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-title">Total Users</div>
                        <div class="card-value"><?= number_format($stats['total_users']) ?></div>
                        <div class="card-subtitle"><?= $stats['active_users'] ?> active users</div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-icon info">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="card-title">Districts Covered</div>
                        <div class="card-value"><?= number_format($stats['districts_covered']) ?></div>
                        <div class="card-subtitle">Across Sri Lanka</div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-icon warning">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-title">Today's Activity</div>
                        <div class="card-value"><?= number_format($stats['today_activity']) ?></div>
                        <div class="card-subtitle">User activities today</div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Recent Activity -->
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">
                                <i class="fas fa-clock"></i>
                                Recent Activity
                            </h3>
                            <a href="#" class="panel-action">View All</a>
                        </div>
                        <div class="activity-feed">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?= $activity['action'] ?>">
                                        <i class="fas fa-<?= $activity['action'] === 'login' ? 'sign-in-alt' : ($activity['action'] === 'logout' ? 'sign-out-alt' : 'cog') ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <?= $activity['full_name'] ?: $activity['username'] ?: 'Unknown User' ?>
                                            <?= htmlspecialchars($activity['description']) ?>
                                        </div>
                                        <div class="activity-meta">
                                            <?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?>
                                            • IP: <?= htmlspecialchars($activity['ip_address']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">
                                <i class="fas fa-user-plus"></i>
                                Recent Users
                            </h3>
                            <a href="#" class="panel-action" onclick="showSection('users')">View All</a>
                        </div>
                        <div class="users-list">
                            <?php foreach ($recent_users as $user): ?>
                                <div class="user-item">
                                    <div class="user-item-avatar">
                                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                    </div>
                                    <div class="user-item-info">
                                        <div class="user-item-name"><?= htmlspecialchars($user['full_name']) ?></div>
                                        <div class="user-item-email"><?= htmlspecialchars($user['email']) ?></div>
                                    </div>
                                    <div class="user-item-badge <?= $user['role'] ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <div class="panel-header">
                            <h3 class="panel-title">
                                <i class="fas fa-building"></i>
                                Stations by Brand
                            </h3>
                        </div>
                        <div class="brand-stats">
                            <?php foreach ($brand_stats as $brand): ?>
                                <div class="brand-item">
                                    <div class="brand-name"><?= htmlspecialchars($brand['brand']) ?></div>
                                    <div class="brand-count"><?= number_format($brand['count']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other sections would be loaded via AJAX or included here -->
            <div id="stations-section" class="content-section" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">Fuel Stations Management</h1>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="addStation()">
                            <i class="fas fa-plus"></i>
                            Add Station
                        </button>
                    </div>
                </div>
                <p>Station management interface would go here...</p>
            </div>

            <div id="users-section" class="content-section" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">User Management</h1>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="addUser()">
                            <i class="fas fa-user-plus"></i>
                            Add User
                        </button>
                    </div>
                </div>
                <p>User management interface would go here...</p>
            </div>

            <div id="analytics-section" class="content-section" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">Analytics & Reports</h1>
                </div>
                <p>Analytics interface would go here...</p>
            </div>

            <div id="settings-section" class="content-section" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">System Settings</h1>
                </div>
                <p>Settings interface would go here...</p>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionName + '-section').style.display = 'block';
            
            // Add active class to clicked nav link
            event.target.closest('.nav-link').classList.add('active');
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function refreshDashboard() {
            location.reload();
        }

        function addStation() {
            alert('Add Station functionality would be implemented here');
        }

        function addUser() {
            alert('Add User functionality would be implemented here');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>