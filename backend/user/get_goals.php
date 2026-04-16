<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) { 
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); 
    exit; 
}

try {
    // Try to fetch WITH deadline and the correct saved_amount column
    $stmt = $pdo->prepare("SELECT id, name, target_amount, saved_amount, deadline FROM goals WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    // Fallback if deadline column doesn't exist
    try {
        $stmt = $pdo->prepare("SELECT id, name, target_amount, saved_amount FROM goals WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e2) {
        echo json_encode(['status' => 'error', 'message' => $e2->getMessage()]);
    }
}
?>