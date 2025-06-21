<?php
session_start();
$conn = new mysqli("localhost", "root", "", "asset_management");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'principal') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$requestId = $data['requestId'] ?? 0;
$action = $data['action'] ?? '';
$rejection_reason = $data['rejection_reason'] ?? '';

if ($requestId === 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

$status = $action === 'approve' ? 'Approved by Committee' : 'Rejected by Committee';
$approved_by = $_SESSION['name'] ?? 'Principal';
$sql = "UPDATE disposal_forms SET status = ?, approved_by = ?, rejection_reason = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $status, $approved_by, $rejection_reason, $requestId);
if ($stmt->execute()) {
    $sql = "UPDATE register SET disposal_status = ? WHERE sr_no = (SELECT item_id FROM disposal_forms WHERE id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $requestId);
    $stmt->execute();
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Update failed"]);
}

$stmt->close();
$conn->close();
?>