<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_identifier = $data['username'] ?? ''; 
    $password = $data['password'] ?? '';

    if (empty($login_identifier) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing username or password.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$login_identifier]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            echo json_encode([
                'status' => 'success',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'display_name' => $user['display_name'] ?? $user['username'],
                    'email' => $user['email'],
                    'profile_picture' => $user['profile_picture'] ?? null
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