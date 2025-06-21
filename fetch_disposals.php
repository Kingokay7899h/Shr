<?php
session_start();
$conn = new mysqli("localhost", "root", "", "asset_management");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['role']) || !isset($_SESSION['lab_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$role = $_SESSION['role'];
$lab_id = $_SESSION['lab_id'];
$type = $_GET['type'] ?? 'pending';
$disposals = [];

if ($type === 'rejected') {
    if (in_array($role, ['lab assistant', 'lab faculty incharge', 'HOD'])) {
        $sql = "SELECT df.id, df.item_id, r.name_of_the_item, r.item_specification, df.status, 
                       df.condemnation_reason, df.created_at, r.last_maintenance, r.maintenance_due
                FROM disposal_forms df
                JOIN register r ON df.item_id = r.sr_no
                WHERE r.lab_id = ? AND df.status LIKE 'Rejected%'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $lab_id);
    } elseif ($role === 'store') {
        $sql = "SELECT df.id, df.item_id, r.name_of_the_item, r.item_specification, df.status, 
                       df.condemnation_reason, df.created_at, r.last_maintenance, r.maintenance_due
                FROM disposal_forms df
                JOIN register r ON df.item_id = r.sr_no
                WHERE df.status LIKE 'Rejected by Stores'";
        $stmt = $conn->prepare($sql);
    } elseif ($role === 'principal') {
        $sql = "SELECT df.id, df.item_id, r.name_of_the_item, r.item_specification, df.status, 
                       df.condemnation_reason, df.created_at, r.last_maintenance, r.maintenance_due
                FROM disposal_forms df
                JOIN register r ON df.item_id = r.sr_no
                WHERE df.status LIKE 'Rejected by Committee'";
        $stmt = $conn->prepare($sql);
    }
} elseif ($type === 'past') {
    if (in_array($role, ['lab assistant', 'lab faculty incharge', 'HOD'])) {
        $sql = "SELECT pd.id, pd.item_id, pd.item_name, r.item_specification, pd.status, 
                       pd.reason AS condemnation_reason, pd.created_at, r.last_maintenance, r.maintenance_due
                FROM past_disposals pd
                JOIN register r ON pd.item_id = r.sr_no
                WHERE pd.lab_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $lab_id);
    } elseif (in_array($role, ['store', 'principal'])) {
        $sql = "SELECT pd.id, pd.item_id, pd.item_name, r.item_specification, pd.status, 
                       pd.reason AS condemnation_reason, pd.created_at, r.last_maintenance, r.maintenance_due
                FROM past_disposals pd
                JOIN register r ON pd.item_id = r.sr_no";
        $stmt = $conn->prepare($sql);
    }
} else {
    if (in_array($role, ['lab assistant', 'lab faculty incharge', 'HOD'])) {
        $sql = "SELECT df.id, df.item_id, r.name_of_the_item, r.item_specification, df.status, 
                       df.condemnation_reason, df.created_at, r.last_maintenance, r.maintenance_due
                FROM disposal_forms df
                JOIN register r ON df.item_id = r.sr_no
                WHERE r.lab_id = ? AND df.status NOT LIKE 'Rejected%' AND df.status != 'Disposed'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $lab_id);
    } elseif ($role === 'store') {
        $sql = "SELECT df.id, df.item_id, r.name_of_the_item, r.item_specification, df.status, 
                       df.condemnation_reason, df.created_at, r.last_maintenance, r.maintenance_due
                FROM disposal_forms df
                JOIN register r ON df.item_id = r.sr_no
                WHERE df.status IN ('Pending Stores', 'Pending Committee', 'Approved by Committee')";
        $stmt = $conn->prepare($sql);
    } elseif ($role === 'principal') {
        $sql = "SELECT df.id, df.item_id, r.name_of_the_item, r.item_specification, df.status, 
                       df.condemnation_reason, df.created_at, r.last_maintenance, r.maintenance_due
                FROM disposal_forms df
                JOIN register r ON df.item_id = r.sr_no
                WHERE df.status IN ('Pending Committee', 'Approved by Committee')";
        $stmt = $conn->prepare($sql);
    }
}

if (!isset($stmt)) {
    echo json_encode(["status" => "error", "message" => "Invalid role or query"]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $disposals[] = [
        'id' => $row['id'],
        'item_id' => $row['item_id'],
        'name_of_the_item' => $row['name_of_the_item'] ?? $row['item_name'],
        'item_specification' => $row['item_specification'],
        'status' => $row['status'],
        'condemnation_reason' => $row['condemnation_reason'] ?? $row['reason'],
        'created_at' => $row['created_at'],
        'last_maintenance' => $row['last_maintenance'],
        'maintenance_due' => $row['maintenance_due']
    ];
}

echo json_encode($disposals);
$stmt->close();
$conn->close();
?>