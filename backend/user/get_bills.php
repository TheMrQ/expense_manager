<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) { 
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); 
    exit; 
}

try {
    $stmt = $pdo->prepare("SELECT id, name, amount, due_date, category_id, last_paid_month FROM bills WHERE user_id = ? ORDER BY due_date ASC");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>