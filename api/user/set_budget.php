<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');
$raw_category_id = $_POST['category_id'] ?? '';
$budget_amount = (float)($_POST['budget_amount'] ?? 0);
$month = date('Y-m');

try {
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

    // PostgreSQL ON CONFLICT syntax
    $stmt = $conn->prepare("
        INSERT INTO budgets (user_id, category_id, month, budget_amount) 
        VALUES (?, ?, ?, ?)
        ON CONFLICT (user_id, category_id, month) DO UPDATE SET budget_amount = EXCLUDED.budget_amount
    ");
    $stmt->execute([$user_id, $final_category_id, $month, $budget_amount]);
    echo json_encode(['success' => true, 'message' => 'Budget set successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
