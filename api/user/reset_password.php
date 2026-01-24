<?php
require '../connection/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $pass = $_POST['new_password'] ?? '';

    $stmt = $conn->prepare("SELECT id, token_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user && strtotime($user['token_expires']) > time()) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expires = NULL WHERE id = ?");
        $upd->execute([$hash, $user['id']]);
        echo "Password reset successful! <a href='../../frontend/login.html'>Login</a>";
    } else {
        echo "Invalid or expired link.";
    }
}
