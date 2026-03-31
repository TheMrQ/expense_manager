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
    $conn->begin_transaction();

    // 1. Get the goal name for the transaction note
    $stmt_goal = $conn->prepare("SELECT name FROM goals WHERE id = ? AND user_id = ?");
    $stmt_goal->bind_param("ii", $goal_id, $user_id);
    $stmt_goal->execute();
    $goal = $stmt_goal->get_result()->fetch_assoc();
    if (!$goal) throw new Exception("Goal not found.");
    $stmt_goal->close();

    // 2. Find or create a "Savings" expense category
    $savings_category_name = 'Savings';
    $savings_category_type = 'expense';
    $stmt_find_cat = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?");
    $stmt_find_cat->bind_param("iss", $user_id, $savings_category_name, $savings_category_type);
    $stmt_find_cat->execute();
    $category = $stmt_find_cat->get_result()->fetch_assoc();
    $stmt_find_cat->close();

    $category_id = null;
    if ($category) {
        $category_id = $category['id'];
    } else {
        $stmt_insert_cat = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
        $stmt_insert_cat->bind_param("iss", $user_id, $savings_category_name, $savings_category_type);
        $stmt_insert_cat->execute();
        $category_id = $conn->insert_id;
        $stmt_insert_cat->close();
    }

    // 3. Create a corresponding expense transaction
    $date = date('Y-m-d');
    $note = "Contribution to: " . $goal['name'];
    $currency = 'USD'; // Assuming base currency for goals is USD
    $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, category_id, amount, note, date, currency) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_trans->bind_param("iidsss", $user_id, $category_id, $amount, $note, $date, $currency);
    $stmt_trans->execute();
    $stmt_trans->close();

    // 4. Update the saved_amount in the goals table
    $stmt_update = $conn->prepare("UPDATE goals SET saved_amount = saved_amount + ? WHERE id = ? AND user_id = ?");
    $stmt_update->bind_param("dii", $amount, $goal_id, $user_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Contribution added and transaction recorded!']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
}