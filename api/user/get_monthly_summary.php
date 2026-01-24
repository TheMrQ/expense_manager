<?php
require __DIR__ . '/../connection/config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            TO_CHAR(t.date, 'YYYY-MM') as month,
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as total_income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as total_expense
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.date >= CURRENT_DATE - INTERVAL '6 months'
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll();

    $labels = [];
    $income_data = [];
    $expense_data = [];
    $net_data = [];

    foreach ($results as $row) {
        $labels[] = date("M Y", strtotime($row['month'] . "-01"));
        $income_data[] = (float)$row['total_income'];
        $expense_data[] = (float)$row['total_expense'];
        $net_data[] = (float)$row['total_income'] - (float)$row['total_expense'];
    }

    echo json_encode(['success' => true, 'data' => [
        'labels' => $labels,
        'income' => $income_data,
        'expenses' => $expense_data,
        'net' => $net_data
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
