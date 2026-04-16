<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['bill_id'], $data['month'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); 
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE bills SET last_paid_month = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$data['month'], $data['bill_id'], $data['user_id']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to mark as paid.']);
    }
} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>