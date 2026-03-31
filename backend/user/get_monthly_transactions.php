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
        SELECT t.id, c.name AS category_name, c.type AS category_type, t.amount, t.note, t.date, t.currency
        FROM transactions t JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? ORDER BY t.date DESC, t.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $monthly_data = [];
    while ($transaction = $result->fetch_assoc()) {
        $month_key = date('F Y', strtotime($transaction['date']));
        if (!isset($monthly_data[$month_key])) {
            $monthly_data[$month_key] = ['transactions' => [], 'top_expense' => null, 'top_income' => null];
        }
        $monthly_data[$month_key]['transactions'][] = $transaction;
    }

    foreach ($monthly_data as $month => &$data) {
        $expense_totals = [];
        $income_totals = [];
        // This logic now also tracks the currency of the largest transaction
        $top_expense_info = ['amount' => 0];
        $top_income_info = ['amount' => 0];

        foreach ($data['transactions'] as $t) {
            if ($t['category_type'] === 'expense' && $t['amount'] > $top_expense_info['amount']) {
                $top_expense_info = ['name' => $t['category_name'], 'amount' => $t['amount'], 'currency' => $t['currency']];
            } else if ($t['category_type'] === 'income' && $t['amount'] > $top_income_info['amount']) {
                $top_income_info = ['name' => $t['category_name'], 'amount' => $t['amount'], 'currency' => $t['currency']];
            }
        }

        if ($top_expense_info['amount'] > 0) $data['top_expense'] = $top_expense_info;
        if ($top_income_info['amount'] > 0) $data['top_income'] = $top_income_info;
    }

    $stmt->close();
    echo json_encode(['success' => true, 'data' => $monthly_data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}