<?php
session_start();
$conn = new mysqli("localhost", "root", "", "asset_management");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['lab_id']) || !isset($_SESSION['role'])) {
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
$sql = "SELECT sr_no, name_of_the_item, price, date, item_specification 
        FROM register 
        WHERE sr_no IN ($placeholders) AND lab_id = ? AND disposal_status IS NULL";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Query preparation failed"]);
    exit;
}

$types = str_repeat('i', count($sr_nos)) . 's';
$bind_params = array_merge($sr_nos, [$_SESSION['lab_id']]);
$stmt->bind_param($types, ...$bind_params);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'sr_no' => $row['sr_no'],
        'name_of_the_item' => $row['name_of_the_item'],
        'price' => $row['price'],
        'date' => $row['date'],
        'item_specification' => $row['item_specification']
    ];
}
echo json_encode($items);
$stmt->close();
$conn->close();
?>