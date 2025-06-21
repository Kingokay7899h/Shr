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
$current_date = date('Y-m-d');
$upcoming_date = date('Y-m-d', strtotime('+30 days'));

$sql = "SELECT sr_no, name_of_the_item, item_specification, last_maintenance, maintenance_due, service_provider
        FROM register 
        WHERE lab_id = ? 
        AND (disposal_status IS NULL OR disposal_status NOT LIKE 'Disposed')
        AND maintenance_due IS NOT NULL 
        AND (maintenance_due <= ? OR maintenance_due <= ?)
        ORDER BY maintenance_due ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $lab_id, $current_date, $upcoming_date);
$stmt->execute();
$result = $stmt->get_result();

$alerts = [];
while ($row = $result->fetch_assoc()) {
    $status = (strtotime($row['maintenance_due']) <= strtotime($current_date)) ? 'Overdue' : 'Upcoming';
    $alerts[] = [
        'sr_no' => $row['sr_no'],
        'name_of_the_item' => $row['name_of_the_item'],
        'item_specification' => $row['item_specification'],
        'last_maintenance' => $row['last_maintenance'],
        'maintenance_due' => $row['maintenance_due'],
        'service_provider' => $row['service_provider'],
        'status' => $status
    ];
}

echo json_encode($alerts);
$stmt->close();
$conn->close();
?>