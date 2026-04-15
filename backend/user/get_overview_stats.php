<?php
require __DIR__ . '/../connection/config.php';

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

try {
    $stats = [
        'total_balance' => 0,
        'monthly_income' => 0,
        'monthly_expenses' => 0
    ];

    $currentMonth = date('m');
    $currentYear = date('Y');

    // 1. CALCULATE TOTAL BALANCE (JOINING with Categories to check type)
    $stmtBalance = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as total_in,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as total_out
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
    ");
    $stmtBalance->execute([$user_id]);
    $row = $stmtBalance->fetch();
    
    $stats['total_balance'] = (float)($row['total_in'] ?? 0) - (float)($row['total_out'] ?? 0);

    // 2. CALCULATE THIS MONTH'S TOTALS
    $stmtMonthly = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as month_in,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as month_out
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND MONTH(t.date) = ? AND YEAR(t.date) = ?
    ");
    $stmtMonthly->execute([$user_id, $currentMonth, $currentYear]);
    $mRow = $stmtMonthly->fetch();

    $stats['monthly_income'] = (float)($mRow['month_in'] ?? 0);
    $stats['monthly_expenses'] = (float)($mRow['month_out'] ?? 0);

    echo json_encode($stats);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>