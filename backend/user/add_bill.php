<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['name'], $data['amount'], $data['due_date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']); 
    exit;
}

// Category is optional for bills
$category_id = !empty($data['category_id']) ? $data['category_id'] : null;

try {
    $stmt = $pdo->prepare("INSERT INTO bills (user_id, name, amount, due_date, category_id) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$data['user_id'], $data['name'], $data['amount'], $data['due_date'], $category_id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add bill.']);
    }
} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>