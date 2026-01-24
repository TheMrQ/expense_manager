<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

$start_date = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING);
$end_date = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING);

if (!$start_date || !$end_date) {
    exit(json_encode(['success' => false, 'error' => 'Start and end dates are required.']));
}

try {
    $response = [];

    // 1. Get main summary stats (Income, Expense, Net)
    $stmt_summary = $conn->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
            COALESCE(SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
    ");
    $stmt_summary->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt_summary->execute();
    $summary = $stmt_summary->get_result()->fetch_assoc();
    $response['summary'] = [
        'income' => (float)$summary['total_income'],
        'expense' => (float)$summary['total_expense'],
        'net' => (float)($summary['total_income'] - $summary['total_expense'])
    ];
    $stmt_summary->close();

    // 2. Get expense breakdown by category
    $stmt_breakdown = $conn->prepare("
        SELECT c.name as category_name, SUM(t.amount) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND c.type = 'expense' AND t.date BETWEEN ? AND ?
        GROUP BY c.name
        ORDER BY total DESC
    ");
    $stmt_breakdown->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt_breakdown->execute();
    $response['expense_breakdown'] = $stmt_breakdown->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_breakdown->close();

    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
