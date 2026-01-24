<?php
session_start();
require __DIR__ . '/../connection/config.php';

if (!isset($_SESSION['user_id'])) {
    echo 'Guest';
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $username = $stmt->fetchColumn();

    echo $username ? htmlspecialchars($username) : 'User';
} catch (PDOException $e) {
    echo 'User';
}
