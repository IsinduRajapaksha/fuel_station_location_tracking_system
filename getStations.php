<?php
require_once("db_connect.php");
header('Content-Type: application/json');

try {
    // =====================
    // Fetch Stations
    // =====================
    $stationsQuery = "
        SELECT 
            fs.acct_no as id, 
            fs.name, 
            fs.district, 
            fs.brand, 
            fs.latitude, 
            fs.longitude, 
            fs.address, 
            fs.contact_phone, 
            fs.status,
            d.depo_name AS depo,
            'station' as type
        FROM fuel_stations fs
        LEFT JOIN depo_by_station ds ON fs.acct_no = ds.acct_no
        LEFT JOIN depo d ON ds.depo_id = d.depo_id
        WHERE fs.latitude IS NOT NULL 
          AND fs.longitude IS NOT NULL 
          AND fs.latitude != 0 
          AND fs.longitude != 0
        ORDER BY fs.name
    ";

    $stationsResult = $conn->query($stationsQuery);
    if (!$stationsResult) {
        throw new Exception("Error fetching stations: " . $conn->error);
    }

    $stations = [];
    while ($station = $stationsResult->fetch_assoc()) {
        // Get fuel availability for each station
        $fuelQuery = "
            SELECT fuel_type, year, load_liters, available_liters 
            FROM fuel_quantity 
            WHERE station_acct_no = ? 
            ORDER BY year, fuel_type
        ";
        $fuelStmt = $conn->prepare($fuelQuery);
        $fuelStmt->bind_param("i", $station['id']);
        $fuelStmt->execute();
        $fuelResult = $fuelStmt->get_result();

        $fuelAvailability = [];
        $fuelTypes = [];

        while ($fuel = $fuelResult->fetch_assoc()) {
            $year = $fuel['year'];
            $type = $fuel['fuel_type'];
            $available = $fuel['available_liters'] > 0;

            if (!isset($fuelAvailability[$year])) {
                $fuelAvailability[$year] = [];
            }

            $fuelAvailability[$year][$type] = [
                'available' => $available,
                'stock' => (int)$fuel['available_liters'],
                'loaded' => (int)$fuel['load_liters'],
                'price' => getFuelPrice($type)
            ];

            if ($available) {
                $displayName = ucfirst(str_replace('_', ' ', $type));
                if (!in_array($displayName, $fuelTypes)) {
                    $fuelTypes[] = $displayName;
                }
            }
        }

        $station['fuel_availability'] = $fuelAvailability;
        $station['fuel_types'] = $fuelTypes; // keep as array for easier filtering
        $station['latitude'] = (float)$station['latitude'];
        $station['longitude'] = (float)$station['longitude'];

        $stations[] = $station;
        $fuelStmt->close();
    }

    // =====================
    // Fetch Depos
    // =====================
    $deposQuery = "
        SELECT 
            depo_id as id,
            depo_name as name,
            latitude,
            longitude,
            'depo' as type
        FROM depo 
        WHERE latitude IS NOT NULL 
          AND longitude IS NOT NULL 
          AND latitude != 0 
          AND longitude != 0
        ORDER BY depo_name
    ";
    $deposResult = $conn->query($deposQuery);
    if (!$deposResult) {
        throw new Exception("Error fetching depos: " . $conn->error);
    }

    $depos = [];
    while ($depo = $deposResult->fetch_assoc()) {
        $depo['latitude'] = (float)$depo['latitude'];
        $depo['longitude'] = (float)$depo['longitude'];
        $depos[] = $depo;
    }

    // =====================
    // Return combined JSON
    // =====================
    echo json_encode([
        'stations' => $stations,
        'depos' => $depos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getFuelPrice($fuelType) {
    $prices = [
        'petrol' => 365,
        'diesel' => 320,
        'super_diesel' => 350,
        'octane_95' => 395
    ];
    return $prices[$fuelType] ?? 300;
}

$conn->close();
?>
