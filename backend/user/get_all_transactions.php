<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;
if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); exit; }

try {
    // Same query as recent, but without the LIMIT 10
    $stmt = $pdo->prepare("
        SELECT t.id, t.category_id, t.amount, t.note, t.date, c.name as category_name, c.type as category_type
        FROM transactions t LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? ORDER BY t.date DESC, t.id DESC
    ");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>