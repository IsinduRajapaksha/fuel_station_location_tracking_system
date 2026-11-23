<?php
require_once("db_connect.php");
header('Content-Type: application/json');

try {
    // Fetch fuel stations
    $stationsQuery = "
        SELECT 
            acct_no as id, 
            name, 
            district, 
            brand, 
            latitude, 
            longitude, 
            status,
            'station' as type
        FROM fuel_stations
        WHERE latitude IS NOT NULL 
        AND longitude IS NOT NULL 
        AND latitude != 0 
        AND longitude != 0
        ORDER BY name
    ";

    $stationsResult = $conn->query($stationsQuery);
    
    if (!$stationsResult) {
        throw new Exception("Error fetching stations: " . $conn->error);
    }
    
    $stations = [];
    
    while ($station = $stationsResult->fetch_assoc()) {
        // Convert lat/lon to float for Leaflet compatibility
        $station['latitude'] = (float)$station['latitude'];
        $station['longitude'] = (float)$station['longitude'];
        
        $stations[] = $station;
    }

    // Fetch depos
    $deposQuery = "
        SELECT 
            depo_id,
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
        // Convert lat/lon to float for Leaflet compatibility
        $depo['latitude'] = (float)$depo['latitude'];
        $depo['longitude'] = (float)$depo['longitude'];
        // Add brand as 'Depo' for consistency
        $depo['brand'] = 'Storage Depo';
        
        $depos[] = $depo;
    }
    
    // Return both datasets
    echo json_encode([
        'stations' => $stations,
        'depos' => $depos
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>