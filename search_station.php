<?php
require_once("db_connect.php");
header('Content-Type: application/json');

$term = $_GET['term'] ?? '';

$sql = "SELECT acct_no, name AS name, district 
        FROM fuel_stations 
        WHERE name LIKE ? 
        LIMIT 4";

$stmt = $conn->prepare($sql);
$search = "%".$term."%";
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$stations = [];
while ($row = $result->fetch_assoc()) {
    $stations[] = $row;
}
echo json_encode($stations);
