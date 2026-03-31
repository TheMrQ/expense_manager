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

if (!$goal_id) {
    exit(json_encode(['success' => false, 'error' => 'Invalid goal ID.']));
}

try {
    $conn->begin_transaction();

    // 1. Get the goal's name before deleting it
    $stmt_get = $conn->prepare("SELECT name FROM goals WHERE id = ? AND user_id = ?");
    $stmt_get->bind_param("ii", $goal_id, $user_id);
    $stmt_get->execute();
    $goal = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    if ($goal) {
        // 2. Delete all associated contribution transactions from the ledger
        $note_to_find = "Contribution to: " . $goal['name'];
        $stmt_delete_trans = $conn->prepare("DELETE FROM transactions WHERE user_id = ? AND note = ?");
        $stmt_delete_trans->bind_param("is", $user_id, $note_to_find);
        $stmt_delete_trans->execute();
        $stmt_delete_trans->close();
    }

    // 3. Delete the goal itself (which will also cascade-delete from the 'contributions' table)
    $stmt_delete_goal = $conn->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
    $stmt_delete_goal->bind_param("ii", $goal_id, $user_id);
    $stmt_delete_goal->execute();

    if ($stmt_delete_goal->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Goal and all associated transactions deleted successfully.']);
    } else {
        // This can happen on a double-click, which is fine.
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Goal removed.']);
    }
    $stmt_delete_goal->close();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}