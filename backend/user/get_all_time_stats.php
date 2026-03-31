<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    // Helper function to get the top transaction of a certain type
    function get_top_transaction($conn, $user_id, $type)
    {
        $stmt = $conn->prepare("
            SELECT t.amount, t.date, c.name as category_name, t.currency
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND c.type = ?
            ORDER BY t.amount DESC LIMIT 1
        ");
        $stmt->bind_param("is", $user_id, $type);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    $top_income = get_top_transaction($conn, $user_id, 'income');
    $top_expense = get_top_transaction($conn, $user_id, 'expense');

    echo json_encode(['success' => true, 'data' => ['income' => $top_income, 'expense' => $top_expense]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}