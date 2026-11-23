<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'fualStations';
    private $username = 'your_username';  // Change this
    private $password = 'your_password';  // Change this
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
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// classes/Station.php
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

        // Sanitize and bind data
        $stmt->bindParam(':acct_no', $data['acct_no']);
        $stmt->bindParam(':name', htmlspecialchars(strip_tags($data['name'])));
        $stmt->bindParam(':district', $data['district']);
        $stmt->bindParam(':brand', $data['brand']);
        $stmt->bindParam(':latitude', $data['latitude']);
        $stmt->bindParam(':longitude', $data['longitude']);
        $stmt->bindParam(':address', htmlspecialchars(strip_tags($data['address'])));
        $stmt->bindParam(':contact_phone', $data['contact_phone']);
        $stmt->bindParam(':status', $data['status']);

        try {
            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Station added successfully'];
            }
        } catch(PDOException $e) {
            if($e->getCode() == 23000) { // Duplicate entry
                return ['success' => false, 'message' => 'Station with this account number already exists'];
            }
            return ['success' => false, 'message' => 'Error adding station: ' . $e->getMessage()];
        }

        return ['success' => false, 'message' => 'Failed to add station'];
    }

    public function getAllStations(): mixed {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getStationByAcctNo($acct_no) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE acct_no = :acct_no";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':acct_no', $acct_no);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function updateStation($acct_no, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, district = :district, brand = :brand, 
                      latitude = :latitude, longitude = :longitude, 
                      address = :address, contact_phone = :contact_phone, 
                      status = :status 
                  WHERE acct_no = :acct_no";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':acct_no', $acct_no);
        $stmt->bindParam(':name', htmlspecialchars(strip_tags($data['name'])));
        $stmt->bindParam(':district', $data['district']);
        $stmt->bindParam(':brand', $data['brand']);
        $stmt->bindParam(':latitude', $data['latitude']);
        $stmt->bindParam(':longitude', $data['longitude']);
        $stmt->bindParam(':address', htmlspecialchars(strip_tags($data['address'])));
        $stmt->bindParam(':contact_phone', $data['contact_phone']);
        $stmt->bindParam(':status', $data['status']);

        if($stmt->execute()) {
            return ['success' => true, 'message' => 'Station updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update station'];
    }


public function deleteStation($acct_no){
    $query = "DELETE FROM " . $this->table_name . " WHERE acct_no = :acct_no";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':acct_no', $acct_no);

    if($stmt->execute()) {
        return ['success' => true, 'message' => 'Station deleted successfully'];
    }
    return ['success' => false, 'message' => 'Failed to delete station'];
}

}

// classes/FuelQuantity.php
class FuelQuantity {
    private $conn;
    private $table_name = "fuel_quantity";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addFuelStock($data) {
        // First check if record already exists
        $check_query = "SELECT id FROM " . $this->table_name . " 
                       WHERE station_acct_no = :station_acct_no 
                       AND fuel_type = :fuel_type 
                       AND year = :year";
        
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':station_acct_no', $data['station_acct_no']);
        $check_stmt->bindParam(':fuel_type', $data['fuel_type']);
        $check_stmt->bindParam(':year', $data['year']);
        $check_stmt->execute();

        if($check_stmt->fetch()) {
            return ['success' => false, 'message' => 'Fuel record for this station, type and year already exists. Use edit to update.'];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (station_acct_no, fuel_type, year, load_liters, available_liters) 
                  VALUES (:station_acct_no, :fuel_type, :year, :load_liters, :available_liters)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':station_acct_no', $data['station_acct_no']);
        $stmt->bindParam(':fuel_type', $data['fuel_type']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':load_liters', $data['load_liters']);
        $stmt->bindParam(':available_liters', $data['available_liters']);

        try {
            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Fuel stock added successfully'];
            }
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error adding fuel stock: ' . $e->getMessage()];
        }

        return ['success' => false, 'message' => 'Failed to add fuel stock'];
    }

    public function updateFuelStock($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET load_liters = :load_liters, available_liters = :available_liters 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':load_liters', $data['load_liters']);
        $stmt->bindParam(':available_liters', $data['available_liters']);

        if($stmt->execute()) {
            return ['success' => true, 'message' => 'Fuel stock updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update fuel stock'];
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

    public function getFuelRecordsByStation($station_acct_no) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE station_acct_no = :station_acct_no 
                  ORDER BY fuel_type ASC, year DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':station_acct_no', $station_acct_no);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deductFuel($station_acct_no, $fuel_type, $year, $quantity) {
        $query = "UPDATE " . $this->table_name . " 
                  SET available_liters = available_liters - :quantity 
                  WHERE station_acct_no = :station_acct_no 
                  AND fuel_type = :fuel_type 
                  AND year = :year 
                  AND available_liters >= :quantity";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':station_acct_no', $station_acct_no);
        $stmt->bindParam(':fuel_type', $fuel_type);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':quantity', $quantity);

        if($stmt->execute() && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Fuel quantity deducted successfully'];
        }
        return ['success' => false, 'message' => 'Insufficient fuel stock or invalid parameters'];
    }
}

// station_management.php (Main file)
session_start();
require_once 'config/database.php';
require_once 'classes/Station.php';
require_once 'classes/FuelQuantity.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed. Please check your configuration.");
}

// Initialize classes
$station = new Station($db);
$fuelQuantity = new FuelQuantity($db);

// Initialize variables
$message = '';
$messageType = 'success';
$stations = [];
$fuelRecords = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_station':
            // Validate required fields
            $required_fields = ['acct_no', 'name', 'district', 'brand', 'latitude', 'longitude'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                $message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
                $messageType = 'error';
            } else {
                // Validate account number (6 digits)
                if (!preg_match('/^\d{6}$/', $_POST['acct_no'])) {
                    $message = 'Account number must be exactly 6 digits';
                    $messageType = 'error';
                } else {
                    // Validate coordinates
                    $lat = floatval($_POST['latitude']);
                    $lng = floatval($_POST['longitude']);
                    
                    if ($lat < -90 || $lat > 90) {
                        $message = 'Latitude must be between -90 and 90';
                        $messageType = 'error';
                    } elseif ($lng < -180 || $lng > 180) {
                        $message = 'Longitude must be between -180 and 180';
                        $messageType = 'error';
                    } else {
                        $station_data = [
                            'acct_no' => intval($_POST['acct_no']),
                            'name' => $_POST['name'],
                            'district' => $_POST['district'],
                            'brand' => $_POST['brand'],
                            'latitude' => $lat,
                            'longitude' => $lng,
                            'address' => $_POST['address'] ?? '',
                            'contact_phone' => $_POST['contact_phone'] ?? '',
                            'status' => $_POST['status'] ?? 'Open'
                        ];
                        
                        $result = $station->addStation($station_data);
                        $message = $result['message'];
                        $messageType = $result['success'] ? 'success' : 'error';
                    }
                }
            }
            break;

        case 'add_fuel_stock':
            $required_fields = ['station_acct_no', 'fuel_type', 'year'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                $message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
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
            if (empty($_POST['fuel_id']) || empty($_POST['load_liters']) || empty($_POST['available_liters'])) {
                $message = 'Please fill in all required fields for update';
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
    }
}

// Fetch data for display
try {
    $stations = $station->getAllStations();
    $fuelRecords = $fuelQuantity->getAllFuelRecords();
} catch (Exception $e) {
    $message = 'Error fetching data: ' . $e->getMessage();
    $messageType = 'error';
}
?>