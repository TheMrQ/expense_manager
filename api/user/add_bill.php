<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$due_date = filter_input(INPUT_POST, 'due_date', FILTER_VALIDATE_INT);
$raw_category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$name || !$amount || !$due_date || !$raw_category_id) {
    exit(json_encode(['success' => false, 'error' => 'All fields are required.']));
}

try {
    $final_category_id = null;

    if (is_numeric($raw_category_id)) {
        $final_category_id = (int)$raw_category_id;
    } else {
        $parts = explode('-', $raw_category_id, 2);
        if (count($parts) !== 2) throw new Exception("Invalid category format.");

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

    $stmt = $conn->prepare("INSERT INTO bills (user_id, name, amount, due_date, category_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $amount, $due_date, $final_category_id]);

    echo json_encode(['success' => true, 'message' => 'Bill added successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
