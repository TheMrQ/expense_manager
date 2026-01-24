<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$category_id = filter_var($data['category_id'] ?? null, FILTER_VALIDATE_INT);
$month = date('Y-m');

if (!$category_id) {
    exit(json_encode(['success' => false, 'error' => 'Invalid Category ID.']));
}

try {
    $stmt = $conn->prepare("DELETE FROM budgets WHERE category_id = ? AND user_id = ? AND month = ?");
    $stmt->bind_param("iis", $category_id, $user_id, $month);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Budget deleted successfully.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Budget removed.']);
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
