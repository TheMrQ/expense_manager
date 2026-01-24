<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');
header('Content-Type: application/json');

try {
    $stmt_budgets = $conn->prepare("
        SELECT b.category_id, c.name as category_name, b.budget_amount
        FROM budgets b
        JOIN categories c ON b.category_id = c.id
        WHERE (b.user_id, b.category_id, b.month) IN (
            SELECT user_id, category_id, MAX(month)
            FROM budgets
            WHERE user_id = ?
            GROUP BY user_id, category_id
        )
    ");
    $stmt_budgets->execute([$user_id]);
    $budgets_raw = $stmt_budgets->fetchAll();

    $budgets = [];
    foreach ($budgets_raw as $row) {
        $budgets[$row['category_id']] = $row;
        $budgets[$row['category_id']]['spent_amount'] = 0;
    }

    $stmt_spending = $conn->prepare("
        SELECT category_id, SUM(amount) as spent_this_month
        FROM transactions
        WHERE user_id = ? AND TO_CHAR(date, 'YYYY-MM') = ?
        GROUP BY category_id
    ");
    $stmt_spending->execute([$user_id, $current_month]);
    $spending_results = $stmt_spending->fetchAll();

    foreach ($spending_results as $row) {
        if (isset($budgets[$row['category_id']])) {
            $budgets[$row['category_id']]['spent_amount'] = (float)$row['spent_this_month'];
        }
    }

    echo json_encode(['success' => true, 'data' => array_values($budgets)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
