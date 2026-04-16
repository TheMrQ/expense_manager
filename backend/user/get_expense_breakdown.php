<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$month = $data['month'] ?? date('Y-m');

if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT c.name as category_name, SUM(t.amount) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND c.type = 'expense' AND DATE_FORMAT(t.date, '%Y-%m') = ?
        GROUP BY c.id
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id, $month]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>