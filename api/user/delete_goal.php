<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$goal_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$goal_id) exit(json_encode(['success' => false, 'error' => 'Invalid ID.']));

try {
    $conn->beginTransaction();
    // 1. Get name for note
    $stmt = $conn->prepare("SELECT name FROM goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    $goal = $stmt->fetch();

    if ($goal) {
        // 2. Delete transactions
        $note = "Contribution to: " . $goal['name'];
        $del_trans = $conn->prepare("DELETE FROM transactions WHERE user_id = ? AND note = ?");
        $del_trans->execute([$user_id, $note]);
    }

    // 3. Delete goal
    $del_goal = $conn->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
    $del_goal->execute([$goal_id, $user_id]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Goal removed.']);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
