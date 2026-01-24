<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $stmt_bal = $conn->prepare("SELECT COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE -t.amount END), 0) FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.currency = 'USD'");
    $stmt_bal->execute([$user_id]);
    $balance = $stmt_bal->fetchColumn();

    $start = date('Y-m-01');
    $end = date('Y-m-t');
    $stmt_month = $conn->prepare("SELECT SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as inc, SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as exp FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.date BETWEEN ? AND ? AND t.currency = 'USD'");
    $stmt_month->execute([$user_id, $start, $end]);
    $m = $stmt_month->fetch();

    echo json_encode(['success' => true, 'data' => [
        'current_balance' => (float)$balance,
        'month_income' => (float)$m['inc'],
        'month_spending' => (float)$m['exp'],
        'month_net' => (float)($m['inc'] - $m['exp'])
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
