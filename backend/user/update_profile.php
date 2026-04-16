<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['username'], $data['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']); exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    if ($stmt->execute([$data['username'], $data['email'], $data['user_id']])) {
        // Fetch the fully updated user data, now including profile_picture
        $stmt2 = $pdo->prepare("SELECT id, username, email, profile_picture FROM users WHERE id = ?");
        $stmt2->execute([$data['user_id']]);
        $updatedUser = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'user' => $updatedUser]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile.']);
    }
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>