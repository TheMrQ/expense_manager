<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); exit; }

try {
    // We fetch categories that belong to the user, OR default ones (if you want to implement defaults later)
    $stmt = $pdo->prepare("SELECT id, name, type FROM categories WHERE user_id = ? ORDER BY type ASC, name ASC");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>