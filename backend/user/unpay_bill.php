<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['bill_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']); 
    exit;
}

try {
    // Sets the last_paid_month back to NULL so it shows as due again
    $stmt = $pdo->prepare("UPDATE bills SET last_paid_month = NULL WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$data['bill_id'], $data['user_id']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to unmark bill.']);
    }
} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>