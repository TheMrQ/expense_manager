<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$due_date = filter_input(INPUT_POST, 'due_date', FILTER_VALIDATE_INT);
$raw_category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_STRING);

if (!$name || !$amount || !$due_date || !$raw_category_id) {
    exit(json_encode(['success' => false, 'error' => 'All fields are required.']));
}

try {
    $final_category_id = null;

    // --- Category Handling Logic ---
    if (is_numeric($raw_category_id)) {
        // If it's a number, it's an existing category. Use it directly.
        $final_category_id = (int)$raw_category_id;
    } else {
        // If it's text (e.g., "expense-rent"), find or create the category.
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
            // It doesn't exist, so create it.
            $stmt_insert = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iss", $user_id, $category_name, $category_type);
            $stmt_insert->execute();
            $final_category_id = $conn->insert_id;
            $stmt_insert->close();
        }
        $stmt_find->close();
    }

    if ($final_category_id === null) {
        throw new Exception("Category processing failed.");
    }

    // --- Final Insert with a guaranteed numeric ID ---
    $stmt = $conn->prepare("INSERT INTO bills (user_id, name, amount, due_date, category_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdii", $user_id, $name, $amount, $due_date, $final_category_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Bill added successfully.']);
    } else {
        throw new Exception('Failed to add bill.');
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}