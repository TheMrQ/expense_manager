<?php
require __DIR__ . '/../connection/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$transaction_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$transaction_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid Transaction ID provided.']);
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Transaction not found or you do not have permission to delete it.']);
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in delete_transaction.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred during deletion.']);
}
