<?php
session_start();
$conn = new mysqli("localhost", "root", "", "asset_management");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['lab_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$sr_nos = $data['sr_nos'] ?? [];

if (empty($sr_nos)) {
    echo json_encode(["status" => "error", "message" => "No items provided"]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($sr_nos), '?'));
$sql = "SELECT sr_no, name_of_the_item, disposal_status 
        FROM register 
        WHERE sr_no IN ($placeholders) AND lab_id = ? AND disposal_status IS NOT NULL";
$stmt = $conn->prepare($sql);
$types = str_repeat('i', count($sr_nos)) . 's';
$bind_params = array_merge($sr_nos, [$_SESSION['lab_id']]);
$stmt->bind_param($types, ...$bind_params);
$stmt->execute();
$result = $stmt->get_result();

$invalid_items = [];
while ($row = $result->fetch_assoc()) {
    $invalid_items[] = [
        'sr_no' => $row['sr_no'],
        'name' => $row['name_of_the_item'],
        'status' => $row['disposal_status']
    ];
}

if (!empty($invalid_items)) {
    echo json_encode(["status" => "error", "invalid_items" => $invalid_items]);
} else {
    echo json_encode(["status" => "success"]);
}

$stmt->close();
$conn->close();
?>