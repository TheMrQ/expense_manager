<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$cat_id = filter_var($data['category_id'] ?? null, FILTER_VALIDATE_INT);

try {
    $stmt = $conn->prepare("DELETE FROM budgets WHERE category_id = ? AND user_id = ? AND month = ?");
    $stmt->execute([$cat_id, $user_id, date('Y-m')]);
    echo json_encode(['success' => true, 'message' => 'Budget removed.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
