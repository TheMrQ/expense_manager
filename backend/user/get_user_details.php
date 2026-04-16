<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;
if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); exit; }

try {
    $stmt = $pdo->prepare("SELECT id, username, display_name, email, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) echo json_encode(['status' => 'success', 'data' => $user]);
    else echo json_encode(['status' => 'error', 'message' => 'User not found.']);
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>