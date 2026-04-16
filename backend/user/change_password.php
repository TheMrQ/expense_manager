<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['current_password'], $data['new_password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); 
    exit;
}

try {
    // 1. Verify the current password first
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$data['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data['current_password'], $user['password'])) {
        // 2. Hash and save the new password
        $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($update->execute([$newHash, $data['user_id']])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save new password.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
    }
} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>