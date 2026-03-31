<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated.']));
}
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    // 1. Get all unique categories from the user's table
    $stmt = $conn->prepare("SELECT id, name, type FROM categories WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 2. Define the default list
    $default_categories = [
        ['name' => 'Salary', 'type' => 'income'],
        ['name' => 'Freelance', 'type' => 'income'],
        ['name' => 'Investments', 'type' => 'income'],
        ['name' => 'Gifts Received', 'type' => 'income'],
        ['name' => 'Other Income', 'type' => 'income'],
        ['name' => 'Food & Groceries', 'type' => 'expense'],
        ['name' => 'Rent/Mortgage', 'type' => 'expense'],
        ['name' => 'Utilities', 'type' => 'expense'],
        ['name' => 'Transportation', 'type' => 'expense'],
        ['name' => 'Dining Out', 'type' => 'expense'],
        ['name' => 'Entertainment', 'type' => 'expense'],
        ['name' => 'Health & Medical', 'type' => 'expense'],
        ['name' => 'Education', 'type' => 'expense'],
        ['name' => 'Shopping', 'type' => 'expense'],
        ['name' => 'Bills', 'type' => 'expense'],
        ['name' => 'Travel', 'type' => 'expense'],
        ['name' => 'Personal Care', 'type' => 'expense'],
        ['name' => 'Children', 'type' => 'expense'],
        ['name' => 'Pets', 'type' => 'expense'],
        ['name' => 'Home Maintenance', 'type' => 'expense'],
        ['name' => 'Subscriptions', 'type' => 'expense'],
        ['name' => 'Other Expenses', 'type' => 'expense'],
    ];

    // 3. THE FIX: Merge and de-duplicate intelligently
    $final_list = [];
    $names_added = [];

    // Helper function to standardize names for comparison
    function normalize_name($name)
    {
        return strtolower(trim(preg_replace("/\s*\(.*\)/", "", $name)));
    }

    // Add user's real categories first
    foreach ($user_categories as $cat) {
        $normalized_name = normalize_name($cat['name']);
        if (!in_array($normalized_name, $names_added)) {
            $final_list[] = $cat;
            $names_added[] = $normalized_name;
        }
    }

    // Add any missing default categories
    foreach ($default_categories as $default) {
        $normalized_name = normalize_name($default['name']);
        if (!in_array($normalized_name, $names_added)) {
            $default['id'] = strtolower($default['type']) . '-' . strtolower(str_replace(' ', '-', $default['name']));
            $final_list[] = $default;
            $names_added[] = $normalized_name;
        }
    }

    // Sort the final list alphabetically by name
    usort($final_list, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    echo json_encode(['success' => true, 'data' => $final_list]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}