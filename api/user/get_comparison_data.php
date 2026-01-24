<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    // PostgreSQL query using date truncation for monthly comparisons
    $sql = "
        SELECT
            SUM(CASE WHEN date >= DATE_TRUNC('month', CURRENT_DATE) THEN amount ELSE 0 END) as current_month_total,
            SUM(CASE WHEN date >= DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month') 
                     AND date < DATE_TRUNC('month', CURRENT_DATE) THEN amount ELSE 0 END) as last_month_total
        FROM transactions
        WHERE user_id = ? AND category_id IN (SELECT id FROM categories WHERE type = ?)
    ";
    $stmt = $conn->prepare($sql);

    $stmt->execute([$user_id, 'expense']);
    $expenses = $stmt->fetch();

    $stmt->execute([$user_id, 'income']);
    $income = $stmt->fetch();

    function calculate_change($current, $previous)
    {
        if ($previous == 0) return ($current > 0) ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    $current_savings = $income['current_month_total'] - $expenses['current_month_total'];
    $last_savings = $income['last_month_total'] - $expenses['last_month_total'];

    echo json_encode(['success' => true, 'data' => [
        'expense_change' => calculate_change($expenses['current_month_total'], $expenses['last_month_total']),
        'income_change' => calculate_change($income['current_month_total'], $income['last_month_total']),
        'savings_change' => calculate_change($current_savings, $last_savings)
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
