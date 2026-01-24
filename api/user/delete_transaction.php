<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Not authenticated.']));
}
header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$transaction_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$transaction_id, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Transaction deleted.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
