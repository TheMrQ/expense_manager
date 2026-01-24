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

$raw_category_id = $_POST['category_id'] ?? '';
$amount_str = $_POST['amount'] ?? '';
$date_input = $_POST['date'] ?? '';
$note = $_POST['note'] ?? '';
$currency = $_POST['currency'] ?? 'USD';

$amount = (float)str_replace(',', '.', $amount_str);

try {
    $conn->beginTransaction();

    $final_category_id = null;
    if (is_numeric($raw_category_id)) {
        $final_category_id = (int)$raw_category_id;
    } else {
        $parts = explode('-', $raw_category_id, 2);
        $category_type = $parts[0];
        $category_name = ucwords(str_replace('-', ' ', $parts[1]));

        $stmt_find = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ? LIMIT 1");
        $stmt_find->execute([$user_id, $category_name, $category_type]);
        $cat = $stmt_find->fetch();

        if ($cat) {
            $final_category_id = $cat['id'];
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
            $stmt_insert->execute([$user_id, $category_name, $category_type]);
            $final_category_id = $conn->lastInsertId();
        }
    }

    $sql = "INSERT INTO transactions (user_id, category_id, amount, note, date, currency) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $final_category_id, $amount, $note, $date_input, $currency]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Transaction added successfully!']);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
