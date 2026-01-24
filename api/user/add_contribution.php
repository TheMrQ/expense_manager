<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

$goal_id = filter_input(INPUT_POST, 'goal_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

if (!$goal_id || !$amount) {
    exit(json_encode(['success' => false, 'error' => 'All fields are required.']));
}

try {
    $conn->beginTransaction();

    $stmt_goal = $conn->prepare("SELECT name FROM goals WHERE id = ? AND user_id = ?");
    $stmt_goal->execute([$goal_id, $user_id]);
    $goal = $stmt_goal->fetch();
    if (!$goal) throw new Exception("Goal not found.");

    $savings_category_name = 'Savings';
    $savings_category_type = 'expense';
    $stmt_find_cat = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?");
    $stmt_find_cat->execute([$user_id, $savings_category_name, $savings_category_type]);
    $category = $stmt_find_cat->fetch();

    $category_id = $category ? $category['id'] : null;
    if (!$category_id) {
        $stmt_insert_cat = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
        $stmt_insert_cat->execute([$user_id, $savings_category_name, $savings_category_type]);
        $category_id = $conn->lastInsertId();
    }

    $date = date('Y-m-d');
    $note = "Contribution to: " . $goal['name'];
    $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, category_id, amount, note, date, currency) VALUES (?, ?, ?, ?, ?, 'USD')");
    $stmt_trans->execute([$user_id, $category_id, $amount, $note, $date]);

    $stmt_update = $conn->prepare("UPDATE goals SET saved_amount = saved_amount + ? WHERE id = ? AND user_id = ?");
    $stmt_update->execute([$amount, $goal_id, $user_id]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Contribution added!']);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
}
