<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

try {
    $s_stmt = $conn->prepare("SELECT COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END), 0) as income, COALESCE(SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END), 0) as expense FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.date BETWEEN ? AND ?");
    $s_stmt->execute([$user_id, $start, $end]);
    $s = $s_stmt->fetch();

    $b_stmt = $conn->prepare("SELECT c.name as category_name, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND c.type = 'expense' AND t.date BETWEEN ? AND ? GROUP BY c.name ORDER BY total DESC");
    $b_stmt->execute([$user_id, $start, $end]);

    echo json_encode(['success' => true, 'data' => ['summary' => ['income' => (float)$s['income'], 'expense' => (float)$s['expense'], 'net' => $s['income'] - $s['expense']], 'expense_breakdown' => $b_stmt->fetchAll()]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
