<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['bill_id'], $data['name'], $data['amount'], $data['due_date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']); 
    exit;
}

$category_id = !empty($data['category_id']) ? $data['category_id'] : null;

try {
    $stmt = $pdo->prepare("UPDATE bills SET name = ?, amount = ?, due_date = ?, category_id = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$data['name'], $data['amount'], $data['due_date'], $category_id, $data['bill_id'], $data['user_id']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update bill.']);
    }
} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>