<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

$month = filter_input(INPUT_GET, 'month', FILTER_SANITIZE_STRING);
if (!preg_match("/^\d{4}-\d{2}$/", $month)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid month format.']);
    exit();
}

try {
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));

    $stmt = $conn->prepare("
        SELECT t.id, t.date, c.name AS category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
        ORDER BY t.date ASC, t.amount DESC
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $daily_top_transaction = [];
    while ($row = $result->fetch_assoc()) {
        $date = $row['date'];
        if (!isset($daily_top_transaction[$date])) {
            // Store both the name and the transaction ID
            $daily_top_transaction[$date] = [
                'category_name' => $row['category_name'],
                'transaction_id' => $row['id']
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $daily_top_transaction]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}