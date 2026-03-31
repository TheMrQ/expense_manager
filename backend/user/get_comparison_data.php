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
            SUM(CASE WHEN date BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE()) THEN amount ELSE 0 END) as current_month_total,
            SUM(CASE WHEN date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH) THEN amount ELSE 0 END) as last_month_total
        FROM transactions
        WHERE user_id = ? AND category_id IN (SELECT id FROM categories WHERE type = ?)
    ");

    // Calculate for Expenses
    $type_expense = 'expense';
    $stmt->bind_param("is", $user_id, $type_expense);
    $stmt->execute();
    $expenses = $stmt->get_result()->fetch_assoc();

    // Calculate for Income
    $type_income = 'income';
    $stmt->bind_param("is", $user_id, $type_income);
    $stmt->execute();
    $income = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Helper function for percentage change
    function calculate_change($current, $previous)
    {
        if ($previous == 0) return ($current > 0) ? 100 : 0; // Avoid division by zero
        return (($current - $previous) / $previous) * 100;
    }

    $current_savings = $income['current_month_total'] - $expenses['current_month_total'];
    $last_savings = $income['last_month_total'] - $expenses['last_month_total'];

    $response = [
        'expense_change' => calculate_change($expenses['current_month_total'], $expenses['last_month_total']),
        'income_change' => calculate_change($income['current_month_total'], $income['last_month_total']),
        'savings_change' => calculate_change($current_savings, $last_savings)
    ];

    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}