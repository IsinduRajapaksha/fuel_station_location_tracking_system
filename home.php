<?php
require_once("db_connect.php");
require_once("auth_config.php");

// Check if user is logged in for additional features
$isLoggedIn = $auth->isLoggedIn();
$currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;

// Total Stations
$result1 = $conn->query("SELECT COUNT(*) AS total_stations FROM fuel_stations");
$total_stations = $result1->fetch_assoc()['total_stations'];

// Districts Covered
$result2 = $conn->query("SELECT COUNT(DISTINCT district) AS active_districts FROM fuel_stations");
$active_districts = $result2->fetch_assoc()['active_districts'];

// Fuel Brands
$result3 = $conn->query("SELECT COUNT(DISTINCT brand) AS fuel_brands FROM fuel_stations");
$fuel_brands = $result3->fetch_assoc()['fuel_brands'];

// Company-wise Station Counts
$ceylonCount = $conn->query("SELECT COUNT(*) AS count FROM fuel_stations WHERE brand = 'Ceylon Petroleum Corporation'");
$ceylon_stations = $ceylonCount->fetch_assoc()['count'];

$iocCount = $conn->query("SELECT COUNT(*) AS count FROM fuel_stations WHERE brand = 'Lanka IOC'");
$ioc_stations = $iocCount->fetch_assoc()['count'];

$rpclCount = $conn->query("SELECT COUNT(*) AS count FROM fuel_stations WHERE brand = 'Rpml'");
$rpcl_stations = $rpclCount->fetch_assoc()['count'];

$sinopecCount = $conn->query("SELECT COUNT(*) AS count FROM fuel_stations WHERE brand = 'Sinopec'");
$sinopec_stations = $sinopecCount->fetch_assoc()['count'];

// Get unique districts for dropdown
$districtsQuery = "SELECT DISTINCT district FROM fuel_stations ORDER BY district";
$districtsResult = $conn->query($districtsQuery);
$districts = [];
while ($row = $districtsResult->fetch_assoc()) {
    $districts[] = $row['district'];
}

// Get unique brands for dropdown
$brandsQuery = "SELECT DISTINCT brand FROM fuel_stations ORDER BY brand";
$brandsResult = $conn->query($brandsQuery);
$brands = [];
while ($row = $brandsResult->fetch_assoc()) {
    $brands[] = $row['brand'];
}

$depoQuery = "SELECT DISTINCT depo_name FROM depo ORDER BY depo_name";
$depoResult = $conn->query($depoQuery);
$depos = [];
while ($row = $depoResult->fetch_assoc()) {
    $depos[] = $row['depo_name'];
}

?>

<?php
// Add this after your existing queries (around line 40)
$depoQuery = "SELECT depo_id, depo_name FROM depo ORDER BY depo_name";
$depoResult = $conn->query($depoQuery);
$deposForCounter = [];
while ($row = $depoResult->fetch_assoc()) {
    $deposForCounter[] = $row;
}

// Add the AJAX handler at the top of your file (after the existing PHP logic)
if (isset($_GET['action']) && $_GET['action'] === 'get_station_count') {
    header('Content-Type: application/json');
    
    if (!isset($_GET['depo_id']) || empty($_GET['depo_id'])) {
        echo json_encode(['error' => 'Depo ID is required']);
        exit;
    }
    
    $depo_id = intval($_GET['depo_id']);
    
    $depoQuery = "SELECT depo_name FROM depo WHERE depo_id = ?";
    $stmt = $conn->prepare($depoQuery);
    $stmt->bind_param("i", $depo_id);
    $stmt->execute();
    $depoResult = $stmt->get_result();
    
    if ($depoRow = $depoResult->fetch_assoc()) {
        $countQuery = "SELECT COUNT(dbs.acct_no) as station_count 
                       FROM depo_by_station dbs 
                       INNER JOIN fuel_stations fs ON dbs.acct_no = fs.acct_no 
                       WHERE dbs.depo_id = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("i", $depo_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'depo_name' => $depoRow['depo_name'],
            'station_count' => $countRow['station_count']
        ]);
    } else {
        echo json_encode(['error' => 'Depo not found']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sri Lanka Fuel Stations - Advanced Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
            color: #ccd6f6;
            overflow-x: hidden;
        }

        /* Top Navigation Bar */
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

        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--card-bg);
            border: 1px solid rgba(220, 38, 38, 0.2);
            border-radius: 10px;
            padding: 1rem;
            min-width: 200px;
            box-shadow: var(--shadow);
            display: none;
            z-index: 1001;
        }

        .user-dropdown.active {
            display: block;
        }

        .user-info {
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-light);
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .dropdown-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .dropdown-link:hover {
            background: rgba(220, 38, 38, 0.1);
            color: var(--text-light);
        }

        .dropdown-link.logout {
            color: var(--error);
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 0.5rem;
            padding-top: 1rem;
        }

        .login-btn {
            background: var(--gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
        }

        .hero-section {
            background: linear-gradient(135deg, #dc2626 0%, #1f2937 100%);
            padding: 2rem 0;
            position: relative;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
            padding: 0 2rem;
        }

        .logo {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border: 3px solid #dc2626;
            font-size: 2rem;
            color: #dc2626;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: white;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .stats-container {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            min-width: 150px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            color: var(--success);
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Company Stats Styles */
        .company-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .company-stat-card {
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .company-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .company-stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
        }

        .company-stat-card.ceylon {
            border-color: rgba(181, 18, 0, 0.5);
        }

        .company-stat-card.ceylon::before {
            background: linear-gradient(135deg, #b51200ff, #8b0d00);
        }

        .company-stat-card.ioc {
            border-color: rgba(215, 198, 7, 0.5);
        }

        .company-stat-card.ioc::before {
            background: linear-gradient(135deg, #d7c607ff, #a89805);
        }

        .company-stat-card.rpcl {
            border-color: rgba(16, 6, 198, 0.5);
        }

        .company-stat-card.rpcl::before {
            background: linear-gradient(135deg, #1006c6ff, #0c0498);
        }

        .company-stat-card.sinopec {
            border-color: rgba(8, 136, 61, 0.5);
        }

        .company-stat-card.sinopec::before {
            background: linear-gradient(135deg, #08883dff, #06662e);
        }

        .company-stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .company-stat-count {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--success);
            display: block;
            margin-bottom: 0.5rem;
        }

        .company-stat-label {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .total-stat-card {
            background: rgba(220, 38, 38, 0.1);
            border: 2px solid var(--primary-color);
            grid-column: span 1;
        }

        .total-stat-card .company-stat-count {
            color: var(--primary-color);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .panel {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
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
            margin-bottom: 2rem;
        }

        .panel-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-container {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.3);
            border-radius: 50px;
            color: #ffffff;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.3);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--gradient);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .filter-group {
            position: relative;
        }

        .filter-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .filter-select, .station-select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 10px;
            color: #706e6eff;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
        }

        .station-search-input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 10px;
            color: #ffffff;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .station-search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(220, 38, 38, 0.3);
            background: rgba(255,255,255,0.08);
        }

        .station-search-input::placeholder {
            color: #9ca3af;
        }

        .autocomplete-container {
            position: relative;
            width: 100%;
        }

        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--card-bg);
            border: 2px solid rgba(220, 38, 38, 0.3);
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            display: none;
        }

        .autocomplete-suggestions.active {
            display: block;
        }

        .autocomplete-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
            color: var(--text-muted);
        }

        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background: rgba(220, 38, 38, 0.2);
            color: var(--text-light);
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-highlight {
            background: rgba(220, 38, 38, 0.3);
            font-weight: 600;
            color: #dc2626;
        }

        .station-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .station-name {
            font-weight: 600;
            color: #ffffff;
        }

        .station-details {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .no-results {
            padding: 15px;
            text-align: center;
            color: var(--text-muted);
            font-style: italic;
        }

        .filter-select:focus, .station-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(220, 38, 38, 0.3);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
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
            color: #ffffff;
            border: 2px solid rgba(220, 38, 38, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
        }

        .map-container {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        #map {
            height: 70vh;
            width: 100%;
            border-radius: 20px;
        }

        .map-overlay {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .map-control {
            background: rgba(22, 33, 62, 0.9);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 50px;
            padding: 12px 20px;
            color: #ccd6f6;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .map-control:hover, .map-control.active {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .result-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(220, 38, 38, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .result-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffffff;
        }

        .distance-badge {
            background: var(--gradient);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .result-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(220, 38, 38, 0.3);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            padding: 1rem;
            color: var(--error);
            text-align: center;
            margin: 1rem 0;
        }

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 10px;
            padding: 1rem;
            color: var(--success);
            text-align: center;
            margin: 1rem 0;
        }

        /* Station Details Panel */
        .station-details-panel {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(220, 38, 38, 0.2);
            position: relative;
            overflow: hidden;
            display: none;
        }

        .station-details-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .station-details-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .station-info-header {
            flex: 1;
        }

        .station-details-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .station-details-brand {
            background: var(--gradient);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .station-basic-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(220, 38, 38, 0.1);
            color: var(--primary-color);
        }

        .year-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .year-tab {
            padding: 12px 24px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 25px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .year-tab.active {
            background: var(--gradient);
            color: white;
            border-color: var(--primary-color);
        }

        .year-tab:hover:not(.active) {
            border-color: var(--primary-color);
            color: white;
        }

        .fuel-data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .fuel-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .fuel-card.available {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.1);
        }

        .fuel-card.unavailable {
            border-color: #6b7280;
            background: rgba(107, 114, 128, 0.1);
            opacity: 0.7;
        }

        .fuel-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .fuel-type-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .availability-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .availability-badge.available {
            background: var(--success);
            color: white;
        }

        .availability-badge.unavailable {
            background: #6b7280;
            color: white;
        }

        .fuel-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .fuel-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .fuel-detail-item:last-child {
            border-bottom: none;
        }

        .fuel-detail-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .fuel-detail-value {
            color: #ffffff;
            font-weight: 600;
        }

        .fuel-price {
            color: var(--warning);
        }

        .fuel-stock {
            color: var(--success);
        }

        /* Custom popup styles */
        .fuel-availability {
            margin: 15px 0;
        }

        .fuel-year {
            margin-bottom: 10px;
            background: rgba(220, 38, 38, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            border-left: 3px solid #dc2626;
        }

        .fuel-year-title {
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .fuel-types {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .fuel-badge {
            background: #10b981;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .fuel-badge.unavailable {
            background: #6b7280;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .container { padding: 1rem; }
            .filter-grid { grid-template-columns: 1fr; }
            .btn-group { flex-direction: column; }
            .fuel-data-grid { grid-template-columns: 1fr; }
            .year-tabs { justify-content: center; }
            .nav-menu { display: none; }
            .nav-container { padding: 0 1rem; }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--dark-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient);
            border-radius: 4px;
        }
    </style>
</head>
<body>
  

    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-gas-pump"></i>
                Fuel Finder LK
            </h1>
            <p class="hero-subtitle">
                Discover fuel stations across Sri Lanka with real-time availability and smart filtering
            </p>
            
        </div>
    </div>

    <div class="container">
        <!-- Station Statistics Panel -->
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">
                    <i class="fas fa-chart-bar"></i>
                    Station Statistics
                </h3>
            </div>

            <div class="company-stats-grid">
                <!-- Total Stations -->
                <div class="company-stat-card total-stat-card">
                    <div class="company-stat-icon">
                        <i class="fas fa-gas-pump"></i>
                    </div>
                    <span class="company-stat-count"><?= $total_stations ?></span>
                    <div class="company-stat-label">Total Stations</div>
                </div>

                <!-- Ceylon Petroleum Corporation -->
                <div class="company-stat-card ceylon">
                    <div class="company-stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <span class="company-stat-count"><?= $ceylon_stations ?></span>
                    <div class="company-stat-label">Ceylon Petroleum</div>
                </div>

                <!-- Lanka IOC -->
                <div class="company-stat-card ioc">
                    <div class="company-stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <span class="company-stat-count"><?= $ioc_stations ?></span>
                    <div class="company-stat-label">Lanka IOC</div>
                </div>

                <!-- RPCL -->
                <div class="company-stat-card rpcl">
                    <div class="company-stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <span class="company-stat-count"><?= $rpcl_stations ?></span>
                    <div class="company-stat-label">RPCL</div>
                </div>

                <!-- Sinopec -->
                <div class="company-stat-card sinopec">
                    <div class="company-stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <span class="company-stat-count"><?= $sinopec_stations ?></span>
                    <div class="company-stat-label">Sinopec</div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Panel -->
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">
                    <i class="fas fa-filter"></i>
                    Smart Filters
                </h3>
                <button class="btn btn-secondary" onclick="clearAllFilters()">
                    <i class="fas fa-times"></i>
                    Clear All
                </button>
            </div>

            <div class="search-container">
                <input type="text" id="search-input" class="search-input" placeholder="Search by account number or station name">
                <button class="search-btn" onclick="searchStations()">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div class="filter-grid">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-map-marker-alt"></i>
                        District
                    </label>
                    <select id="district" class="filter-select">
                        <option value="">All Districts</option>
                        <?php foreach($districts as $district): ?>
                            <option value="<?= htmlspecialchars($district) ?>"><?= htmlspecialchars($district) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-tint"></i>
                        Fuel Type
                    </label>
                    <select id="fuel-type" class="filter-select">
                        <option value="">All Fuel Types</option>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Super Diesel">Super Diesel</option>
                        <option value="Octane 95">Octane 95</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-warehouse"></i>
                        Depo
                    </label>
                    <select id="depo" class="filter-select">
                        <option value=""> All Depos</option>
                        <?php foreach($depos as $depo): ?>
                            <option value="<?= htmlspecialchars($depo) ?>"><?= htmlspecialchars($depo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-building"></i>
                        Company 
                    </label>
                    <select id="brand" class="filter-select">
                        <option value=""> All Company</option>
                        <?php foreach($brands as $brand): ?>
                            <option value="<?= htmlspecialchars($brand) ?>"><?= htmlspecialchars($brand) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-search"></i>
                    Apply Filters
                </button>
            </div>
        </div>

        <!-- Depo Station Counter Panel -->
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">
                    <i class="fas fa-warehouse"></i>
                    Depo Station Counter
                </h3>
            </div>

            <div class="filter-grid">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-warehouse"></i>
                        Select Depo
                    </label>
                    <select id="depoCounter" class="filter-select">
                        <option value="">Choose a depo...</option>
                        <?php foreach($deposForCounter as $depo): ?>
                            <option value="<?= $depo['depo_id'] ?>"><?= htmlspecialchars($depo['depo_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="counterResult" style="display: none; margin-top: 1rem;">
                <div class="result-card">
                    <div class="result-header">
                        <div class="result-title">
                            <i class="fas fa-warehouse"></i>
                            <span id="selectedDepoName"></span> Depo
                        </div>
                        <div class="distance-badge" id="stationCountDisplay">
                            <i class="fas fa-gas-pump"></i>
                            <span id="stationCountNumber">0</span> Stations
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add this panel after the Depo Station Counter Panel and before the Distance & Route Calculator Panel -->

        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">
                    <i class="fas fa-search-location"></i>
                    Find Nearest Stations
                </h3>
            </div>

            <div class="filter-grid">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-map-pin"></i>
                        Reference Point
                    </label>
                    <select id="referenceType" class="filter-select" onchange="toggleReferenceInput()">
                        <option value="station">Select a Station</option>
                        <option value="location">Use My Location</option>
                    </select>
                </div>

                <div class="filter-group" id="stationSelectGroup">
                    <label class="filter-label">
                        <i class="fas fa-gas-pump"></i>
                        Select Reference Station
                    </label>
                    <div class="autocomplete-container">
                        <input 
                            type="text" 
                            id="referenceStation" 
                            class="station-search-input" 
                            placeholder="Type to search reference station..."
                            autocomplete="off"
                        />
                        <div id="referenceStationSuggestions" class="autocomplete-suggestions"></div>
                    </div>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-list-ol"></i>
                        Number of Nearest Stations
                    </label>
                    <select id="nearestCount" class="filter-select">
                        <option value="3">Top 3 Nearest</option>
                        <option value="5" selected>Top 5 Nearest</option>
                        <option value="10">Top 10 Nearest</option>
                        <option value="15">Top 15 Nearest</option>
                    </select>
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" onclick="findNearestToReference()">
                    <i class="fas fa-compass"></i>
                    Find Nearest Stations
                </button>
                <button class="btn btn-secondary" onclick="clearNearestSearch()">
                    <i class="fas fa-times"></i>
                    Clear Search
                </button>
            </div>

            <div id="nearestStationsResult" style="display: none; margin-top: 1.5rem;">
                <div class="panel-header" style="margin-bottom: 1rem;">
                    <h4 class="panel-title" style="font-size: 1.2rem;">
                        <i class="fas fa-map-marked-alt"></i>
                        Nearest Stations Results
                    </h4>
                </div>
                <div id="nearestStationsContent"></div>
            </div>
        </div>
        <!-- Distance & Route Calculator Panel -->
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">
                    <i class="fas fa-route"></i>
                    Distance & Route Calculator
                </h3>
            </div>

            <div class="filter-grid">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-map-marker-alt"></i>
                        From Station
                    </label>
                    <div class="autocomplete-container">
                        <input 
                            type="text" 
                            id="fromStation" 
                            class="station-search-input" 
                            placeholder="Type to search starting station..."
                            autocomplete="off"
                        />
                        <div id="fromStationSuggestions" class="autocomplete-suggestions"></div>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-flag-checkered"></i>
                        To Station
                    </label>
                    <div class="autocomplete-container">
                        <input 
                            type="text" 
                            id="toStation" 
                            class="station-search-input" 
                            placeholder="Type to search destination station..."
                            autocomplete="off"
                        />
                        <div id="toStationSuggestions" class="autocomplete-suggestions"></div>
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" onclick="calculateDistance()">
                    <i class="fas fa-calculator"></i>
                    Calculate Distance
                </button>
                <button class="btn btn-secondary" onclick="showRoute()">
                    <i class="fas fa-route"></i>
                    Show Route
                </button>
                <button class="btn btn-secondary" onclick="clearResults()">
                    <i class="fas fa-times"></i>
                    Clear Results
                </button>
            </div>
        </div>

        <!-- Results Panel -->
        <div id="resultsPanel" class="panel" style="display: none;">
            <div class="panel-header">
                <h3 class="panel-title">
                    <i class="fas fa-chart-line"></i>
                    Calculation Results
                </h3>
            </div>
            <div id="resultsContent"></div>
        </div>

        <!-- Station Details Panel -->
        <div id="stationDetailsPanel" class="station-details-panel">
            <div class="station-details-header">
                <div class="station-info-header">
                    <div id="stationDetailsName" class="station-details-name"></div>
                    <div id="stationDetailsBrand" class="station-details-brand"></div>
                    <div class="station-basic-info">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt" style="color: #dc2626;"></i>
                            <span id="stationDetailsDistrict"></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-tint" style="color: #dc2626;"></i>
                            <span id="stationDetailsFuels"></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock" style="color: #10b981;"></i>
                            <span id="stationDetailsStatus"></span>
                        </div>
                        <div class="info-item" style="width: 100%;">
                            <i class="fas fa-location-dot" style="color: #dc2626;"></i>
                            <span id="stationDetailsAddress"></span>
                        </div>
                    </div>
                </div>
                <button class="close-btn" onclick="closeStationDetails()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="year-tabs">
                <div class="year-tab active" data-year="2022" onclick="selectYear(2022)">2022</div>
                <div class="year-tab" data-year="2023" onclick="selectYear(2023)">2023</div>
                <div class="year-tab" data-year="2024" onclick="selectYear(2024)">2024</div>
            </div>

            <div id="fuelDataContainer" class="fuel-data-grid"></div>
        </div>

        <br><br>
        <!-- Map Container -->
        <div class="map-container">
            <div class="map-overlay">
                <div class="map-control" id="satellite-toggle" onclick="toggleSatelliteView()">
                    <i class="fas fa-satellite"></i>
                    <span id="view-text">Satellite</span>
                </div>

                <div class="map-control" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i>
                    <span>Fullscreen</span>
                </div>

                <div class="map-control" onclick="locateUser()">
                    <i class="fas fa-location-arrow"></i>
                    <span>My Location</span>
                </div>
            </div>
            <div id="map"></div>
        </div>

        <div id="loading" class="loading-spinner" style="display: none;">
            <div class="spinner"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Global variables
        let map;
        let markersGroup;
        let routeGroup;
        let allStations = [];
        let filteredStations = [];
        let filteredMapStations = [];
        let userLocation = null;
        let satelliteView = false;
        let osmLayer, satelliteLayer;
        let selectedStation = null;
        let currentYear = 2022;
        let showDepos = true;
        let station = []
        let depo = []

        // User dropdown toggle function
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const avatar = document.querySelector('.user-avatar');
            
            if (dropdown && !dropdown.contains(event.target) && event.target !== avatar) {
                dropdown.classList.remove('active');
            }
        });

        // Route calculation backend service
        class RouteCalculator {
            static calculateDistance(lat1, lon1, lat2, lon2) {
                const R = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                          Math.sin(dLon/2) * Math.sin(dLon/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            }

            static calculateTravelTime(distance, speed = 60) {
                return distance / speed;
            }

            static formatTime(hours) {
                const totalMinutes = Math.round(hours * 60);
                const h = Math.floor(totalMinutes / 60);
                const m = totalMinutes % 60;
                return h > 0 ? `${h}h ${m}m` : `${m}m`;
            }

            static async getActualRoute(fromLat, fromLng, toLat, toLng) {
                try {
                    // Using OSRM (Open Source Routing Machine) - Free and no API key required
                    const url = `https://router.project-osrm.org/route/v1/driving/${fromLng},${fromLat};${toLng},${toLat}?overview=full&geometries=geojson`;
                    
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                        const route = data.routes[0];
                        const coordinates = route.geometry.coordinates.map(coord => [coord[1], coord[0]]); // Swap lon,lat to lat,lon
                        
                        return {
                            coordinates: coordinates,
                            distance: route.distance / 1000, // Convert meters to kilometers
                            duration: route.duration / 3600, // Convert seconds to hours
                            instructions: route.legs[0].steps || []
                        };
                    } else {
                        // Fallback to straight line if routing fails
                        console.warn('Routing failed, using fallback');
                        return this.getFallbackRoute(fromLat, fromLng, toLat, toLng);
                    }
                } catch (error) {
                    console.error('Route calculation error:', error);
                    // Fallback to straight line
                    return this.getFallbackRoute(fromLat, fromLng, toLat, toLng);
                }
            }

            static getFallbackRoute(lat1, lng1, lat2, lng2) {
                const waypoints = this.generateWaypoints(lat1, lng1, lat2, lng2);
                return {
                    coordinates: waypoints,
                    distance: this.calculateDistance(lat1, lng1, lat2, lng2),
                    duration: this.calculateTravelTime(this.calculateDistance(lat1, lng1, lat2, lng2)),
                    isFallback: true
                };
            }

            static async getRoute(fromLat, fromLng, toLat, toLng) {
                // Use actual routing
                return await this.getActualRoute(fromLat, fromLng, toLat, toLng);
            }

            static generateWaypoints(lat1, lng1, lat2, lng2, points = 10) {
                const waypoints = [];
                for (let i = 0; i <= points; i++) {
                    const ratio = i / points;
                    const lat = lat1 + (lat2 - lat1) * ratio;
                    const lng = lng1 + (lng2 - lng1) * ratio;
                    waypoints.push([lat, lng]);
                }
                return waypoints;
            }
        }

        function loadStations() {
            fetch('getStations.php')
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    allStations = [...data.stations, ...data.depos];
                    station = [...data.stations];
                    depo = [...data.depos]
                    filteredStations = data;
                    //showStationsOnMap(filteredStations);
                    populateStationDropdowns();
                    updateStats();
                })
                .catch(err => {
                    console.error('Failed to load stations:', err);
                    showMessage('Failed to load station data. Please refresh the page.', 'error');
                });
        }

   function mapStations() {
    fetch('getMapStations.php')
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            // Handle the new combined response
            // filteredMapStations = data.stations || [];
             allDepos = data.depos || [];
            filteredMapStations = [...data.stations, ...data.depos];
            // allStations = [...data.stations, ...data.depos];
            
            // Show both on map
            showStationsOnMap(filteredMapStations);
           // showDeposOnMap(allDepos);
        })
        .catch(err => {
            console.error('Failed to load stations and depos:', err);
            showMessage('Failed to load station and depo data. Please refresh the page.', 'error');
        });
}

        // Display stations on the map
        function showStationsOnMap(stations) {
            markersGroup.clearLayers();
            
            stations.forEach(station => {
                const marker = L.marker([station.latitude, station.longitude], {
                    icon: createMarkerIcon(station.brand,station.type)
                }).bindPopup(createPopupContent(station));
                
                marker.on('click', function() {
                    showStationDetails(station.id);
                });
                // console.log("Created marker:", marker);
               // console.log(station); 
                // console.log("panal closeed"); 
                //  console.log(filteredMapStations);

                markersGroup.addLayer(marker);
            });

            

            if (stations.length > 0) {
                const group = new L.featureGroup(markersGroup.getLayers());
                if (group.getBounds().isValid()) {
                    map.fitBounds(group.getBounds(), { padding: [20, 20] });
                }
            }
        }
    
        // Initialize map
        function initializeMap() {
            map = L.map('map').setView([7.8731, 80.7718], 8);
            
            // Add tile layers
            osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            });
            
            satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '© Esri'
            });
            
            osmLayer.addTo(map);
            markersGroup = L.layerGroup().addTo(map);
            routeGroup = L.layerGroup().addTo(map);
            
            // Get user location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                   userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                    
                    // Add user location marker
                    L.marker([userLocation.lat, userLocation.lng], {
                        icon: L.divIcon({
                            className: 'user-location-marker',
                            html: '<div style="background: #e74c3c; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.3);"><i class="fas fa-user"></i></div>',
                            iconSize: [26, 26],
                            iconAnchor: [13, 13]
                        })
                    }).addTo(map).bindPopup('Your Location');
                });
            }
        }

        
        // Create custom marker icons
function createMarkerIcon(brand, type, isSelected = false) {
    let colors = {
        'Ceylon Petroleum Corporation': '#b51200ff',
        'Lanka IOC': '#d7c607ff',
        'Rpml': '#1006c6ff',
        'Sinopec': '#08883dff',
        'default': '#858585ff'
    };

    const color = colors[brand] || colors.default;
    const size = isSelected ? 50 : 40;

    // Choose different icons for stations vs depos
    const iconSymbol = type === 'depo' 
        ? '<i class="fas fa-warehouse" style="transform: rotate(45deg);"></i>' 
        : '<i class="fas fa-gas-pump" style="transform: rotate(45deg);"></i>';

    return L.divIcon({
        className: 'custom-marker',
        html: `
            <div style="
                background: ${color};
                color: white;
                width: ${size}px;
                height: ${size}px;
                border-radius: 50% 50% 50% 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: ${size === 50 ? '18px' : '16px'};
                font-weight: bold;
                border: 3px solid white;
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                transform: rotate(-45deg);
                position: relative;
            ">
                ${iconSymbol}
            </div>
        `,
        iconSize: [size, size],
        iconAnchor: [size/2, size],
        popupAnchor: [0, -size]
    });
}


        

        // Create popup content with fuel availability
        function createPopupContent(station) {
            const distance = userLocation ? 
                RouteCalculator.calculateDistance(userLocation.lat, userLocation.lng, station.latitude, station.longitude) : 
                null;

            function generateFuelAvailability(station) {
                if (!station.fuel_availability) return '';
                
                let html = '<div class="fuel-availability">';
                
                // Get current year data (2024 or latest available)
                const years = Object.keys(station.fuel_availability).sort().reverse();
                const currentYearData = station.fuel_availability[years[0]];
                
                if (currentYearData) {
                    html += '</div>';
                    html += '</div>';
                }
                
                html += '</div>';
                return html;
            }

            return `
                <div style="min-width: 300px;">
                    <h3 style="margin: 0; font-size: 1.3rem; color: #2c3e50; margin-bottom: 8px;">${station.name}</h3>
                    <p style="margin: 0 0 10px 0; opacity: 0.9; color: #10b981; font-weight: 600;">${station.brand}</p>
                    ${distance ? `<p style="margin: 10px 0; color: #27ae60; font-weight: 600;"><i class="fas fa-route" style="margin-right: 8px; color: #dc2626;"></i> ${distance.toFixed(1)} km away</p>` : ''}
                    <p style="margin: 10px 0; color: #27ae60; font-weight: 600;"></p>
                    ${generateFuelAvailability(station)}
                    <div style="text-align: center; margin-top: 15px;">
                        <button onclick="showStationDetails(${station.id})" style="background: linear-gradient(135deg, #dc2626, #1f2937); color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; font-weight: 600;">
                            <i class="fas fa-eye" style="margin-right: 8px;"></i>View Details
                        </button>
                    </div>
                </div>
            `;
        }

        // Show station details panel
        function showStationDetails(stationId) {
            selectedStation = allStations.find(s => s.id === stationId);
            if (!selectedStation) return;

            // Update station details
            document.getElementById('stationDetailsName').textContent = selectedStation.name;
            document.getElementById('stationDetailsBrand').textContent = selectedStation.brand;
            document.getElementById('stationDetailsDistrict').textContent = selectedStation.district;
            document.getElementById('stationDetailsFuels').textContent = selectedStation.fuel_types;
            document.getElementById('stationDetailsStatus').textContent = selectedStation.status;
            document.getElementById('stationDetailsAddress').textContent = selectedStation.address || 'Not available';

            // Show panel and load fuel data
            document.getElementById('stationDetailsPanel').style.display = 'block';
            document.getElementById('stationDetailsPanel').scrollIntoView({ behavior: 'smooth' });
            
            // Load fuel data for current year
            loadFuelData(currentYear);
        }

        // Close station details panel
        function closeStationDetails() {
            document.getElementById('stationDetailsPanel').style.display = 'none';
            selectedStation = null;
        }

        // Select year and load fuel data
        function selectYear(year) {
            currentYear = year;
            
            // Update active tab
            document.querySelectorAll('.year-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.year-tab[data-year="${year}"]`).classList.add('active');
            
            // Load fuel data for selected year
            loadFuelData(year);
        }

        // Load and display fuel data for selected year
        function loadFuelData(year) {
            if (!selectedStation || !selectedStation.fuel_availability || !selectedStation.fuel_availability[year]) {
                document.getElementById('fuelDataContainer').innerHTML =
                    `<p style="text-align: center; color: #6b7280;">No fuel data available for ${year}</p>`;
                return;
            }

            const yearData = selectedStation.fuel_availability[year];
            const container = document.getElementById('fuelDataContainer');

            // Match the database field names exactly
            const fuelTypes = {
                "Auto Diesel": { label: "Auto Diesel", icon: "fas fa-oil-can" },
                "Auto Super Diesel": { label: "Auto Super Diesel", icon: "fas fa-gas-pump" },
                "92 Petrol": { label: "92 Petrol", icon: "fas fa-tint" },
                "95 Petrol": { label: "95 Petrol", icon: "fas fa-fire" }
            };

            let html = '';

            Object.keys(fuelTypes).forEach(fuelKey => {
                const fuelInfo = fuelTypes[fuelKey];
                const fuelData = yearData[fuelKey];
                const isAvailable = fuelData && fuelData.available;

                html += `
                    <div class="fuel-card ${isAvailable ? 'available' : 'unavailable'}">
                        <div class="fuel-card-header">
                            <div class="fuel-type-name">
                                <i class="${fuelInfo.icon}"></i>
                                ${fuelInfo.label}
                            </div>
                        </div>
                        <div class="fuel-details">
                            <div class="fuel-detail-item">
                                <span class="fuel-detail-label">Outgoing Stock</span>
                                <span class="fuel-detail-value fuel-stock">
                                    ${isAvailable && fuelData.stock ? `${Number(fuelData.stock).toLocaleString()} KL` : 'N/A'}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Autocomplete functionality for station search
        class StationAutocomplete {
            constructor(inputId, suggestionsId) {
                this.input = document.getElementById(inputId);
                this.suggestionsContainer = document.getElementById(suggestionsId);
                this.selectedStation = null;
                this.currentFocus = -1;
                this.init();
            }

            init() {
                this.input.addEventListener('input', (e) => this.handleInput(e));
                this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
                this.input.addEventListener('focus', (e) => this.handleInput(e));
                
                // Close suggestions when clicking outside
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.autocomplete-container')) {
                        this.closeSuggestions();
                    }
                });
            }

            handleInput(e) {
                const value = this.input.value.toLowerCase().trim();
                this.currentFocus = -1;

                if (value.length === 0) {
                    this.showAllStations();
                    return;
                }

                // Remove duplicates before filtering
                const uniqueStations = allStations.filter((station, index, self) =>
                    index === self.findIndex((s) => s.id === station.id)
                );

                const filtered = uniqueStations.filter(station => {
                    const searchText = `${station.name} ${station.district} ${station.brand}`.toLowerCase();
                    return searchText.includes(value);
                });

                this.displaySuggestions(filtered, value);
            }

            showAllStations() {
                // Remove duplicates before showing all stations
                const uniqueStations = allStations.filter((station, index, self) =>
                    index === self.findIndex((s) => s.id === station.id)
                );
                this.displaySuggestions(uniqueStations, '');
            }

            displaySuggestions(stations, searchTerm) {
                this.suggestionsContainer.innerHTML = '';

                if (stations.length === 0) {
                    this.suggestionsContainer.innerHTML = '<div class="no-results">No stations found</div>';
                    this.suggestionsContainer.classList.add('active');
                    return;
                }

                stations.forEach(station => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    
                    const stationText = `${station.name} - ${station.district} (${station.brand})`;
                    const highlightedText = this.highlightMatch(stationText, searchTerm);
                    
                    item.innerHTML = `
                        <div class="station-info">
                            <div class="station-name">${highlightedText}</div>
                            <div class="station-details">
                                <i class="fas fa-map-marker-alt" style="color: #dc2626; font-size: 0.8rem;"></i>
                                ${station.district} | 
                                <i class="fas fa-building" style="color: #dc2626; font-size: 0.8rem;"></i>
                                ${station.brand}
                            </div>
                        </div>
                    `;
                    
                    item.addEventListener('click', () => this.selectStation(station));
                    this.suggestionsContainer.appendChild(item);
                });

                this.suggestionsContainer.classList.add('active');
            }

            highlightMatch(text, term) {
                if (!term) return text;
                
                const regex = new RegExp(`(${term})`, 'gi');
                return text.replace(regex, '<span class="autocomplete-highlight">$1</span>');
            }

            selectStation(station) {
                this.selectedStation = station;
                this.input.value = `${station.name} - ${station.district}`;
                this.input.dataset.stationId = station.id;
                this.closeSuggestions();
            }

            handleKeydown(e) {
                const items = this.suggestionsContainer.querySelectorAll('.autocomplete-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.currentFocus++;
                    this.setActive(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.currentFocus--;
                    this.setActive(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (this.currentFocus > -1 && items[this.currentFocus]) {
                        items[this.currentFocus].click();
                    }
                } else if (e.key === 'Escape') {
                    this.closeSuggestions();
                }
            }

            setActive(items) {
                if (!items.length) return;
                
                // Remove all selected classes
                items.forEach(item => item.classList.remove('selected'));
                
                // Wrap around
                if (this.currentFocus >= items.length) this.currentFocus = 0;
                if (this.currentFocus < 0) this.currentFocus = items.length - 1;
                
                // Add selected class and scroll into view
                items[this.currentFocus].classList.add('selected');
                items[this.currentFocus].scrollIntoView({ block: 'nearest' });
            }

            closeSuggestions() {
                this.suggestionsContainer.classList.remove('active');
                this.currentFocus = -1;
            }

            getSelectedStationId() {
                return this.input.dataset.stationId || null;
            }

            clear() {
                this.input.value = '';
                this.input.dataset.stationId = '';
                this.selectedStation = null;
                this.closeSuggestions();
            }
        }

        // Initialize autocomplete instances
        let fromStationAutocomplete;
        let toStationAutocomplete;
        let referenceStationAutocomplete;

        // Populate station dropdowns for route calculator (updated for autocomplete)
        function populateStationDropdowns() {
            // Initialize autocomplete after stations are loaded
            fromStationAutocomplete = new StationAutocomplete('fromStation', 'fromStationSuggestions');
            toStationAutocomplete = new StationAutocomplete('toStation', 'toStationSuggestions');
        }

        // Populate reference station dropdown with autocomplete
        function populateReferenceStationDropdown() {
            // Initialize autocomplete for reference station
            referenceStationAutocomplete = new StationAutocomplete('referenceStation', 'referenceStationSuggestions');
        }

        // Calculate distance between two stations
        async function calculateDistance() {
            const fromId = fromStationAutocomplete.getSelectedStationId();
            const toId = toStationAutocomplete.getSelectedStationId();
            
            if (!fromId || !toId) {
                showMessage('Please select both starting and destination stations.', 'error');
                return;
            }
            
            if (fromId === toId) {
                showMessage('Please select different stations for origin and destination.', 'error');
                return;
            }
            
            const fromStation = allStations.find(s => s.id == fromId);
            const toStation = allStations.find(s => s.id == toId);
            
            showLoading();
            
            try {
                const distance = RouteCalculator.calculateDistance(
                    fromStation.latitude, fromStation.longitude,
                    toStation.latitude, toStation.longitude
                );
                
                const travelTime = RouteCalculator.calculateTravelTime(distance);
                const fuelCost = calculateFuelCost(distance);
                
                const result = {
                    fromStation,
                    toStation,
                    distance: distance.toFixed(2),
                    travelTime: RouteCalculator.formatTime(travelTime),
                    fuelCost: fuelCost.toFixed(2)
                };
                
                displayResults([result], 'distance');
                hideLoading();
                
            } catch (error) {
                hideLoading();
                showMessage('Error calculating distance. Please try again.', 'error');
            }
        }

        // Function to hide other stations and show only route stations
        function hideOtherStationsForRoute(fromStationId, toStationId) {
            // Clear existing markers
            markersGroup.clearLayers();
            
            // Find the from and to stations
            const fromStation = allStations.find(s => s.id == fromStationId);
            const toStation = allStations.find(s => s.id == toStationId);
            
            if (!fromStation || !toStation) {
                console.error('Stations not found');
                return;
            }
            
            // Add only the start marker
            const startMarker = L.marker([fromStation.latitude, fromStation.longitude], {
                icon: createMarkerIcon(fromStation.brand,fromStation.type, true)
            }).bindPopup(`<strong>Start:</strong> ${fromStation.name}<br/><em>${fromStation.district}</em>`);
            
            // Add only the end marker
            const endMarker = L.marker([toStation.latitude, toStation.longitude], {
                icon: createMarkerIcon(toStation.brand,toStation.type, true)
            }).bindPopup(`<strong>Destination:</strong> ${toStation.name}<br/><em>${toStation.district}</em>`);
            
            // Add markers to the map
            markersGroup.addLayer(startMarker);
            markersGroup.addLayer(endMarker);
            
            return { startMarker, endMarker, fromStation, toStation };
        }

        // Function to restore all stations on map
        function showAllStations() {
            markersGroup.clearLayers();
            showStationsOnMap(filteredMapStations);
        }

        // Enhanced showRoute function
        async function showRoute() {
            const fromId = fromStationAutocomplete.getSelectedStationId();
            const toId = toStationAutocomplete.getSelectedStationId();
            
            if (!fromId || !toId) {
                showMessage('Please select both starting and destination stations.', 'error');
                return;
            }
            
            if (fromId === toId) {
                showMessage('Please select different stations for origin and destination.', 'error');
                return;
            }
            
            showLoading();
            
            try {
                // Hide other stations and show only route stations
                const routeStations = hideOtherStationsForRoute(fromId, toId);
                
                if (!routeStations) {
                    hideLoading();
                    return;
                }
                
                const { fromStation, toStation } = routeStations;
                
                // Get actual road route
                const route = await RouteCalculator.getRoute(
                    fromStation.latitude, fromStation.longitude,
                    toStation.latitude, toStation.longitude
                );
                
                // Clear previous routes
                routeGroup.clearLayers();
                
                // Add route line with improved styling
                const routeLine = L.polyline(route.coordinates, {
                    color: '#dc2626',
                    weight: 5,
                    opacity: 0.8,
                    lineJoin: 'round',
                    lineCap: 'round'
                }).addTo(routeGroup);
                
                // Add direction arrows along the route
                const arrowIcon = L.divIcon({
                    html: '<div style="color: #dc2626; font-size: 20px; transform: rotate(0deg);">➤</div>',
                    className: 'route-arrow',
                    iconSize: [20, 20]
                });
                
                // Add arrows at intervals along the route
                if (route.coordinates.length > 5) {
                    const arrowInterval = Math.floor(route.coordinates.length / 5);
                    for (let i = arrowInterval; i < route.coordinates.length; i += arrowInterval) {
                        if (i + 1 < route.coordinates.length) {
                            const point1 = route.coordinates[i];
                            const point2 = route.coordinates[i + 1];
                            const angle = Math.atan2(point2[1] - point1[1], point2[0] - point1[0]) * 180 / Math.PI;
                            
                            const arrowMarker = L.marker(point1, {
                                icon: L.divIcon({
                                    html: `<div style="color: #dc2626; font-size: 16px; transform: rotate(${angle + 90}deg);">➤</div>`,
                                    className: 'route-arrow',
                                    iconSize: [16, 16]
                                })
                            }).addTo(routeGroup);
                        }
                    }
                }
                
                // Add start and end markers
                const routeStartMarker = L.marker([fromStation.latitude, fromStation.longitude], {
                    icon: createMarkerIcon(fromStation.brand, fromStation.type, true)
                }).bindPopup(`
                    <div style="min-width: 200px;">
                        <h4 style="margin: 0; color: #dc2626;">🚀 START</h4>
                        <h3 style="margin: 5px 0;">${fromStation.name}</h3>
                        <p style="margin: 5px 0; color: #666;">${fromStation.district}</p>
                        <p style="margin: 5px 0; color: #10b981; font-weight: 600;">${fromStation.brand}</p>
                    </div>
                `).addTo(routeGroup);
                
                const routeEndMarker = L.marker([toStation.latitude, toStation.longitude], {
                    icon: createMarkerIcon(toStation.brand, toStation.type, true)
                }).bindPopup(`
                    <div style="min-width: 200px;">
                        <h4 style="margin: 0; color: #dc2626;">🏁 DESTINATION</h4>
                        <h3 style="margin: 5px 0;">${toStation.name}</h3>
                        <p style="margin: 5px 0; color: #666;">${toStation.district}</p>
                        <p style="margin: 5px 0; color: #10b981; font-weight: 600;">${toStation.brand}</p>
                    </div>
                `).addTo(routeGroup);
                
                // Fit map to route bounds with padding
                map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
                
                const result = {
                    fromStation,
                    toStation,
                    distance: route.distance.toFixed(2),
                    travelTime: RouteCalculator.formatTime(route.duration),
                    fuelCost: calculateFuelCost(route.distance).toFixed(2),
                    route: route.coordinates,
                    routeType: route.isFallback ? 'Estimated (Direct)' : 'Actual Road Route'
                };
                
                displayResults([result], 'route');
                
                if (route.isFallback) {
                    showMessage('Note: Using estimated direct route. Actual road routing unavailable.', 'info');
                }
                
                hideLoading();
                
            } catch (error) {
                hideLoading();
                console.error('Route error:', error);
                showMessage('Error calculating route. Please try again.', 'error');
            }
        }

       

        // Calculate estimated fuel cost
        function calculateFuelCost(distance, fuelPrice = 365, efficiency = 12) {
            return (distance / efficiency) * fuelPrice;
        }

        // Display calculation results
        function displayResults(results, type) {
            const panel = document.getElementById('resultsPanel');
            const content = document.getElementById('resultsContent');
            
            let html = '';
            
           results.forEach((result, index) => {
                html += `
                    <div class="result-card">
                        <div class="result-header">
                            <div class="result-title">
                                ${type === 'nearest' ? `Nearest Station #${index + 1}` : 
                                  type === 'route' ? 'Route Information' : 'Distance Calculation'}
                            </div>
                            <div class="distance-badge">${result.distance} km</div>
                        </div>
                        <div class="result-details">
                            <div class="detail-item">
                                <i class="fas fa-play" style="color: #dc2626; width: 16px;"></i>
                                From: ${result.fromStation.name}
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-flag-checkered" style="color: #dc2626; width: 16px;"></i>
                                To: ${result.toStation.name}
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock" style="color: #dc2626; width: 16px;"></i>
                                Travel Time: ${result.travelTime}
                            </div>
                            ${result.routeType ? `
                            <div class="detail-item">
                                <i class="fas fa-route" style="color: #dc2626; width: 16px;"></i>
                                Route Type: ${result.routeType}
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            content.innerHTML = html;
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth' });
        }

        // Clear calculation results
        function clearResults() {
            document.getElementById('resultsPanel').style.display = 'none';
            routeGroup.clearLayers();
            if (fromStationAutocomplete) fromStationAutocomplete.clear();
            if (toStationAutocomplete) toStationAutocomplete.clear();
            showAllStations();
        }

        // Show message to user
        function showMessage(message, type = 'info') {
            const className = type === 'error' ? 'error-message' : type === 'info' ? 'success-message' : 'success-message';
            const content = document.getElementById('resultsContent');
            content.innerHTML = `<div class="${className}">${message}</div>`;
            document.getElementById('resultsPanel').style.display = 'block';
            
            if (type !== 'info') {
                setTimeout(() => {
                    document.getElementById('resultsPanel').style.display = 'none';
                }, 3000);
            }
        }

        // Search and filter functions
        function searchStations() {
            applyFilters();
        }

// Add a type so you can distinguish
// Flatten and guard against undefined variables
allStations = [
  ...(station || []).map(s => ({ ...s, type: 'station' })),
  ...(depo   || []).map(d => ({ ...d, type: 'depo' }))
];

// helper to normalize fuel_types into a lowercased array
function normalizeFuelTypes(item) {
  if (!item || item.fuel_types == null) return [];
  if (Array.isArray(item.fuel_types)) {
    return item.fuel_types.map(ft => ft.toString().toLowerCase());
  }
  // if it's a comma-separated string from backend
  if (typeof item.fuel_types === 'string') {
    return item.fuel_types.split(',').map(s => s.trim().toLowerCase());
  }
  return [];
}

function applyFilters() {
  const searchTerm = (document.getElementById('search-input').value || '').toLowerCase().trim();
  const districtFilter = (document.getElementById('district').value || '').toLowerCase();
  const fuelFilter = (document.getElementById('fuel-type').value || '').toLowerCase();
  const brandFilter = (document.getElementById('brand').value || '').toLowerCase();
  const depoFilter = (document.getElementById('depo').value || '').toLowerCase();

  filteredStations = allStations.filter(item => {
    // Normalize fields to avoid undefined errors
    const name = (item.name || '').toString().toLowerCase();
    const district = (item.district || '').toString().toLowerCase();
    // for stations, depot name usually in item.depo; for depo items, name holds the depo name
    const depoName = ((item.depo || item.name) || '').toString().toLowerCase();
    const fuelTypes = normalizeFuelTypes(item);

    const matchesSearch = !searchTerm ||
      name.includes(searchTerm) ||
      district.includes(searchTerm) ||
      depoName.includes(searchTerm);

    const matchesDistrict = !districtFilter || district === districtFilter;
    // fuel filter checks normalized fuelTypes array (works if backend returns array or CSV)
    const matchesFuel = !fuelFilter || fuelTypes.includes(fuelFilter);
    const matchesBrand = !brandFilter || ((item.brand || '').toString().toLowerCase() === brandFilter);
    // match depot by station.depo OR by depo.name
    const matchesDepo = !depoFilter || depoName === depoFilter;

    return matchesSearch && matchesDistrict && matchesFuel && matchesBrand && matchesDepo;
  });

  console.log('filteredStations', filteredStations);
  console.log('allStations length', allStations.length, 'sample item:', allStations[0]);
  showStationsOnMap(filteredStations);
}



        function clearAllFilters() {
            document.getElementById('search-input').value = '';
            document.getElementById('district').value = '';
            document.getElementById('fuel-type').value = '';
            document.getElementById('brand').value = '';
            document.getElementById('depo').value = '';
            closeStationDetails();
            filteredStations = [...allStations];
            showStationsOnMap(filteredStations);
        }


        // Depo Station Counter functionality
        document.getElementById('depoCounter').addEventListener('change', function() {
            const depoId = this.value;
            
            if (!depoId) {
                document.getElementById('counterResult').style.display = 'none';
                return;
            }
            
            loadDepoStationCount(depoId);
        });

        async function loadDepoStationCount(depoId) {
            try {
                const response = await fetch(`?action=get_station_count&depo_id=${depoId}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('selectedDepoName').textContent = data.depo_name;
                    document.getElementById('stationCountNumber').textContent = data.station_count;
                    document.getElementById('counterResult').style.display = 'block';
                } else {
                    console.error('Error:', data.error);
                }
            } catch (error) {
                console.error('Network error:', error);
            }
        }

        // Map control functions
        function toggleSatelliteView() {
            satelliteView = !satelliteView;
            const toggleBtn = document.getElementById('satellite-toggle');
            const viewText = document.getElementById('view-text');
            
            if (satelliteView) {
                map.removeLayer(osmLayer);
                map.addLayer(satelliteLayer);
                toggleBtn.classList.add('active');
                viewText.textContent = 'Street';
            } else {
                map.removeLayer(satelliteLayer);
                map.addLayer(osmLayer);
                toggleBtn.classList.remove('active');
                viewText.textContent = 'Satellite';
            }
        }
        function locateUser() {
            if (!userLocation) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        
                        map.setView([userLocation.lat, userLocation.lng], 15);
                        
                        L.marker([userLocation.lat, userLocation.lng], {
                            icon: L.divIcon({
                                className: 'user-location-marker',
                                html: '<div style="background: #e74c3c; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.3);"><i class="fas fa-user"></i></div>',
                                iconSize: [26, 26],
                                iconAnchor: [13, 13]
                            })
                        }).addTo(map).bindPopup('Your Location');
                    });
                }
            } else {
                map.setView([userLocation.lat, userLocation.lng], 15);
            }
        }

        function toggleFullscreen() {
            const mapContainer = document.getElementById('map');
            
            if (!document.fullscreenElement) {
                mapContainer.requestFullscreen().then(() => {
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);
                });
            } else {
                document.exitFullscreen().then(() => {
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);
                });
            }
        }

        // Utility functions
        function updateStats() {
            document.getElementById('total-stations').textContent = allStations.length;
        }

        function updateDisplay() {
            updateMapMarkers();
        }

        function showLoading() {
            document.getElementById('loading').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        // Initialize application
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            loadStations();
            mapStations();
            
            let searchTimeout;
            document.getElementById('search-input').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 300);
            });
            
            document.getElementById('district').addEventListener('change', applyFilters);
            document.getElementById('fuel-type').addEventListener('change', applyFilters);
            document.getElementById('brand').addEventListener('change', applyFilters);
            document.getElementById('depo').addEventListener('change', applyFilters);
        });

        
        initializeMap();
        loadStations();
        mapStations();

     // Add these functions to your existing JavaScript

// Note: populateReferenceStationDropdown() now uses autocomplete - see above function

// Toggle between station selection and my location
function toggleReferenceInput() {
    const referenceType = document.getElementById('referenceType').value;
    const stationGroup = document.getElementById('stationSelectGroup');
    
    if (referenceType === 'station') {
        stationGroup.style.display = 'block';
    } else {
        stationGroup.style.display = 'none';
    }
}

// Main function to find nearest stations based on reference type
function findNearestToReference() {
    const referenceType = document.getElementById('referenceType').value;
    
    if (referenceType === 'station') {
        findNearestToStation();
    } else {
        findNearestToMyLocation();
    }
}

// Find nearest stations to my location
function findNearestToMyLocation() {
    if (!userLocation) {
        showMessage('Location access required. Please enable location services and try again.', 'error');
        
        // Try to get location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Try again after getting location
                    findNearestToMyLocation();
                },
                function(error) {
                    showMessage('Unable to access your location. Please check your browser settings.', 'error');
                }
            );
        }
        return;
    }
    
    const count = parseInt(document.getElementById('nearestCount').value);
    
    showLoading();
    
    // Remove duplicates from allStations based on station ID
    const uniqueStations = allStations.filter((station, index, self) =>
        index === self.findIndex((s) => s.id === station.id)
    );
    
    // Calculate distances to all stations
    const stationsWithDistance = uniqueStations.map(station => {
        const distance = RouteCalculator.calculateDistance(
            userLocation.lat, 
            userLocation.lng,
            station.latitude, 
            station.longitude
        );
        return { 
            ...station, 
            distance,
            travelTime: RouteCalculator.calculateTravelTime(distance),
            fuelCost: calculateFuelCost(distance)
        };
    });
    
    // Sort by distance and get top N
    stationsWithDistance.sort((a, b) => a.distance - b.distance);
    const nearestStations = stationsWithDistance.slice(0, count);
    
    // Create reference object for my location
    const myLocationReference = {
        name: 'My Current Location',
        district: 'Your Position',
        brand: 'GPS Location',
        latitude: userLocation.lat,
        longitude: userLocation.lng,
        isUserLocation: true
    };
    
    // Clear existing layers first
    markersGroup.clearLayers();
    routeGroup.clearLayers();
    
    // Display results
    displayNearestStationsResults(myLocationReference, nearestStations);
    
    // Show only the nearest stations on map
    showNearestStationsOnMap(nearestStations);
    
    // Add special marker for user location
    highlightUserLocation();
    
    // Draw lines to nearest stations
    drawNearestStationLines(myLocationReference, nearestStations);
    
    // Fit map to show all stations including user location
    const stationsToShow = [myLocationReference, ...nearestStations];
    fitMapToStations(stationsToShow);
    
    hideLoading();
}

// Find nearest stations to selected reference station
function findNearestToStation() {
    const referenceStationId = referenceStationAutocomplete.getSelectedStationId();
    const count = parseInt(document.getElementById('nearestCount').value);
    
    if (!referenceStationId) {
        showMessage('Please select a reference station first.', 'error');
        return;
    }
    
    showLoading();
    
    // Remove duplicates from allStations based on station ID
    const uniqueStations = allStations.filter((station, index, self) =>
        index === self.findIndex((s) => s.id === station.id)
    );
    
    const referenceStation = uniqueStations.find(s => s.id == referenceStationId);
    
    if (!referenceStation) {
        hideLoading();
        showMessage('Reference station not found.', 'error');
        return;
    }
    
    // Calculate distances to all other unique stations
    const stationsWithDistance = uniqueStations
        .filter(s => s.id != referenceStationId) // Exclude the reference station itself
        .map(station => {
            const distance = RouteCalculator.calculateDistance(
                referenceStation.latitude, 
                referenceStation.longitude,
                station.latitude, 
                station.longitude
            );
            return { 
                ...station, 
                distance,
                travelTime: RouteCalculator.calculateTravelTime(distance),
                fuelCost: calculateFuelCost(distance)
            };
        });
    
    // Sort by distance and get top N
    stationsWithDistance.sort((a, b) => a.distance - b.distance);
    const nearestStations = stationsWithDistance.slice(0, count);
    
    // Clear existing layers first
    markersGroup.clearLayers();
    routeGroup.clearLayers();
    
    // Display results
    displayNearestStationsResults(referenceStation, nearestStations);
    
    // Show only nearest stations on map
    showNearestStationsOnMap(nearestStations);
    
    // Add special marker for reference station
    highlightReferenceStation(referenceStation);
    
    // Draw lines to nearest stations
    drawNearestStationLines(referenceStation, nearestStations);
    
    // Fit map to show all stations
    const stationsToShow = [referenceStation, ...nearestStations];
    fitMapToStations(stationsToShow);
    
    hideLoading();
}

// Display nearest stations results
function displayNearestStationsResults(referenceStation, nearestStations) {
    const resultDiv = document.getElementById('nearestStationsResult');
    const contentDiv = document.getElementById('nearestStationsContent');
    
    // Clear previous content completely
    contentDiv.innerHTML = '';
    
    let html = `
        <div class="result-card" style="background: rgba(220, 38, 38, 0.1); border-color: #dc2626; margin-bottom: 1.5rem;">
            <div class="result-header">
                <div class="result-title">
                    <i class="fas fa-${referenceStation.isUserLocation ? 'user' : 'map-pin'}" style="color: #dc2626;"></i>
                    Reference Point: ${referenceStation.name}
                </div>
            </div>
            ${!referenceStation.isUserLocation ? `
            <div class="result-details">
                <div class="detail-item">
                    <i class="fas fa-map-marker-alt" style="color: #dc2626; width: 16px;"></i>
                    ${referenceStation.district}
                </div>
                <div class="detail-item">
                    <i class="fas fa-building" style="color: #dc2626; width: 16px;"></i>
                    ${referenceStation.brand}
                </div>
            </div>
            ` : `
            <div class="result-details">
                <div class="detail-item">
                    <i class="fas fa-crosshairs" style="color: #dc2626; width: 16px;"></i>
                    Using GPS coordinates
                </div>
            </div>
            `}
        </div>
        
        <h4 style="color: #ffffff; margin: 1.5rem 0 1rem 0; padding-left: 0.5rem; border-left: 3px solid #dc2626;">
            <i class="fas fa-list-ol"></i> Nearest Stations
        </h4>
    `;
    
    nearestStations.forEach((station, index) => {
        html += `
            <div class="result-card" style="cursor: pointer;" onclick="showStationDetails(${station.id})">
                <div class="result-header">
                    <div class="result-title">
                        <span style="background: linear-gradient(135deg, #dc2626, #1f2937); 
                                     color: white; 
                                     padding: 4px 12px; 
                                     border-radius: 50%; 
                                     margin-right: 10px;
                                     font-weight: 700;
                                     display: inline-block;
                                     min-width: 30px;
                                     text-align: center;">
                            ${index + 1}
                        </span>
                        ${station.name}
                    </div>
                    <div class="distance-badge">${station.distance.toFixed(2)} km</div>
                </div>
                <div class="result-details">
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt" style="color: #dc2626; width: 16px;"></i>
                        ${station.district}
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-building" style="color: #dc2626; width: 16px;"></i>
                        ${station.brand}
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock" style="color: #dc2626; width: 16px;"></i>
                        Travel: ${RouteCalculator.formatTime(station.travelTime)}
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-tint" style="color: #dc2626; width: 16px;"></i>
                        Est. Cost: Rs ${station.fuelCost.toFixed(2)}
                    </div>
                </div>
            </div>
        `;
    });
    
    // Set the content once
    contentDiv.innerHTML = html;
    resultDiv.style.display = 'block';
    
    // Scroll smoothly
    setTimeout(() => {
        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}

// Show nearest stations on map without duplicating the reference station
function showNearestStationsOnMap(stations) {
    stations.forEach(station => {
        const marker = L.marker([station.latitude, station.longitude], {
            icon: createMarkerIcon(station.brand, station.type)
        }).bindPopup(createPopupContent(station));
        
        marker.on('click', function() {
            showStationDetails(station.id);
        });
        
        markersGroup.addLayer(marker);
    });
}

// Highlight user location on map
function highlightUserLocation() {
    const marker = L.marker([userLocation.lat, userLocation.lng], {
        icon: L.divIcon({
            className: 'user-reference-marker',
            html: `
                <div style="
                    background: #e74c3c;
                    color: white;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: bold;
                    border: 4px solid #ffd700;
                    box-shadow: 0 4px 20px rgba(231, 76, 60, 0.5);
                    position: relative;
                    animation: pulse 2s infinite;
                ">
                    <i class="fas fa-user"></i>
                </div>
            `,
            iconSize: [60, 60],
            iconAnchor: [30, 30],
            popupAnchor: [0, -30]
        })
    }).bindPopup(`
        <div style="min-width: 250px;">
            <h3 style="margin: 0; color: #e74c3c;">📍 YOUR LOCATION</h3>
            <h4 style="margin: 10px 0;">Current Position</h4>
            <p style="margin: 5px 0;"><strong>Latitude:</strong> ${userLocation.lat.toFixed(6)}</p>
            <p style="margin: 5px 0;"><strong>Longitude:</strong> ${userLocation.lng.toFixed(6)}</p>
        </div>
    `);
    
    markersGroup.addLayer(marker);
}

// Highlight reference station on map
function highlightReferenceStation(station) {
    const marker = L.marker([station.latitude, station.longitude], {
        icon: L.divIcon({
            className: 'reference-marker',
            html: `
                <div style="
                    background: #dc2626;
                    color: white;
                    width: 60px;
                    height: 60px;
                    border-radius: 50% 50% 50% 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: bold;
                    border: 4px solid #ffd700;
                    box-shadow: 0 4px 20px rgba(220, 38, 38, 0.5);
                    transform: rotate(-45deg);
                    position: relative;
                    animation: pulse 2s infinite;
                ">
                    <i class="fas fa-star" style="transform: rotate(45deg);"></i>
                </div>
            `,
            iconSize: [60, 60],
            iconAnchor: [30, 60],
            popupAnchor: [0, -60]
        })
    }).bindPopup(`
        <div style="min-width: 250px;">
            <h3 style="margin: 0; color: #dc2626;">📍 REFERENCE STATION</h3>
            <h4 style="margin: 10px 0;">${station.name}</h4>
            <p style="margin: 5px 0;"><strong>District:</strong> ${station.district}</p>
            <p style="margin: 5px 0;"><strong>Brand:</strong> ${station.brand}</p>
        </div>
    `);
    
    markersGroup.addLayer(marker);
}

// Draw lines from reference station to nearest stations
function drawNearestStationLines(referenceStation, nearestStations) {
    routeGroup.clearLayers();
    
    nearestStations.forEach((station, index) => {
        const color = index < 3 ? '#10b981' : '#f59e0b'; // Green for top 3, orange for rest
        
        const line = L.polyline([
            [referenceStation.latitude, referenceStation.longitude],
            [station.latitude, station.longitude]
        ], {
            color: color,
            weight: 3,
            opacity: 0.6,
            dashArray: '5, 10'
        }).bindPopup(`
            Distance: ${station.distance.toFixed(2)} km<br>
            Travel Time: ${RouteCalculator.formatTime(station.travelTime)}
        `);
        
        routeGroup.addLayer(line);
    });
}

// Fit map to show all stations
function fitMapToStations(stations) {
    if (stations.length === 0) return;
    
    const bounds = L.latLngBounds(
        stations.map(s => [s.latitude, s.longitude])
    );
    
    map.fitBounds(bounds, { 
        padding: [50, 50],
        maxZoom: 12
    });
}

// Clear nearest station search
function clearNearestSearch() {
    if (referenceStationAutocomplete) referenceStationAutocomplete.clear();
    document.getElementById('referenceType').value = 'station';
    document.getElementById('stationSelectGroup').style.display = 'block';
    document.getElementById('nearestStationsResult').style.display = 'none';
    
    // Clear all layers
    markersGroup.clearLayers();
    routeGroup.clearLayers();
    
    // Restore all stations on map
    showStationsOnMap(filteredMapStations);
}

// Add CSS animation for pulsing effect
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.5);
        }
        50% {
            box-shadow: 0 4px 30px rgba(220, 38, 38, 0.8);
        }
    }
`;
document.head.appendChild(style);

// Update the DOMContentLoaded event to include the new dropdown population
document.addEventListener('DOMContentLoaded', function() {
    // ... existing initialization code ...
    
    // Populate reference station dropdown after stations are loaded
    setTimeout(() => {
        populateReferenceStationDropdown();
    }, 1000);
});

// Also call it after loadStations completes
function loadStations() {
    fetch('getStations.php')
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            allStations = [...data.stations, ...data.depos];
            station = [...data.stations];
            depo = [...data.depos];
            filteredStations = data;
            populateStationDropdowns();
            populateReferenceStationDropdown(); // Add this linea
            updateStats();
        })
        .catch(err => {
            console.error('Failed to load stations:', err);
            showMessage('Failed to load station data. Please refresh the page.', 'error');
        });
}
    </script>
</body>
</html>