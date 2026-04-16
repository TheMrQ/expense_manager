<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) { echo json_encode(['status' => 'error']); exit; }

try {
    // Find the old picture file and delete it from the server
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['profile_picture']) {
        $filePath = "../uploads/" . $user['profile_picture'];
        if (file_exists($filePath)) unlink($filePath);
    }

    // Set the database column back to NULL
    $stmt2 = $pdo->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
    if ($stmt2->execute([$user_id])) {
        $stmt3 = $pdo->prepare("SELECT id, username, email, profile_picture FROM users WHERE id = ?");
        $stmt3->execute([$user_id]);
        echo json_encode(['status' => 'success', 'user' => $stmt3->fetch(PDO::FETCH_ASSOC)]);
    } else {
        echo json_encode(['status' => 'error']);
    }
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>