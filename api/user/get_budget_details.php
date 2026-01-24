<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}
$user_id = $_SESSION['user_id'];
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$month = date('Y-m');
header('Content-Type: application/json');

if (!$category_id) {
    exit(json_encode(['success' => false, 'error' => 'Invalid ID']));
}

$stmt = $conn->prepare("SELECT category_id, budget_amount FROM budgets WHERE category_id = ? AND user_id = ? AND month = ?");
$stmt->bind_param("iis", $category_id, $user_id, $month);
$stmt->execute();
$budget = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($budget) {
    echo json_encode(['success' => true, 'data' => $budget]);
} else {
    echo json_encode(['success' => false, 'error' => 'Budget not found.']);
}
