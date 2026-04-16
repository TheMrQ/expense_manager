<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['username'], $data['display_name'], $data['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']); exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET username = ?, display_name = ?, email = ? WHERE id = ?");
    if ($stmt->execute([$data['username'], $data['display_name'], $data['email'], $data['user_id']])) {
        $stmt2 = $pdo->prepare("SELECT id, username, display_name, email, profile_picture FROM users WHERE id = ?");
        $stmt2->execute([$data['user_id']]);
        echo json_encode(['status' => 'success', 'user' => $stmt2->fetch(PDO::FETCH_ASSOC)]);
    } else {
        echo json_encode(['status' => 'error']);
    }
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>