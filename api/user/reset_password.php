<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require '../connection/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
        // Simple error for now, can be styled later if needed
        exit("❌ All fields are required.");
    }

    if ($newPassword !== $confirmPassword) {
        exit("❌ Passwords do not match.");
    }

    // Fetch user by token
    $stmt = $conn->prepare("SELECT id, token_expires FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close(); // Close statement early

    // Compare expiry properly
    if (!$user || strtotime($user['token_expires']) < time()) {
        exit("❌ Invalid or expired token. Please request a new reset link.");
    }

    // Hash and update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expires = NULL WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $user['id']);
    $updateStmt->execute();
    $updateStmt->close(); // Close the update statement

    // --- MODIFIED: Output a styled HTML success page ---
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Password Reset Successful</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans&display=swap" rel="stylesheet" />
    <style>
        body {
            background: #f5f5f5;
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .form-container {
            max-width: 420px;
            margin: 100px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .form-container h2 {
            color: #03ca93;
            margin-bottom: 16px;
        }
        .form-container p {
            color: #444;
            font-size: 16px;
            margin-bottom: 25px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #03ca93;
            color: #fff;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .btn:hover {
            background-color: #02b582;
        }
    </style>
    </head>
    <body>
    <div class="form-container">
        <h2>✅ Success!</h2>
        <p>Your password has been reset. You can now log in with your new credentials.</p>
        <a class="btn" href="../../frontend/login.html">Back to Login</a>
    </div>
    </body>
    </html>
HTML;
    exit();
}