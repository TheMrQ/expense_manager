<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT c.name, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND c.type = 'expense' AND t.date BETWEEN ? AND ? GROUP BY c.name ORDER BY total DESC LIMIT 5");
    $stmt->execute([$user_id, date('Y-m-01'), date('Y-m-t')]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
