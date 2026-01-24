<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $current_month_start = date('Y-m-01');
    $current_month_end = date('Y-m-t');

    $stmt = $conn->prepare("
        SELECT c.name, SUM(t.amount) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND c.type = 'expense' AND t.date BETWEEN ? AND ?
        GROUP BY c.name
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->bind_param("iss", $user_id, $current_month_start, $current_month_end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
