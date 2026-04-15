<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['category_id'], $data['month'], $data['budget_amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO budgets (user_id, category_id, month, budget_amount) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE budget_amount = ?
    ");
    if ($stmt->execute([$data['user_id'], $data['category_id'], $data['month'], $data['budget_amount'], $data['budget_amount']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>