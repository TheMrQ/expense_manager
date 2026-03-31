<?php
require __DIR__ . '/../connection/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username']; // ✅ CORRECT
    $password = $_POST['password'];

    // Fetch user from DB
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();


    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // ✅ Success — start session, redirect or respond
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: ../../frontend/dashboard.html"); // or wherever your homepage is
            exit;
        } else {
            die("Incorrect password.");
        }
    } else {
        die("Email not found.");
    }
}