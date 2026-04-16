<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['bill_id'], $data['month'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE bills SET last_paid_month = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['month'], $data['bill_id'], $data['user_id']]);

    $stmt2 = $pdo->prepare("SELECT name, amount FROM bills WHERE id = ?");
    $stmt2->execute([$data['bill_id']]);
    $bill = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($bill) {
        // Find or create the 'Bills' category for this user
        $catStmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Bills' AND type = 'expense'");
        $catStmt->execute([$data['user_id']]);
        $catId = $catStmt->fetchColumn();

        if (!$catId) {
            $insertCat = $pdo->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, 'Bills', 'expense')");
            $insertCat->execute([$data['user_id']]);
            $catId = $pdo->lastInsertId();
        }

        // Log the transaction with the 'Bills' expense category
        $date = date('Y-m-d');
        $note = "Bill Payment: " . $bill['name'];
        $stmt3 = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, note, date) VALUES (?, ?, ?, ?, ?)");
        $stmt3->execute([$data['user_id'], $catId, $bill['amount'], $note, $date]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) { 
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>