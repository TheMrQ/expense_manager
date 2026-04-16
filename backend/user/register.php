<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username'], $data['display_name'], $data['email'], $data['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

try {
    // 1. Check if email or username already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check->execute([$data['email'], $data['username']]);
    if ($check->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email or Username already registered.']);
        exit;
    }

    // 2. Hash password and create the user
    $pass = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, display_name, email, password) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$data['username'], $data['display_name'], $data['email'], $pass])) {
        
        // 3. Get the ID of the brand new user
        $new_user_id = $pdo->lastInsertId();

        // 4. Automatically generate their default categories!
        $default_categories = [
            ['Salary', 'income'],
            ['Freelance', 'income'],
            ['Other', 'income'],
            ['Food & Groceries', 'expense'],
            ['Rent', 'expense'],
            ['Utilities', 'expense'],
            ['Entertainment', 'expense'],
            ['Transportation', 'expense'],
            ['Other', 'expense']
        ];

        $cat_stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
        
        foreach ($default_categories as $cat) {
            $cat_stmt->execute([$new_user_id, $cat[0], $cat[1]]);
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>