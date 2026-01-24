<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

$bill_id = filter_input(INPUT_POST, 'bill_id', FILTER_VALIDATE_INT);
$name = $_POST['name'] ?? '';
$amount = (float)($_POST['amount'] ?? 0);
$due_date = (int)($_POST['due_date'] ?? 0);
$category_id = (int)($_POST['category_id'] ?? 0);

try {
    $stmt = $conn->prepare("UPDATE bills SET name = ?, amount = ?, due_date = ?, category_id = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$name, $amount, $due_date, $category_id, $bill_id, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Bill updated.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Update failed.']);
}
