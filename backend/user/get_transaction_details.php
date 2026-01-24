<?php
require __DIR__ . '/../connection/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit();
}

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];
$transaction_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$transaction_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid Transaction ID.']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            t.id, 
            t.amount, 
            t.note, 
            t.date, 
            t.currency, 
            t.category_id,
            c.name as category_name,
            c.type as category_type
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($transaction = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $transaction]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Transaction not found or you do not have permission to view it.']);
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in get_transaction_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
