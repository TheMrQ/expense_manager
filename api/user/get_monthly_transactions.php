<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT t.id, c.name AS category_name, c.type AS category_type, t.amount, t.note, t.date, t.currency FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? ORDER BY t.date DESC");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();

    $monthly_data = [];
    foreach ($rows as $t) {
        $month = date('F Y', strtotime($t['date']));
        if (!isset($monthly_data[$month])) {
            $monthly_data[$month] = ['transactions' => [], 'top_expense' => null, 'top_income' => null];
        }
        $monthly_data[$month]['transactions'][] = $t;

        $type = ($t['category_type'] === 'expense') ? 'top_expense' : 'top_income';
        if (!$monthly_data[$month][$type] || $t['amount'] > $monthly_data[$month][$type]['amount']) {
            $monthly_data[$month][$type] = ['name' => $t['category_name'], 'amount' => $t['amount'], 'currency' => $t['currency']];
        }
    }
    echo json_encode(['success' => true, 'data' => $monthly_data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
