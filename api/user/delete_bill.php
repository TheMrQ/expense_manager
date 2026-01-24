<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$bill_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$bill_id) {
    exit(json_encode(['success' => false, 'error' => 'Invalid bill ID.']));
}

try {
    $stmt = $conn->prepare("DELETE FROM bills WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $bill_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Bill deleted successfully.']);
    } else {
        throw new Exception('Bill not found or you do not have permission to delete it.');
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
