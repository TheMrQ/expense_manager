<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
$bill_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
header('Content-Type: application/json');

if (!$bill_id) {
    exit(json_encode(['success' => false, 'error' => 'Invalid ID']));
}

$stmt = $conn->prepare("SELECT id, name, amount, due_date, category_id FROM bills WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $bill_id, $user_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($bill) {
    echo json_encode(['success' => true, 'data' => $bill]);
} else {
    echo json_encode(['success' => false, 'error' => 'Bill not found.']);
}
