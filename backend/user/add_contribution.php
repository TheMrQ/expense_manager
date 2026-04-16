<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['goal_id'], $data['amount'])) {
    echo json_encode(['status' => 'error']); exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE goals SET saved_amount = saved_amount + ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['amount'], $data['goal_id'], $data['user_id']]);

    $stmt2 = $pdo->prepare("SELECT name FROM goals WHERE id = ?");
    $stmt2->execute([$data['goal_id']]);
    $goal = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($goal) {
        // Find or create the 'Goals' category for this user
        $catStmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Goals' AND type = 'expense'");
        $catStmt->execute([$data['user_id']]);
        $catId = $catStmt->fetchColumn();

        if (!$catId) {
            $insertCat = $pdo->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, 'Goals', 'expense')");
            $insertCat->execute([$data['user_id']]);
            $catId = $pdo->lastInsertId();
        }

        // Log the transaction with the 'Goals' expense category
        $date = date('Y-m-d');
        $note = "Goal Contribution: " . $goal['name'];
        $stmt3 = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, note, date) VALUES (?, ?, ?, ?, ?)");
        $stmt3->execute([$data['user_id'], $catId, $data['amount'], $note, $date]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) { 
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>