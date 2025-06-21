<?php
session_start();
$conn = new mysqli("localhost", "root", "", "asset_management");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['lab assistant', 'lab faculty incharge'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$sr_no = $data['sr_no'] ?? 0;
$last_maintenance = $data['last_maintenance'] ?? null;
$maintenance_due = $data['maintenance_due'] ?? null;
$service_provider = $data['service_provider'] ?? null;

if ($sr_no === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid item ID"]);
    exit;
}

$sql = "UPDATE register SET last_maintenance = ?, maintenance_due = ?, service_provider = ? WHERE sr_no = ? AND lab_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssis", $last_maintenance, $maintenance_due, $service_provider, $sr_no, $_SESSION['lab_id']);
if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Update failed"]);
}

$stmt->close();
$conn->close();
?>