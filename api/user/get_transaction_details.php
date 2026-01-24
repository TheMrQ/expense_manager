<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'User not authenticated.']));
}
header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];
$transaction_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$transaction_id) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid Transaction ID.']));
}
try {
    $stmt = $conn->prepare("
        SELECT t.id, t.amount, t.note, t.date, t.currency, t.category_id, c.name as category_name, c.type as category_type
        FROM transactions t JOIN categories c ON t.category_id = c.id
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$transaction_id, $user_id]);
    $transaction = $stmt->fetch();
    if ($transaction) {
        echo json_encode(['success' => true, 'data' => $transaction]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Transaction not found.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
