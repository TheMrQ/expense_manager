<?php
require __DIR__ . '/../connection/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Get and Validate Data ---
$transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
$raw_category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_STRING);
$amount_str = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_STRING);
$date_input = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
$note = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) ?? '';
$currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING) ?? 'USD';

if (!$transaction_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Transaction ID is required for an update.']);
    exit();
}

$amount = filter_var(str_replace(',', '.', $amount_str), FILTER_VALIDATE_FLOAT);
if ($amount === false || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
    exit();
}

$date_obj = DateTime::createFromFormat('Y-m-d', $date_input);
if ($date_obj === false || $date_obj->format('Y-m-d') !== $date_input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date format. Please use YYYY-MM-DD.']);
    exit();
}
$final_date = $date_obj->format('Y-m-d');

try {
    $conn->begin_transaction();

    // --- Verify transaction ownership ---
    $stmt_verify = $conn->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ?");
    $stmt_verify->bind_param("ii", $transaction_id, $user_id);
    $stmt_verify->execute();
    if ($stmt_verify->get_result()->num_rows === 0) {
        $stmt_verify->close();
        throw new Exception("Transaction not found or permission denied.", 404);
    }
    $stmt_verify->close();

    // --- Category Handling (Same as add_transaction.php) ---
    $final_category_id = null;
    if (is_numeric($raw_category_id)) {
        // Assume it's an existing, user-owned category ID.
        // A more robust check could be added here if needed.
        $final_category_id = (int)$raw_category_id;
    } else {
        // Logic to find or create default category
        $parts = explode('-', $raw_category_id, 2);
        if (count($parts) !== 2) throw new Exception("Invalid default category format.");

        $category_type = $parts[0];
        $category_name_raw = str_replace('-', ' ', $parts[1]);
        $category_name = ucwords($category_name_raw);
        // Add special name handling if necessary...

        $stmt_find = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ? LIMIT 1");
        $stmt_find->bind_param("iss", $user_id, $category_name, $category_type);
        $stmt_find->execute();
        $result_find = $stmt_find->get_result();
        if ($cat = $result_find->fetch_assoc()) {
            $final_category_id = $cat['id'];
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iss", $user_id, $category_name, $category_type);
            $stmt_insert->execute();
            $final_category_id = $conn->insert_id;
            $stmt_insert->close();
        }
        $stmt_find->close();
    }

    if ($final_category_id === null) {
        throw new Exception("Category processing failed.");
    }

    // --- Update Transaction ---
    $stmt_update = $conn->prepare("
        UPDATE transactions 
        SET category_id = ?, amount = ?, note = ?, date = ?, currency = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt_update->bind_param("idsssii", $final_category_id, $amount, $note, $final_date, $currency, $transaction_id, $user_id);
    $stmt_update->execute();

    if ($stmt_update->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction updated successfully!']);
    } else {
        // This can happen if the user saves without making any changes.
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'No changes were made to the transaction.']);
    }
    $stmt_update->close();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code($e->getCode() ?: 500);
    error_log("Error in update_transaction.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
