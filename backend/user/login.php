<?php
require __DIR__ . '/../connection/config.php';

// Vue.js sends data as a JSON payload, so we must read it this way instead of $_POST
$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing username or password.']);
        exit;
    }

    try {
        // Fetch user from DB using our new PDO connection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]); // Allows login with username OR email
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Success — start session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Respond with JSON instead of a header redirect!
            echo json_encode([
                'status' => 'success',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
}
?>