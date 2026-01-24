<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
$cat_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
header('Content-Type: application/json');

$stmt = $conn->prepare("SELECT category_id, budget_amount FROM budgets WHERE category_id = ? AND user_id = ? AND month = ?");
$stmt->execute([$cat_id, $user_id, date('Y-m')]);
$budget = $stmt->fetch();

if ($budget) {
    echo json_encode(['success' => true, 'data' => $budget]);
} else {
    echo json_encode(['success' => false, 'error' => 'Not found.']);
}
