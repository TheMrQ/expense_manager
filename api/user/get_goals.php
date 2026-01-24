<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT id, name, target_amount, saved_amount FROM goals WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $goals]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
