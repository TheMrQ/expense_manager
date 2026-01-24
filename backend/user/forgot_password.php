<?php
session_start();
require_once '../connection/config.php';
require_once '../mail/send_email.php'; // Make sure this file contains sendPasswordResetEmail()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $_SESSION["forgot_errors"] = ["❌ Please enter your email."];
        header("Location: ../../frontend/forgot_password.html");
        exit();
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION["forgot_errors"] = ["❌ Email not found."];
        header("Location: ../../frontend/forgot_password.html");
        exit();
    }

    $user = $result->fetch_assoc();
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Save token to DB
    $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expires = ? WHERE email = ?");
    if (!$update) {
        die("Prepare failed: " . $conn->error);
    }
    $update->bind_param("sss", $token, $expiry, $email);
    $update->execute();


    // Send email
    sendPasswordResetEmail($email, $token);
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reset Link Sent</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans&display=swap" rel="stylesheet" />
    <style>
        body {
        background: #f5f5f5;
        font-family: 'DM Sans', sans-serif;
        margin: 0;
        padding: 0;
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
        margin-bottom: 20px;
        }
        .btn {
        display: inline-block;
        padding: 10px 24px;
        background-color: #03ca93;
        color: #fff;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-size: 15px;
        cursor: pointer;
        }
        .btn:hover {
        background-color: #02b582;
        }
    </style>
    </head>
    <body>
    <div class="form-container">
        <h2>📩 Reset Link Sent</h2>
        <p>Please check your email for the password reset link. It will expire in 10 minutes.</p>
        <a class="btn" href="../../frontend/login.html">Back to Login</a>
    </div>
    </body>
    </html>
    HTML;
    exit;
}
