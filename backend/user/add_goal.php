<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$target_amount = filter_input(INPUT_POST, 'target_amount', FILTER_VALIDATE_FLOAT);

if (!$name || !$target_amount) {
    exit(json_encode(['success' => false, 'error' => 'All fields are required.']));
}

try {
    $stmt = $conn->prepare("INSERT INTO goals (user_id, name, target_amount) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $user_id, $name, $target_amount);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Goal added!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}