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

try {
    $conn->beginTransaction();
    $stmt_bill = $conn->prepare("SELECT name, amount, category_id FROM bills WHERE id = ? AND user_id = ?");
    $stmt_bill->execute([$bill_id, $user_id]);
    $bill = $stmt_bill->fetch();
    if (!$bill) throw new Exception('Bill not found.');

    $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, category_id, amount, note, date, currency) VALUES (?, ?, ?, ?, ?, 'USD')");
    $stmt_trans->execute([$user_id, $bill['category_id'], $bill['amount'], "Paid bill: " . $bill['name'], date('Y-m-d')]);

    $stmt_update = $conn->prepare("UPDATE bills SET last_paid_month = ? WHERE id = ?");
    $stmt_update->execute([date('Y-m'), $bill_id]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Bill marked as paid.']);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
