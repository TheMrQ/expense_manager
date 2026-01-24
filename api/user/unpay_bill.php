<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$bill_id = $data['id'] ?? null;

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("SELECT name FROM bills WHERE id = ? AND user_id = ?");
    $stmt->execute([$bill_id, $user_id]);
    $bill = $stmt->fetch();

    if ($bill) {
        $note = "Paid bill: " . $bill['name'];
        $del = $conn->prepare("DELETE FROM transactions WHERE user_id = ? AND note = ? AND date >= DATE_TRUNC('month', CURRENT_DATE)");
        $del->execute([$user_id, $note]);
    }

    $upd = $conn->prepare("UPDATE bills SET last_paid_month = NULL WHERE id = ? AND user_id = ?");
    $upd->execute([$bill_id, $user_id]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Bill unpaid.']);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
