<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'User not authenticated.']));
}
header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];
$transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
$raw_category_id = $_POST['category_id'] ?? '';
$amount = (float)str_replace(',', '.', $_POST['amount'] ?? 0);
$date = $_POST['date'] ?? '';
$note = $_POST['note'] ?? '';
$currency = $_POST['currency'] ?? 'USD';

try {
    $conn->beginTransaction();
    $stmt_verify = $conn->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ?");
    $stmt_verify->execute([$transaction_id, $user_id]);
    if (!$stmt_verify->fetch()) throw new Exception("Transaction not found.", 404);

    $final_category_id = is_numeric($raw_category_id) ? (int)$raw_category_id : null;
    if (!$final_category_id) {
        $parts = explode('-', $raw_category_id, 2);
        $category_name = ucwords(str_replace('-', ' ', $parts[1]));
        $stmt_find = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ? LIMIT 1");
        $stmt_find->execute([$user_id, $category_name, $parts[0]]);
        $cat = $stmt_find->fetch();
        if ($cat) {
            $final_category_id = $cat['id'];
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
            $stmt_insert->execute([$user_id, $category_name, $parts[0]]);
            $final_category_id = $conn->lastInsertId();
        }
    }

    $stmt_update = $conn->prepare("UPDATE transactions SET category_id = ?, amount = ?, note = ?, date = ?, currency = ? WHERE id = ? AND user_id = ?");
    $stmt_update->execute([$final_category_id, $amount, $note, $date, $currency, $transaction_id, $user_id]);
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Transaction updated successfully!']);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollback();
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
