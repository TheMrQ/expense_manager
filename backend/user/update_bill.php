<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

$bill_id = filter_input(INPUT_POST, 'bill_id', FILTER_VALIDATE_INT);
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$due_date = filter_input(INPUT_POST, 'due_date', FILTER_VALIDATE_INT);
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

if (!$bill_id || !$name || !$amount || !$due_date || !$category_id) {
    exit(json_encode(['success' => false, 'error' => 'All fields are required.']));
}

try {
    $stmt = $conn->prepare("UPDATE bills SET name = ?, amount = ?, due_date = ?, category_id = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sdiiii", $name, $amount, $due_date, $category_id, $bill_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Bill updated successfully.']);
    } else {
        throw new Exception('Failed to update bill or no changes were made.');
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}