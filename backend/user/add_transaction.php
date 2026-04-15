<?php
require __DIR__ . '/../connection/config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['category_id'], $data['amount'], $data['date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, note, date) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([
        $data['user_id'], 
        $data['category_id'], 
        $data['amount'], 
        $data['note'] ?? '', 
        $data['date']
    ])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>