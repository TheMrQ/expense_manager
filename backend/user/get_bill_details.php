<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;
if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT b.id, b.name, b.amount, b.due_date, b.last_paid_month, c.name as category_name
        FROM bills b LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.user_id = ? ORDER BY b.due_date ASC
    ");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>