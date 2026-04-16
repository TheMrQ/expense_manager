<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['user_id'], $data['goal_id'], $data['name'], $data['target_amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); exit;
}
$deadline = !empty($data['deadline']) ? $data['deadline'] : null;
try {
    $stmt = $pdo->prepare("UPDATE goals SET name = ?, target_amount = ?, deadline = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$data['name'], $data['target_amount'], $deadline, $data['goal_id'], $data['user_id']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>