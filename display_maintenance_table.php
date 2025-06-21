<?php
session_start();
$conn = new mysqli("localhost", "root", "", "asset_management");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['lab_id']) || !isset($_SESSION['role'])) {
    echo json_encode([]);
    exit;
}

$lab_id = $_SESSION['lab_id'];
$sql = "SELECT sr_no, name_of_the_item, item_specification, date AS procurement_date, 
               price AS cost, last_maintenance, maintenance_due, service_provider, 
               disposal_status
        FROM register 
        WHERE lab_id = ? AND (disposal_status IS NULL OR disposal_status NOT LIKE 'Disposed')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $lab_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'sr_no' => $row['sr_no'],
        'name_of_the_item' => $row['name_of_the_item'],
        'item_specification' => $row['item_specification'],
        'procurement_date' => $row['procurement_date'],
        'cost' => $row['cost'],
        'last_maintenance' => $row['last_maintenance'],
        'maintenance_due' => $row['maintenance_due'],
        'service_provider' => $row['service_provider'],
        'disposal_status' => $row['disposal_status']
    ];
}

echo json_encode($data);
$stmt->close();
$conn->close();
?>