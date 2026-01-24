<?php
session_start();
require_once '../connection/config.php';
require_once '../mail/send_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $upd = $conn->prepare("UPDATE users SET reset_token = ?, token_expires = ? WHERE email = ?");
        $upd->execute([$token, $expiry, $email]);
        sendPasswordResetEmail($email, $token);
    }
    echo "Check your email for the reset link.";
}
