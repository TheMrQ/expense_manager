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
            DATE_FORMAT(t.date, '%Y-%m') as month,
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as total_income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as total_expense
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $income_data = [];
    $expense_data = [];
    $net_data = []; // New array for net income

    while ($row = $result->fetch_assoc()) {
        $labels[] = date("M Y", strtotime($row['month'] . "-01"));
        $income_data[] = $row['total_income'];
        $expense_data[] = $row['total_expense'];
        // Calculate and add net income
        $net_data[] = $row['total_income'] - $row['total_expense'];
    }
    $stmt->close();

    $response = [
        'labels' => $labels,
        'income' => $income_data,
        'expenses' => $expense_data,
        'net' => $net_data // Include net income in the response
    ];

    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}