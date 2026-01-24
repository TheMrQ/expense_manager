<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');

    $stmt_monthly = $conn->prepare("SELECT SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as income, SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as expense FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.date BETWEEN ? AND ?");
    $stmt_monthly->execute([$user_id, $month_start, $month_end]);
    $totals = $stmt_monthly->fetch();

    $stmt_largest = $conn->prepare("SELECT amount, note FROM transactions WHERE user_id = ? AND date BETWEEN ? AND ? AND category_id IN (SELECT id FROM categories WHERE type = 'expense') ORDER BY amount DESC LIMIT 1");
    $stmt_largest->execute([$user_id, $month_start, $month_end]);
    $largest = $stmt_largest->fetch();

    $stmt_day = $conn->prepare("SELECT date, SUM(amount) as daily_total FROM transactions WHERE user_id = ? AND date BETWEEN ? AND ? AND category_id IN (SELECT id FROM categories WHERE type = 'expense') GROUP BY date ORDER BY daily_total DESC LIMIT 1");
    $stmt_day->execute([$user_id, $month_start, $month_end]);
    $day = $stmt_day->fetch();

    $savings_rate = ($totals['income'] > 0) ? (($totals['income'] - $totals['expense']) / $totals['income']) * 100 : 0;

    echo json_encode(['success' => true, 'data' => ['savings_rate' => (float)$savings_rate, 'largest_expense' => $largest, 'biggest_spending_day' => $day]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
