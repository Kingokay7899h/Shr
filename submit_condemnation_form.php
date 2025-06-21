<?php
session_start();
$conn = new mysqli("localhost", "root", "", "asset_management");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['lab assistant', 'lab faculty incharge', 'HOD', 'store', 'principal'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$requestId = $data['requestId'] ?? '';
$items = $data['items'] ?? [];
$status = $data['status'] ?? 'Pending Stores';
$submitted_by = $_SESSION['name'] ?? 'Unknown';

if (empty($items)) {
    echo json_encode(["status" => "error", "message" => "No items provided"]);
    exit;
}

$conn->begin_transaction();
try {
    foreach ($items as $item) {
        $item_id = $item['item_id'] ?? 0;
        $sql = "INSERT INTO disposal_forms (
                    item_id, weight, book_value, purchase_date, unserviceable_date, 
                    period_of_use, current_condition, repair_efforts, location, 
                    condemnation_reason, remarks, status, submitted_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issssssssssss",
            $item_id,
            $item['weight'],
            $item['book_value'],
            $item['purchase_date'],
            $item['unserviceable_date'],
            $item['period_of_use'],
            $item['current_condition'],
            $item['repair_efforts'],
            $item['location'],
            $item['condemnation_reason'],
            $item['remarks'],
            $status,
            $submitted_by
        );
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert disposal form");
        }

        $sql = "UPDATE register SET disposal_status = ?, reason_for_disposal = ? WHERE sr_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $status, $item['condemnation_reason'], $item_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update register");
        }
    }

    $conn->commit();
    echo json_encode(["status" => "success", "requestId" => $requestId]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>