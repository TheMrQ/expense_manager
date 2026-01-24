<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require __DIR__ . '/../connection/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        die("❌ All fields are required.");
    }

    if ($password !== $confirmPassword) {
        die("❌ Passwords do not match.");
    }

    try {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            die("❌ Username or Email already exists.");
        }

        // Hash password and insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

        if ($insertStmt->execute([$username, $email, $hashedPassword])) {
            // Success: Redirect to login
            header("Location: /login.html?registered=true");
            exit();
        } else {
            die("❌ Registration failed.");
        }
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        die("❌ A database error occurred.");
    }
}
