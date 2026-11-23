<?php
// Database Configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'fualStations';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Depot Management Class
class Depot {
    private $conn;
    private $table_name = "depo";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllDepots() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY depo_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDepotById($depo_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE depo_id = :depo_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':depo_id' => $depo_id]);
        return $stmt->fetch();
    }

    public function getDepotsByStation($acct_no) {
        $query = "SELECT d.* FROM " . $this->table_name . " d
                  INNER JOIN depo_by_station ds ON d.depo_id = ds.depo_id
                  WHERE ds.acct_no = :acct_no
                  ORDER BY d.depo_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':acct_no' => $acct_no]);
        return $stmt->fetchAll();
    }

    public function assignDepotToStation($acct_no, $depo_id) {
        $query = "INSERT INTO depo_by_station (acct_no, depo_id) VALUES (:acct_no, :depo_id)";
        $stmt = $this->conn->prepare($query);
        
        try {
            $stmt->execute([
                ':acct_no' => $acct_no,
                ':depo_id' => $depo_id
            ]);
            return ['success' => true, 'message' => 'Depot assigned successfully'];
        } catch(PDOException $e) {
            if($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'This depot is already assigned to the station'];
            }
            return ['success' => false, 'message' => 'Error assigning depot: ' . $e->getMessage()];
        }
    }

    public function removeDepotFromStation($acct_no, $depo_id) {
        $query = "DELETE FROM depo_by_station WHERE acct_no = :acct_no AND depo_id = :depo_id";
        $stmt = $this->conn->prepare($query);
        
        try {
            $stmt->execute([
                ':acct_no' => $acct_no,
                ':depo_id' => $depo_id
            ]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Depot removed successfully'];
            } else {
                return ['success' => false, 'message' => 'Depot assignment not found or already removed'];
            }
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error removing depot: ' . $e->getMessage()];
        }
    }
}

// Station Management Class (Updated)
class Station {
    private $conn;
    private $table_name = "fuel_stations";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addStation($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (acct_no, name, district, brand, latitude, longitude, address, contact_phone, status) 
                  VALUES (:acct_no, :name, :district, :brand, :latitude, :longitude, :address, :contact_phone, :status)";

        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([
                ':acct_no' => $data['acct_no'],
                ':name' => htmlspecialchars(strip_tags($data['name'])),
                ':district' => $data['district'],
                ':brand' => $data['brand'],
                ':latitude' => $data['latitude'],
                ':longitude' => $data['longitude'],
                ':address' => htmlspecialchars(strip_tags($data['address'])),
                ':contact_phone' => $data['contact_phone'],
                ':status' => $data['status']
            ]);
            return ['success' => true, 'message' => 'Station added successfully'];
        } catch(PDOException $e) {
            if($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Station with this account number already exists'];
            }
            return ['success' => false, 'message' => 'Error adding station: ' . $e->getMessage()];
        }
    }

    public function updateStation($acct_no, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, district = :district, brand = :brand, 
                      latitude = :latitude, longitude = :longitude, 
                      address = :address, contact_phone = :contact_phone, status = :status
                  WHERE acct_no = :acct_no";

        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([
                ':acct_no' => $acct_no,
                ':name' => htmlspecialchars(strip_tags($data['name'])),
                ':district' => $data['district'],
                ':brand' => $data['brand'],
                ':latitude' => $data['latitude'],
                ':longitude' => $data['longitude'],
                ':address' => htmlspecialchars(strip_tags($data['address'])),
                ':contact_phone' => $data['contact_phone'],
                ':status' => $data['status']
            ]);
            return ['success' => true, 'message' => 'Station updated successfully'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error updating station: ' . $e->getMessage()];
        }
    }

    public function getStationByAcctNo($acct_no) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE acct_no = :acct_no";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':acct_no' => $acct_no]);
        return $stmt->fetch();
    }

    public function getAllStations() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchStations($searchTerm) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE name LIKE :search 
                  OR acct_no LIKE :search 
                  OR district LIKE :search 
                  OR brand LIKE :search 
                  OR address LIKE :search 
                  OR contact_phone LIKE :search
                  ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $searchPattern = '%' . $searchTerm . '%';
        $stmt->execute([':search' => $searchPattern]);
        return $stmt->fetchAll();
    }

    public function deleteStation($acct_no) {
        $query = "DELETE FROM " . $this->table_name . " WHERE acct_no = :acct_no";
        $stmt = $this->conn->prepare($query);
        
        try {
            $stmt->execute([':acct_no' => $acct_no]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Station deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Station not found'];
            }
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error deleting station: ' . $e->getMessage()];
        }
    }
}

// Fuel Quantity Management Class
class FuelQuantity {
    private $conn;
    private $table_name = "fuel_quantity";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addFuelStock($data) {
        $check_query = "SELECT id FROM " . $this->table_name . " 
                       WHERE station_acct_no = :station_acct_no 
                       AND fuel_type = :fuel_type 
                       AND year = :year";
        
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->execute([
            ':station_acct_no' => $data['station_acct_no'],
            ':fuel_type' => $data['fuel_type'],
            ':year' => $data['year']
        ]);

        if($check_stmt->fetch()) {
            return ['success' => false, 'message' => 'Fuel record for this station, type and year already exists. Use edit to update.'];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (station_acct_no, fuel_type, year, load_liters, available_liters) 
                  VALUES (:station_acct_no, :fuel_type, :year, :load_liters, :available_liters)";

        $stmt = $this->conn->prepare($query);
        
        try {
            $stmt->execute([
                ':station_acct_no' => $data['station_acct_no'],
                ':fuel_type' => $data['fuel_type'],
                ':year' => $data['year'],
                ':load_liters' => $data['load_liters'],
                ':available_liters' => $data['available_liters']
            ]);
            return ['success' => true, 'message' => 'Fuel stock added successfully'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error adding fuel stock: ' . $e->getMessage()];
        }
    }

    public function updateFuelStock($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET load_liters = :load_liters, available_liters = :available_liters 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        
        try {
            $stmt->execute([
                ':id' => $id,
                ':load_liters' => $data['load_liters'],
                ':available_liters' => $data['available_liters']
            ]);
            return ['success' => true, 'message' => 'Fuel stock updated successfully'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update fuel stock: ' . $e->getMessage()];
        }
    }

    public function getAllFuelRecords() {
        $query = "SELECT fq.*, fs.name as station_name, fs.district 
                  FROM " . $this->table_name . " fq
                  LEFT JOIN fuel_stations fs ON fq.station_acct_no = fs.acct_no
                  ORDER BY fs.name ASC, fq.fuel_type ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Initialize
$database = new Database();
$db = $database->getConnection();
$station = new Station($db);
$fuelQuantity = new FuelQuantity($db);
$depot = new Depot($db);

$message = '';
$messageType = 'success';
$stations = [];
$editingStation = null;
$deleteStation = null;
$searchTerm = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_station':
            $required_fields = ['acct_no', 'name', 'district', 'brand', 'latitude', 'longitude'];
            $missing_fields = array_filter($required_fields, function($field) {
                return empty($_POST[$field]);
            });
            
            if (!empty($missing_fields)) {
                $message = 'Please fill in all required fields';
                $messageType = 'error';
            } elseif (!preg_match('/^\d{6}$/', $_POST['acct_no'])) {
                $message = 'Account number must be exactly 6 digits';
                $messageType = 'error';
            } else {
                $station_data = [
                    'acct_no' => intval($_POST['acct_no']),
                    'name' => $_POST['name'],
                    'district' => $_POST['district'],
                    'brand' => $_POST['brand'],
                    'latitude' => floatval($_POST['latitude']),
                    'longitude' => floatval($_POST['longitude']),
                    'address' => $_POST['address'] ?? '',
                    'contact_phone' => $_POST['contact_phone'] ?? '',
                    'status' => $_POST['status'] ?? 'Open'
                ];
                
                $result = $station->addStation($station_data);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';

                // If station added successfully and depot selected, assign depot
                if ($result['success'] && !empty($_POST['depo_id'])) {
                    $depot_result = $depot->assignDepotToStation(intval($_POST['acct_no']), intval($_POST['depo_id']));
                    if ($depot_result['success']) {
                        $message .= ' and depot assigned successfully';
                    } else {
                        $message .= ' but depot assignment failed: ' . $depot_result['message'];
                    }
                }
            }
            break;

        case 'update_station':
            $required_fields = ['edit_acct_no', 'name', 'district', 'brand', 'latitude', 'longitude'];
            $missing_fields = array_filter($required_fields, function($field) {
                return empty($_POST[$field]);
            });
            
            if (!empty($missing_fields)) {
                $message = 'Please fill in all required fields';
                $messageType = 'error';
            } else {
                $station_data = [
                    'name' => $_POST['name'],
                    'district' => $_POST['district'],
                    'brand' => $_POST['brand'],
                    'latitude' => floatval($_POST['latitude']),
                    'longitude' => floatval($_POST['longitude']),
                    'address' => $_POST['address'] ?? '',
                    'contact_phone' => $_POST['contact_phone'] ?? '',
                    'status' => $_POST['status'] ?? 'Open'
                ];
                
                $result = $station->updateStation(intval($_POST['edit_acct_no']), $station_data);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
            break;

        case 'assign_depot':
            if (empty($_POST['station_acct_no']) || empty($_POST['depo_id'])) {
                $message = 'Invalid station or depot';
                $messageType = 'error';
            } else {
                $result = $depot->assignDepotToStation(intval($_POST['station_acct_no']), intval($_POST['depo_id']));
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
            break;

        case 'remove_depot':
            if (empty($_POST['station_acct_no']) || empty($_POST['depo_id'])) {
                $message = 'Invalid station or depot';
                $messageType = 'error';
            } else {
                $result = $depot->removeDepotFromStation(intval($_POST['station_acct_no']), intval($_POST['depo_id']));
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                
                // Redirect back to manage depot page to refresh the list
                if ($result['success']) {
                    header("Location: ?manage_depot=" . intval($_POST['station_acct_no']));
                    exit();
                }
            }
            break;

        case 'add_fuel_stock':
            $required_fields = ['station_acct_no', 'fuel_type', 'year'];
            $missing_fields = array_filter($required_fields, function($field) {
                return empty($_POST[$field]);
            });
            
            if (!empty($missing_fields)) {
                $message = 'Please fill in all required fields';
                $messageType = 'error';
            } else {
                $fuel_data = [
                    'station_acct_no' => intval($_POST['station_acct_no']),
                    'fuel_type' => $_POST['fuel_type'],
                    'year' => intval($_POST['year']),
                    'load_liters' => intval($_POST['load_liters'] ?? 0),
                    'available_liters' => intval($_POST['available_liters'] ?? 0)
                ];
                
                $result = $fuelQuantity->addFuelStock($fuel_data);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
            break;

        case 'update_fuel_stock':
            if (empty($_POST['fuel_id'])) {
                $message = 'Invalid fuel record ID';
                $messageType = 'error';
            } else {
                $update_data = [
                    'load_liters' => intval($_POST['load_liters']),
                    'available_liters' => intval($_POST['available_liters'])
                ];
                
                $result = $fuelQuantity->updateFuelStock(intval($_POST['fuel_id']), $update_data);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
            break;

        case 'delete_station':
            if (empty($_POST['acct_no'])) {
                $message = 'Invalid station account number';
                $messageType = 'error';
            } else {
                $result = $station->deleteStation(intval($_POST['acct_no']));
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
            break;
    }
}

// Handle station editing request
if (isset($_GET['edit_station'])) {
    $editingStation = $station->getStationByAcctNo(intval($_GET['edit_station']));
    if (!$editingStation) {
        $message = 'Station not found';
        $messageType = 'error';
    }
}

// Handle manage depot request
$managingDepot = null;
if (isset($_GET['manage_depot'])) {
    $managingDepot = $station->getStationByAcctNo(intval($_GET['manage_depot']));
    if (!$managingDepot) {
        $message = 'Station not found';
        $messageType = 'error';
    }
}

if (isset($_GET['delete_station']) && isset($_GET['confirm'])) {
    $result = $station->deleteStation(intval($_GET['delete_station']));
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?tab=edit-stations");
    exit();   
}

// Handle search
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $stations = $station->searchStations($searchTerm);
} else {
    $stations = $station->getAllStations();
}

$allDepots = $depot->getAllDepots();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Management - Fuel Finder LK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #1f2937;
            --dark-bg: #111827;
            --card-bg: #1f2937;
            --text-light: #adadadff;
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
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: var(--gradient);
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
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

        .panel-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-container {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(220, 38, 38, 0.3);
        }

        .search-btn {
            padding: 12px 24px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.4);
        }

        .clear-search-btn {
            padding: 12px 24px;
            background: rgba(255,255,255,0.1);
            color: var(--text-light);
            border: 2px solid rgba(220, 38, 38, 0.3);
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .clear-search-btn:hover {
            border-color: var(--primary-color);
            background: rgba(220, 38, 38, 0.1);
        }

        .search-results-info {
            margin-bottom: 1rem;
            color: var(--text-muted);
            font-style: italic;
        }

        .search-highlight {
            background: rgba(220, 38, 38, 0.3);
            padding: 2px 4px;
            border-radius: 3px;
            color: var(--text-light);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-input, .form-select, .form-textarea {
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(220, 38, 38, 0.3);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-select {
            cursor: pointer;
            appearance: none;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
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
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.95rem;
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
            border-color: var(--primary-color);
            background: rgba(220, 38, 38, 0.1);
        }

        .btn-warning {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
            border: 2px solid rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            border-color: var(--warning);
            background: rgba(245, 158, 11, 0.3);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error);
            border: 2px solid rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            border-color: var(--error);
            background: rgba(239, 68, 68, 0.3);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
        }

        .table-container {
            background: rgba(255,255,255,0.02);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(220, 38, 38, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: rgba(220, 38, 38, 0.1);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: var(--text-muted);
        }

        .table tbody tr:hover {
            background: rgba(220, 38, 38, 0.05);
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge.success {
            background: var(--success);
            color: white;
        }

        .badge.warning {
            background: var(--warning);
            color: white;
        }

        .badge.error {
            background: var(--error);
            color: white;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .tab {
            padding: 1rem 2rem;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab.active {
            color: var(--text-light);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .required {
            color: var(--error);
        }

        .station-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(220, 38, 38, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .station-card:hover {
            border-color: var(--primary-color);
            background: rgba(220, 38, 38, 0.05);
        }

        .station-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .station-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-light);
        }

        .station-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .station-detail {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .station-detail strong {
            color: var(--text-light);
        }

        .depot-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .depot-tag {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .depot-tag button {
            background: none;
            border: none;
            color: var(--error);
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .depot-tag button:hover {
            transform: scale(1.2);
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

        .station-name-autocomplete {
            font-weight: 600;
            color: var(--text-light);
        }

        .station-details-autocomplete {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .no-results {
            padding: 15px;
            text-align: center;
            color: var(--text-muted);
            font-style: italic;
        }

        .depot-assignment-section {
            background: rgba(255,255,255,0.02);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid rgba(220, 38, 38, 0.1);
        }

        .depot-assignment-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
                gap: 0;
            }
            
            .tab {
                border-bottom: 1px solid rgba(255,255,255,0.1);
                border-radius: 0;
            }

            .station-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .station-details {
                grid-template-columns: 1fr;
            }

            .search-box {
                flex-direction: column;
            }

            .search-input {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="header-title">
                <i class="fas fa-cog"></i>
                Station Management
            </h1>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Map
            </a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab <?= !$editingStation && !$managingDepot ? 'active' : '' ?>" onclick="switchTab('add-station')">
                <i class="fas fa-plus-circle"></i>
                Add New Station
            </button>
            <button class="tab" onclick="switchTab('manage-fuel')">
                <i class="fas fa-tint"></i>
                Manage Fuel Stock
            </button>
            <button class="tab <?= ($editingStation || isset($_GET['tab']) && $_GET['tab'] == 'edit-stations') ? 'active' : '' ?>" onclick="switchTab('edit-stations')">
                <i class="fas fa-edit"></i>
                Edit Station Details
            </button>
            <button class="tab <?= $managingDepot ? 'active' : '' ?>" onclick="switchTab('manage-depots')">
                <i class="fas fa-warehouse"></i>
                Manage Depots
            </button>
        </div>

        <!-- Add New Station Tab -->
        <div id="add-station" class="tab-content <?= !$editingStation && !$managingDepot ? 'active' : '' ?>">
            <div class="panel">
                <h3 class="panel-title">
                    <i class="fas fa-gas-pump"></i>
                    Add New Fuel Station
                </h3>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_station">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Account Number <span class="required">*</span></label>
                            <input type="number" name="acct_no" class="form-input" required 
                                   placeholder="Enter 6-digit account number">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Station Name <span class="required">*</span></label>
                            <input type="text" name="name" class="form-input" required 
                                   placeholder="Enter station name">
                        </div>

                        <div class="form-group">
                            <label class="form-label">District <span class="required">*</span></label>
                            <select name="district" class="form-select" required>
                                <option value="">Select District</option>
                                <option value="Ampara">Ampara</option>
                                <option value="Anuradhapura">Anuradhapura</option>
                                <option value="Badulla">Badulla</option>
                                <option value="Batticaloa">Batticaloa</option>
                                <option value="Colombo">Colombo</option>
                                <option value="Galle">Galle</option>
                                <option value="Gampaha">Gampaha</option>
                                <option value="Hambantota">Hambantota</option>
                                <option value="Jaffna">Jaffna</option>
                                <option value="Kalutara">Kalutara</option>
                                <option value="Kandy">Kandy</option>
                                <option value="Kegalle">Kegalle</option>
                                <option value="Kilinochchi">Kilinochchi</option>
                                <option value="Kurunegala">Kurunegala</option>
                                <option value="Mannar">Mannar</option>
                                <option value="Matale">Matale</option>
                                <option value="Matara">Matara</option>
                                <option value="Monaragala">Monaragala</option>
                                <option value="Mullaitivu">Mullaitivu</option>
                                <option value="Nuwara Eliya">Nuwara Eliya</option>
                                <option value="Polonnaruwa">Polonnaruwa</option>
                                <option value="Puttalam">Puttalam</option>
                                <option value="Ratnapura">Ratnapura</option>
                                <option value="Trincomalee">Trincomalee</option>
                                <option value="Vavuniya">Vavuniya</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Company <span class="required">*</span></label>
                            <select name="brand" class="form-select" required>
                                <option value="">Select Company</option>
                                <option value="Ceylon Petroleum Corporation">Ceylon Petroleum Corporation</option>
                                <option value="Lanka IOC">Lanka IOC</option>
                                <option value="Rpml">Rpml</option>
                                <option value="Sinopec">Sinopec</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Latitude <span class="required">*</span></label>
                            <input type="number" name="latitude" class="form-input" step="0.00000001" required 
                                   placeholder="e.g., 6.9271">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Longitude <span class="required">*</span></label>
                            <input type="number" name="longitude" class="form-input" step="0.00000001" required 
                                   placeholder="e.g., 79.8612">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Assign Depot (Optional)</label>
                            <select name="depo_id" class="form-select">
                                <option value="">Select Depot</option>
                                <?php foreach($allDepots as $d): ?>
                                    <option value="<?= $d['depo_id'] ?>">
                                        <?= htmlspecialchars($d['depo_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contact Phone</label>
                            <input type="tel" name="contact_phone" class="form-input" 
                                   placeholder="e.g., +94701234567">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Open">Open</option>
                                <option value="Closed">Closed</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-textarea" 
                                  placeholder="Enter full address"></textarea>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Station
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manage Fuel Stock Tab -->
        <div id="manage-fuel" class="tab-content">
            <div class="panel">
                <h3 class="panel-title">
                    <i class="fas fa-tint"></i>
                    Add Fuel Stock
                </h3>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_fuel_stock">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Station <span class="required">*</span></label>
                            <div class="autocomplete-container">
                                <input 
                                    type="text" 
                                    id="fuelStationSearch" 
                                    class="form-input" 
                                    placeholder="Type to search station..."
                                    autocomplete="off"
                                    required
                                />
                                <input type="hidden" name="station_acct_no" id="selectedStationAcctNo" required>
                                <div id="fuelStationSuggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Fuel Type <span class="required">*</span></label>
                            <select name="fuel_type" class="form-select" required>
                                <option value="">Select Fuel Type</option>
                                <option value="Auto Diesel">Auto Diesel</option>
                                <option value="Auto Super Diesel">Auto Super Diesel</option>
                                <option value="92 Petrol">92 Petrol</option>
                                <option value="95 Petrol">95 Petrol</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Year <span class="required">*</span></label>
                            <select name="year" class="form-select" required>
                                <option value="">Select Year</option>
                                <?php for($year = 2020; $year <= date('Y'); $year++): ?>
                                    <option value="<?= $year ?>" <?= $year == date('Y') ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>


                        <div class="form-group">
                            <label class="form-label">Out going stock (Kl)</label>
                            <input type="number" name="available_liters" class="form-input" min="0" 
                                   placeholder="Available fuel stock" value="0">
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Fuel Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Station Details Tab -->
        <div id="edit-stations" class="tab-content <?= $editingStation ? 'active' : '' ?>">
            <?php if ($editingStation): ?>
                <div class="panel">
                    <h3 class="panel-title">
                        <i class="fas fa-edit"></i>
                        Edit Station Details
                    </h3>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_station">
                        <input type="hidden" name="edit_acct_no" value="<?= $editingStation['acct_no'] ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Account Number (Read Only)</label>
                                <input type="text" class="form-input" value="<?= htmlspecialchars($editingStation['acct_no']) ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Station Name <span class="required">*</span></label>
                                <input type="text" name="name" class="form-input" required 
                                       value="<?= htmlspecialchars($editingStation['name']) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">District <span class="required">*</span></label>
                                <select name="district" class="form-select" required>
                                    <option value="">Select District</option>
                                    <?php 
                                    $districts = ['Ampara', 'Anuradhapura', 'Badulla', 'Batticaloa', 'Colombo', 'Galle', 'Gampaha', 'Hambantota', 'Jaffna', 'Kalutara', 'Kandy', 'Kegalle', 'Kilinochchi', 'Kurunegala', 'Mannar', 'Matale', 'Matara', 'Monaragala', 'Mullaitivu', 'Nuwara Eliya', 'Polonnaruwa', 'Puttalam', 'Ratnapura', 'Trincomalee', 'Vavuniya'];
                                    foreach($districts as $district): ?>
                                        <option value="<?= $district ?>" <?= $editingStation['district'] == $district ? 'selected' : '' ?>>
                                            <?= $district ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Company <span class="required">*</span></label>
                                <select name="brand" class="form-select" required>
                                    <option value="">Select Company</option>
                                    <?php 
                                    $brands = ['Ceylon Petroleum Corporation', 'Lanka IOC', 'Rpml', 'Sinopec'];
                                    foreach($brands as $brand): ?>
                                        <option value="<?= $brand ?>" <?= $editingStation['brand'] == $brand ? 'selected' : '' ?>>
                                            <?= $brand ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Latitude <span class="required">*</span></label>
                                <input type="number" name="latitude" class="form-input" step="0.00000001" required 
                                       value="<?= $editingStation['latitude'] ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Longitude <span class="required">*</span></label>
                                <input type="number" name="longitude" class="form-input" step="0.00000001" required 
                                       value="<?= $editingStation['longitude'] ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" name="contact_phone" class="form-input" 
                                       value="<?= htmlspecialchars($editingStation['contact_phone']) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php 
                                    $statuses = ['Open', 'Closed', 'Maintenance'];
                                    foreach($statuses as $status): ?>
                                        <option value="<?= $status ?>" <?= $editingStation['status'] == $status ? 'selected' : '' ?>>
                                            <?= $status ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-textarea"><?= htmlspecialchars($editingStation['address']) ?></textarea>
                        </div>

                        <div style="text-align: center; margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                            <a href="?tab=edit-stations" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to List
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Update Station
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="search-container">
                    <form method="GET" class="search-box">
                        <input type="hidden" name="tab" value="edit-stations">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search by station name, account number, district, company, address, or phone..." 
                               value="<?= htmlspecialchars($searchTerm) ?>">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                            Search
                        </button>
                        <?php if ($searchTerm): ?>
                            <a href="?tab=edit-stations" class="clear-search-btn">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="panel">
                    <h3 class="panel-title">
                        <i class="fas fa-list"></i>
                        <?php if ($searchTerm): ?>
                            Search Results
                        <?php else: ?>
                            All Stations
                        <?php endif; ?>
                    </h3>

                    <?php if ($searchTerm): ?>
                        <div class="search-results-info">
                            <?php 
                            $resultCount = count($stations);
                            if ($resultCount > 0): ?>
                                Found <?= $resultCount ?> station<?= $resultCount != 1 ? 's' : '' ?> matching "<?= htmlspecialchars($searchTerm) ?>"
                            <?php else: ?>
                                No stations found matching "<?= htmlspecialchars($searchTerm) ?>"
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($stations)): ?>
                        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="fas fa-<?= $searchTerm ? 'search' : 'gas-pump' ?>" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <h3><?= $searchTerm ? 'No Results Found' : 'No Stations Found' ?></h3>
                            <p><?= $searchTerm ? 'Try searching with different keywords.' : 'Add your first fuel station to get started.' ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach($stations as $station_item): 
                            $stationDepots = $depot->getDepotsByStation($station_item['acct_no']);
                        ?>
                            <div class="station-card">
                                <div class="station-header">
                                    <div>
                                        <div class="station-name">
                                            <?php
                                            $name = htmlspecialchars($station_item['name']);
                                            if ($searchTerm) {
                                                $name = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="search-highlight">$1</span>', $name);
                                            }
                                            echo $name;
                                            ?>
                                        </div>
                                        <div style="color: var(--text-muted); font-size: 0.9rem;">
                                            Account: 
                                            <?php
                                            $acct = $station_item['acct_no'];
                                            if ($searchTerm && strpos($acct, $searchTerm) !== false) {
                                                $acct = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="search-highlight">$1</span>', $acct);
                                            }
                                            echo $acct;
                                            ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <span class="badge <?= $station_item['status'] == 'Open' ? 'success' : ($station_item['status'] == 'Closed' ? 'error' : 'warning') ?>">
                                            <?= $station_item['status'] ?>
                                        </span>
                                        <a href="?manage_depot=<?= $station_item['acct_no'] ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-warehouse"></i>
                                            Depots
                                        </a>
                                        <a href="?edit_station=<?= $station_item['acct_no'] ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this station?')">
                                            <input type="hidden" name="action" value="delete_station">
                                            <input type="hidden" name="acct_no" value="<?= $station_item['acct_no'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fa-solid fa-trash"></i>
                                                Delete 
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="station-details">
                                    <div class="station-detail">
                                        <strong>District:</strong> 
                                        <?php
                                        $district = htmlspecialchars($station_item['district']);
                                        if ($searchTerm && stripos($district, $searchTerm) !== false) {
                                            $district = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="search-highlight">$1</span>', $district);
                                        }
                                        echo $district;
                                        ?>
                                    </div>
                                    <div class="station-detail">
                                        <strong>Company:</strong> 
                                        <?php
                                        $brand = htmlspecialchars($station_item['brand']);
                                        if ($searchTerm && stripos($brand, $searchTerm) !== false) {
                                            $brand = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="search-highlight">$1</span>', $brand);
                                        }
                                        echo $brand;
                                        ?>
                                    </div>
                                    <div class="station-detail">
                                        <strong>Location:</strong> <?= $station_item['latitude'] ?>, <?= $station_item['longitude'] ?>
                                    </div>
                                    <?php if ($station_item['contact_phone']): ?>
                                        <div class="station-detail">
                                            <strong>Phone:</strong> 
                                            <?php
                                            $phone = htmlspecialchars($station_item['contact_phone']);
                                            if ($searchTerm && stripos($phone, $searchTerm) !== false) {
                                                $phone = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="search-highlight">$1</span>', $phone);
                                            }
                                            echo $phone;
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($station_item['address']): ?>
                                    <div class="station-detail">
                                        <strong>Address:</strong> 
                                        <?php
                                        $address = htmlspecialchars($station_item['address']);
                                        if ($searchTerm && stripos($address, $searchTerm) !== false) {
                                            $address = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="search-highlight">$1</span>', $address);
                                        }
                                        echo $address;
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($stationDepots)): ?>
                                    <div class="station-detail">
                                        <strong>Assigned Depots:</strong>
                                        <div class="depot-list">
                                            <?php foreach($stationDepots as $d): ?>
                                                <span class="depot-tag">
                                                    <i class="fas fa-warehouse"></i>
                                                    <?= htmlspecialchars($d['depo_name']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Manage Depots Tab -->
        <div id="manage-depots" class="tab-content <?= $managingDepot ? 'active' : '' ?>">
            <?php if ($managingDepot): 
                $assignedDepots = $depot->getDepotsByStation($managingDepot['acct_no']);
                $assignedDepotIds = array_column($assignedDepots, 'depo_id');
            ?>
                <div class="panel">
                    <h3 class="panel-title">
                        <i class="fas fa-warehouse"></i>
                        Manage Depots for <?= htmlspecialchars($managingDepot['name']) ?>
                    </h3>

                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 10px; margin-bottom: 2rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            <div>
                                <strong>Account:</strong> <?= $managingDepot['acct_no'] ?>
                            </div>
                            <div>
                                <strong>District:</strong> <?= htmlspecialchars($managingDepot['district']) ?>
                            </div>
                            <div>
                                <strong>Company:</strong> <?= htmlspecialchars($managingDepot['brand']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Currently Assigned Depots -->
                    <div class="depot-assignment-section">
                        <h4 class="depot-assignment-title">
                            <i class="fas fa-check-circle"></i>
                            Currently Assigned Depots
                        </h4>
                        
                        <?php if (empty($assignedDepots)): ?>
                            <p style="color: var(--text-muted); font-style: italic;">No depots assigned to this station yet.</p>
                        <?php else: ?>
                            <div style="display: grid; gap: 1rem;">
                                <?php foreach($assignedDepots as $d): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 10px; border: 1px solid rgba(16, 185, 129, 0.3);">
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-light); margin-bottom: 0.25rem;">
                                                <i class="fas fa-warehouse"></i>
                                                <?= htmlspecialchars($d['depo_name']) ?>
                                            </div>
                                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                                Location: <?= $d['latitude'] ?>, <?= $d['longitude'] ?>
                                            </div>
                                        </div>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="remove_depot">
                                            <input type="hidden" name="station_acct_no" value="<?= $managingDepot['acct_no'] ?>">
                                            <input type="hidden" name="depo_id" value="<?= $d['depo_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove <?= htmlspecialchars($d['depo_name']) ?> from this station?')">
                                                <i class="fas fa-times"></i>
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Assign New Depot -->
                    <div class="depot-assignment-section">
                        <h4 class="depot-assignment-title">
                            <i class="fas fa-plus-circle"></i>
                            Assign New Depot
                        </h4>
                        
                        <?php 
                        $availableDepots = array_filter($allDepots, function($d) use ($assignedDepotIds) {
                            return !in_array($d['depo_id'], $assignedDepotIds);
                        });
                        ?>

                        <?php if (empty($availableDepots)): ?>
                            <p style="color: var(--text-muted); font-style: italic;">All available depots have been assigned to this station.</p>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="assign_depot">
                                <input type="hidden" name="station_acct_no" value="<?= $managingDepot['acct_no'] ?>">
                                
                                <div style="display: flex; gap: 1rem; align-items: flex-end;">
                                    <div class="form-group" style="flex: 1;">
                                        <label class="form-label">Select Depot <span class="required">*</span></label>
                                        <select name="depo_id" class="form-select" required>
                                            <option value="">Choose a depot...</option>
                                            <?php foreach($availableDepots as $d): ?>
                                                <option value="<?= $d['depo_id'] ?>">
                                                    <?= htmlspecialchars($d['depo_name']) ?> (<?= $d['latitude'] ?>, <?= $d['longitude'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Assign Depot
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="?tab=edit-stations" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Station List
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="panel">
                    <h3 class="panel-title">
                        <i class="fas fa-warehouse"></i>
                        All Depots
                    </h3>

                    <?php if (empty($allDepots)): ?>
                        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="fas fa-warehouse" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <h3>No Depots Available</h3>
                            <p>Depots need to be added to the database.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 1rem;">
                            <?php foreach($allDepots as $d): ?>
                                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(220, 38, 38, 0.2); border-radius: 15px; padding: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <div style="font-size: 1.2rem; font-weight: 600; color: var(--text-light);">
                                            <i class="fas fa-warehouse"></i>
                                            <?= htmlspecialchars($d['depo_name']) ?>
                                        </div>
                                        <span class="badge success">Depot #<?= $d['depo_id'] ?></span>
                                    </div>
                                    <div style="color: var(--text-muted);">
                                        <strong>Coordinates:</strong> <?= $d['latitude'] ?>, <?= $d['longitude'] ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 10px;">
                            <p style="margin: 0; color: var(--text-light);">
                                <i class="fas fa-info-circle"></i>
                                <strong>Tip:</strong> To assign depots to stations, go to the "Edit Station Details" tab and click the "Depots" button next to any station.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');

            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            url.searchParams.delete('edit_station');
            url.searchParams.delete('manage_depot');
            url.searchParams.delete('search');
            window.history.replaceState({}, '', url);
        }

        function switchTabByName(tabName) {
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            const targetContent = document.getElementById(tabName);
            if (targetContent) {
                targetContent.classList.add('active');
            }
            
            const targetTab = document.querySelector(`button[onclick="switchTab('${tabName}')"]`);
            if (targetTab) {
                targetTab.classList.add('active');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('manage_depot')) {
                switchTabByName('manage-depots');
            } else if (urlParams.has('tab')) {
                switchTabByName(urlParams.get('tab'));
            } else if (urlParams.has('edit_station')) {
                switchTabByName('edit-stations');
            }

            const latInputs = document.querySelectorAll('input[name="latitude"]');
            const lngInputs = document.querySelectorAll('input[name="longitude"]');

            latInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    const value = parseFloat(e.target.value);
                    if (value < -90 || value > 90) {
                        e.target.setCustomValidity('Latitude must be between -90 and 90');
                    } else {
                        e.target.setCustomValidity('');
                    }
                });
            });

            lngInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    const value = parseFloat(e.target.value);
                    if (value < -180 || value > 180) {
                        e.target.setCustomValidity('Longitude must be between -180 and 180');
                    } else {
                        e.target.setCustomValidity('');
                    }
                });
            });

            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                document.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                        e.preventDefault();
                        searchInput.focus();
                        searchInput.select();
                    }
                });

                const editStationsTab = document.querySelector('button[onclick="switchTab(\'edit-stations\')"]');
                if (editStationsTab) {
                    editStationsTab.addEventListener('click', function() {
                        setTimeout(() => {
                            if (searchInput && document.getElementById('edit-stations').classList.contains('active')) {
                                searchInput.focus();
                            }
                        }, 100);
                    });
                }

                let searchTimeout;
                searchInput.addEventListener('input', function(e) {
                    clearTimeout(searchTimeout);
                    const query = e.target.value.trim();
                    
                    if (query.length > 0) {
                        e.target.style.borderColor = 'var(--primary-color)';
                        e.target.style.boxShadow = '0 0 10px rgba(220, 38, 38, 0.3)';
                    } else {
                        e.target.style.borderColor = 'rgba(220, 38, 38, 0.2)';
                        e.target.style.boxShadow = 'none';
                    }
                });
            }
        });

        const message = document.querySelector('.message');
        if (message) {
            setTimeout(() => {
                message.style.opacity = '0';
                setTimeout(() => {
                    message.remove();
                }, 300);
            }, 5000);
        }

        const numberInputs = document.querySelectorAll('input[type="number"]');
        numberInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        });

        const deleteButtons = document.querySelectorAll('form[onsubmit*="confirm"]');
        deleteButtons.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const stationCard = this.closest('.station-card');
                if (stationCard) {
                    const stationName = stationCard.querySelector('.station-name').textContent.trim();
                    const accountNo = this.querySelector('input[name="acct_no"]').value;
                    
                    const confirmMessage = `Are you sure you want to delete the following station?

Station: ${stationName}
Account: ${accountNo}

This action cannot be undone.`;
                    
                    if (confirm(confirmMessage)) {
                        this.submit();
                    }
                }
            });
        });

        // Station Autocomplete for Fuel Stock Management
        class StationAutocomplete {
            constructor(inputId, suggestionsId, hiddenInputId) {
                this.input = document.getElementById(inputId);
                this.suggestionsContainer = document.getElementById(suggestionsId);
                this.hiddenInput = document.getElementById(hiddenInputId);
                this.stations = <?= json_encode(array_values($stations)) ?>;
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

                const filtered = this.stations.filter(station => {
                    const searchText = `${station.name} ${station.district} ${station.brand} ${station.acct_no}`.toLowerCase();
                    return searchText.includes(value);
                });

                this.displaySuggestions(filtered, value);
            }

            showAllStations() {
                this.displaySuggestions(this.stations, '');
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
                            <div class="station-name-autocomplete">${highlightedText}</div>
                            <div class="station-details-autocomplete">
                                <i class="fas fa-hashtag" style="color: #dc2626; font-size: 0.8rem;"></i>
                                ${station.acct_no}
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
                this.hiddenInput.value = station.acct_no;
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

            clear() {
                this.input.value = '';
                this.hiddenInput.value = '';
                this.selectedStation = null;
                this.closeSuggestions();
            }
        }

        // Initialize station autocomplete for fuel stock management
        if (document.getElementById('fuelStationSearch')) {
            const fuelStationAutocomplete = new StationAutocomplete(
                'fuelStationSearch',
                'fuelStationSuggestions',
                'selectedStationAcctNo'
            );
        }
    </script>
</body>
</html>