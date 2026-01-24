<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? ''; // Format: YYYY-MM
header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT t.id, t.date, c.name AS category_name FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND TO_CHAR(t.date, 'YYYY-MM') = ? ORDER BY t.date ASC");
    $stmt->execute([$user_id, $month]);
    $rows = $stmt->fetchAll();

    $data = [];
    foreach ($rows as $r) {
        if (!isset($data[$r['date']])) {
            $data[$r['date']] = ['category_name' => $r['category_name'], 'transaction_id' => $r['id']];
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
