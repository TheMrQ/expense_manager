
<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

// Accept text for new categories
$raw_category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_STRING);
$budget_amount = filter_input(INPUT_POST, 'budget_amount', FILTER_VALIDATE_FLOAT);
$month = date('Y-m');

if (!$raw_category_id || $budget_amount === false) {
    exit(json_encode(['success' => false, 'error' => 'Category and amount are required.']));
}

try {
    $final_category_id = null;

    // --- Category Handling Logic ---
    if (is_numeric($raw_category_id)) {
        $final_category_id = (int)$raw_category_id;
    } else {
        // It's a string (e.g., "expense-home-maintenance"), so find or create it
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

    if ($final_category_id === null) {
        throw new Exception("Category processing failed.");
    }

    $stmt = $conn->prepare("
        INSERT INTO budgets (user_id, category_id, month, budget_amount) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE budget_amount = VALUES(budget_amount)
    ");
    $stmt->bind_param("iisd", $user_id, $final_category_id, $month, $budget_amount);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Budget set successfully.']);
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
