<?php
date_default_timezone_set('Asia/Ho_Chi_Minh'); // Time zone fix
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');
header('Content-Type: application/json');

try {
    // 1. Get all budgets the user has ever set.
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
    $stmt_budgets->bind_param("i", $user_id);
    $stmt_budgets->execute();
    $budgets_result = $stmt_budgets->get_result();

    $budgets = [];
    while ($row = $budgets_result->fetch_assoc()) {
        $budgets[$row['category_id']] = $row;
        $budgets[$row['category_id']]['spent_amount'] = 0;
    }
    $stmt_budgets->close();

    // 2. Get the total spending for each category for the CURRENT month.
    $stmt_spending = $conn->prepare("
        SELECT category_id, SUM(amount) as spent_this_month
        FROM transactions
        WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
        GROUP BY category_id
    ");
    $stmt_spending->bind_param("is", $user_id, $current_month);
    $stmt_spending->execute();
    $spending_result = $stmt_spending->get_result();

    // 3. Merge the spending data into our budgets array.
    while ($row = $spending_result->fetch_assoc()) {
        if (isset($budgets[$row['category_id']])) {
            $budgets[$row['category_id']]['spent_amount'] = $row['spent_this_month'];
        }
    }
    $stmt_spending->close();

    echo json_encode(['success' => true, 'data' => array_values($budgets)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
