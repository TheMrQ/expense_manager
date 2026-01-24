<?php

/**
 * get_recent_transactions.php
 * Fetches a specified number of recent transactions for the user.
 * Expects GET parameter: ?limit=5
 */

require __DIR__ . '/../connection/config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit();
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

// Get the 'limit' parameter from the request, default to 5
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, [
    'options' => ['default' => 5, 'min_range' => 1]
]);

try {
    // PDO uses a different approach for LIMIT with parameters. 
    // We bind the values directly in execute() for simplicity.
    $stmt = $conn->prepare("
        SELECT t.id, c.name AS category_name, c.type AS category_type, t.amount, t.note, t.date, t.currency
        FROM transactions t JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.date DESC, t.id DESC
        LIMIT ?
    ");

    // In PDO, if you want to use a variable for LIMIT, you must often bind it as an integer 
    // or ensure the emulation is handled. Using the array in execute() works best here.
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $transactions = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $transactions]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("get_recent_transactions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
