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
    $conn->begin_transaction();

    // 1. Get bill details
    $stmt_bill = $conn->prepare("SELECT name, amount, category_id FROM bills WHERE id = ? AND user_id = ?");
    $stmt_bill->bind_param("ii", $bill_id, $user_id);
    $stmt_bill->execute();
    $bill = $stmt_bill->get_result()->fetch_assoc();
    $stmt_bill->close();

    if (!$bill) {
        throw new Exception('Bill not found.');
    }

    // 2. Add the expense to the transactions table
    $date = date('Y-m-d');
    $note = "Paid bill: " . $bill['name'];
    $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, category_id, amount, note, date, currency) VALUES (?, ?, ?, ?, ?, 'USD')");
    $stmt_trans->bind_param("iidss", $user_id, $bill['category_id'], $bill['amount'], $note, $date);
    $stmt_trans->execute();
    $stmt_trans->close();

    // 3. Update the bill's last_paid_month
    $current_month = date('Y-m');
    $stmt_update = $conn->prepare("UPDATE bills SET last_paid_month = ? WHERE id = ?");
    $stmt_update->bind_param("si", $current_month, $bill_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Bill marked as paid and transaction recorded.']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
}
