<?php
require __DIR__ . '/../connection/config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit();
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$raw_category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_STRING);
$amount_str = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_STRING);
$date_input = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
$note = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) ?? '';
$currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING) ?? 'USD';

$amount = filter_var(str_replace(',', '.', $amount_str), FILTER_VALIDATE_FLOAT);
if ($amount === false || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
    exit();
}

$date_obj = DateTime::createFromFormat('Y-m-d', $date_input);
if ($date_obj === false || $date_obj->format('Y-m-d') !== $date_input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date format.']);
    exit();
}
$final_date_for_db = $date_obj->format('Y-m-d');

try {
    $conn->begin_transaction();

    $final_category_id = null;
    if (is_numeric($raw_category_id)) {
        $final_category_id = (int)$raw_category_id;
    } else {
        $parts = explode('-', $raw_category_id, 2);
        if (count($parts) !== 2) throw new Exception("Invalid category format.");
        $category_type = $parts[0];
        $category_name = ucwords(str_replace('-', ' ', $parts[1]));

        $stmt_find = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ? LIMIT 1");
        $stmt_find->bind_param("iss", $user_id, $category_name, $category_type);
        $stmt_find->execute();
        $result_find = $stmt_find->get_result();
        if ($cat = $result_find->fetch_assoc()) {
            $final_category_id = $cat['id'];
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iss", $user_id, $category_name, $category_type);
            $stmt_insert->execute();
            $final_category_id = $conn->insert_id;
            $stmt_insert->close();
        }
        $stmt_find->close();
    }
    if ($final_category_id === null) throw new Exception("Category processing failed.");

    $stmt_cat_type = $conn->prepare("SELECT type FROM categories WHERE id = ? AND user_id = ?");
    $stmt_cat_type->bind_param("ii", $final_category_id, $user_id);
    $stmt_cat_type->execute();
    $category_result = $stmt_cat_type->get_result()->fetch_assoc();
    $stmt_cat_type->close();
    if (!$category_result) throw new Exception("Selected category not found for user.");
    $transaction_type = $category_result['type'];

    if ($transaction_type === 'expense') {
        $stmt_bal = $conn->prepare("
            SELECT COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE -t.amount END), 0) as current_balance
            FROM transactions t JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.currency = ?
        ");
        $stmt_bal->bind_param("is", $user_id, $currency);
        $stmt_bal->execute();
        $balance_row = $stmt_bal->get_result()->fetch_assoc();
        $current_balance = $balance_row['current_balance'] ?? 0;
        $stmt_bal->close();

        if ($current_balance < $amount) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Insufficient balance. Current balance is {$currency} " . number_format($current_balance, 2) . "."]);
            $conn->rollback();
            exit();
        }
    }

    $sql = "INSERT INTO transactions (user_id, category_id, amount, note, date, currency) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iidsss", $user_id, $final_category_id, $amount, $note, $final_date_for_db, $currency);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction added successfully!']);
    } else {
        throw new Exception("Failed to add transaction. No rows affected.");
    }
    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    error_log("add_transaction.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}