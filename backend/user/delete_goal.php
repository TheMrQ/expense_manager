<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['user_id'], $data['goal_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); exit;
}
try {
    $stmt = $pdo->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$data['goal_id'], $data['user_id']])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete.']);
    }
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>