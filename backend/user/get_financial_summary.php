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
    $current_month_start = date('Y-m-01');
    $current_month_end = date('Y-m-t');

    // --- 1. Get Income and Expense for the current month ---
    $stmt_monthly = $conn->prepare("
        SELECT
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as expense
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
    ");
    $stmt_monthly->bind_param("iss", $user_id, $current_month_start, $current_month_end);
    $stmt_monthly->execute();
    $monthly_totals = $stmt_monthly->get_result()->fetch_assoc();
    $stmt_monthly->close();

    // --- 2. Get the single largest expense this month ---
    $stmt_largest = $conn->prepare("
        SELECT amount, note FROM transactions
        WHERE user_id = ? AND date BETWEEN ? AND ? AND category_id IN (SELECT id FROM categories WHERE type = 'expense')
        ORDER BY amount DESC LIMIT 1
    ");
    $stmt_largest->bind_param("iss", $user_id, $current_month_start, $current_month_end);
    $stmt_largest->execute();
    $largest_expense = $stmt_largest->get_result()->fetch_assoc();
    $stmt_largest->close();

    // --- 3. Get the day with the highest spending this month ---
    $stmt_day = $conn->prepare("
        SELECT date, SUM(amount) as daily_total FROM transactions
        WHERE user_id = ? AND date BETWEEN ? AND ? AND category_id IN (SELECT id FROM categories WHERE type = 'expense')
        GROUP BY date ORDER BY daily_total DESC LIMIT 1
    ");
    $stmt_day->bind_param("iss", $user_id, $current_month_start, $current_month_end);
    $stmt_day->execute();
    $biggest_day = $stmt_day->get_result()->fetch_assoc();
    $stmt_day->close();

    // --- Calculate Savings Rate ---
    $savings_rate = 0;
    if ($monthly_totals['income'] > 0) {
        $savings = $monthly_totals['income'] - $monthly_totals['expense'];
        $savings_rate = ($savings / $monthly_totals['income']) * 100;
    }

    $response = [
        'savings_rate' => $savings_rate,
        'largest_expense' => $largest_expense, // ['amount' => X, 'note' => Y]
        'biggest_spending_day' => $biggest_day // ['date' => X, 'daily_total' => Y]
    ];

    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}