<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;
if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); exit; }

try {
    $month = date('m'); $year = date('Y');
    $stmt = $pdo->prepare("
        SELECT c.name as category_name, SUM(t.amount) as total_amount
        FROM transactions t JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND c.type = 'expense' AND MONTH(t.date) = ? AND YEAR(t.date) = ?
        GROUP BY c.id ORDER BY total_amount DESC LIMIT 5
    ");
    $stmt->execute([$user_id, $month, $year]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>