<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$bill_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $stmt = $conn->prepare("DELETE FROM bills WHERE id = ? AND user_id = ?");
    $stmt->execute([$bill_id, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Bill deleted.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
