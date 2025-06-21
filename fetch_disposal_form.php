<?php
session_start();
$conn = new mysqli("localhost", "root", "", "asset_management");
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['role'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$id = $_GET['id'] ?? 0;
$sql = "SELECT df.*, r.name_of_the_item, r.item_specification
        FROM disposal_forms df
        JOIN register r ON df.item_id = r.sr_no
        WHERE df.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$data = ['items' => []];
while ($row = $result->fetch_assoc()) {
    $data['items'][] = [
        'item_id' => $row['item_id'],
        'sr_no' => $row['id'],
        'description' => $row['name_of_the_item'] . ' (' . $row['item_specification'] . ')',
        'weight' => $row['weight'],
        'book_value' => $row['book_value'],
        'purchase_date' => $row['purchase_date'],
        'unserviceable_date' => $row['unserviceable_date'],
        'period_of_use' => $row['period_of_use'],
        'current_condition' => $row['current_condition'],
        'repair_efforts' => $row['repair_efforts'],
        'location' => $row['location'],
        'condemnation_reason' => $row['condemnation_reason'],
        'remarks' => $row['remarks']
    ];
    $data['status'] = $row['status'];
    $data['prepared_by'] = ['name' => $row['submitted_by'], 'designation' => ''];
    $data['reviewed_by'] = ['name' => '', 'designation' => ''];
    $data['committee_members'] = [
        ['name' => 'Dr. Krupashankara M.S', 'designation' => 'Principal'],
        ['name' => '', 'designation' => ''],
        ['name' => 'Shri. Sunil Rauf', 'designation' => ''],
        ['name' => '', 'designation' => ''],
        ['name' => '', 'designation' => '']
    ];
}

if (empty($data['items'])) {
    echo json_encode(['error' => 'Form not found']);
} else {
    echo json_encode($data);
}
$stmt->close();
$conn->close();
?>