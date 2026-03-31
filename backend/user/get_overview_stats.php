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
    // --- 1. Get Total Balance (assuming USD is the main currency for this summary) ---
    $stmt_balance = $conn->prepare("
        SELECT COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE -t.amount END), 0) as current_balance
        FROM transactions t JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.currency = 'USD'
    ");
    $stmt_balance->bind_param("i", $user_id);
    $stmt_balance->execute();
    $balance_result = $stmt_balance->get_result()->fetch_assoc();
    $stmt_balance->close();

    // --- 2. Get Income and Expense for the current month ---
    $current_month_start = date('Y-m-01');
    $current_month_end = date('Y-m-t');
    $stmt_monthly = $conn->prepare("
        SELECT
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as expense
        FROM transactions t JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.date BETWEEN ? AND ? AND t.currency = 'USD'
    ");
    $stmt_monthly->bind_param("iss", $user_id, $current_month_start, $current_month_end);
    $stmt_monthly->execute();
    $monthly_totals = $stmt_monthly->get_result()->fetch_assoc();
    $stmt_monthly->close();

    $response = [
        'current_balance' => (float)($balance_result['current_balance'] ?? 0),
        'month_income' => (float)($monthly_totals['income'] ?? 0),
        'month_spending' => (float)($monthly_totals['expense'] ?? 0),
        'month_net' => (float)(($monthly_totals['income'] ?? 0) - ($monthly_totals['expense'] ?? 0))
    ];

    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}