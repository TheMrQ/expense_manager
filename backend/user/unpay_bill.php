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

    // 1. Get the bill's name to find the transaction
    $stmt_bill = $conn->prepare("SELECT name FROM bills WHERE id = ? AND user_id = ?");
    $stmt_bill->bind_param("ii", $bill_id, $user_id);
    $stmt_bill->execute();
    $bill = $stmt_bill->get_result()->fetch_assoc();
    $stmt_bill->close();

    if (!$bill) {
        throw new Exception('Bill not found.');
    }

    // 2. **THE FIX:** Delete the corresponding transaction
    $note_to_find = "Paid bill: " . $bill['name'];
    $current_month_start = date('Y-m-01');
    $current_month_end = date('Y-m-t');

    $stmt_delete = $conn->prepare("
        DELETE FROM transactions 
        WHERE user_id = ? 
        AND note = ? 
        AND date BETWEEN ? AND ?
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt_delete->bind_param("isss", $user_id, $note_to_find, $current_month_start, $current_month_end);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 3. Mark the bill as unpaid
    $stmt_update = $conn->prepare("UPDATE bills SET last_paid_month = NULL WHERE id = ? AND user_id = ?");
    $stmt_update->bind_param("ii", $bill_id, $user_id);
    $stmt_update->execute();

    if ($stmt_update->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Bill marked as unpaid and transaction removed.']);
    } else {
        throw new Exception('Bill not found or was already unpaid.');
    }
    $stmt_update->close();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
