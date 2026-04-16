<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['name'], $data['target_amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); exit;
}

$deadline = !empty($data['deadline']) ? $data['deadline'] : null;

try {
    // FIX: We are now inserting 0 into 'saved_amount' to match your schema
    $stmt = $pdo->prepare("INSERT INTO goals (user_id, name, target_amount, saved_amount, deadline) VALUES (?, ?, ?, 0, ?)");
    if ($stmt->execute([$data['user_id'], $data['name'], $data['target_amount'], $deadline])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>