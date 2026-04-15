<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['transaction_id'], $data['category_id'], $data['amount'], $data['date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']); exit;
}

try {
    $stmt = $pdo->prepare("UPDATE transactions SET category_id = ?, amount = ?, note = ?, date = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$data['category_id'], $data['amount'], $data['note'] ?? '', $data['date'], $data['transaction_id'], $data['user_id']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update.']);
    }
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>