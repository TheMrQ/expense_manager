<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['goal_id'], $data['amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); 
    exit;
}

try {
    // Update the correct 'saved_amount' column
    $stmt = $pdo->prepare("UPDATE goals SET saved_amount = saved_amount + ? WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$data['amount'], $data['goal_id'], $data['user_id']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
    }
} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>