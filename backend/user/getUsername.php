<?php
session_start();
require __DIR__ . '/../connection/config.php';

if (!isset($_SESSION['user_id'])) {
    echo 'Guest';
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username);

if ($stmt->fetch()) {
    echo htmlspecialchars($username);
} else {
    echo 'User';
}

$stmt->close();
$conn->close();
