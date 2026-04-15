<?php
require __DIR__ . '/../connection/config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Security: Require both the user_id (to prove ownership) and the transaction_id
if (!isset($data['user_id'], $data['transaction_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

try {
    // Only delete if the transaction actually belongs to the logged-in user
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$data['transaction_id'], $data['user_id']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete transaction.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>