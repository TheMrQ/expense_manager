<?php
require __DIR__ . '/../connection/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("
        SELECT
            t.currency,
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as total_income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as total_expense
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        GROUP BY t.currency
    ");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll();

    $balances_data = [];
    foreach ($results as $row) {
        $row['balance'] = $row['total_income'] - $row['total_expense'];
        $balances_data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $balances_data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
