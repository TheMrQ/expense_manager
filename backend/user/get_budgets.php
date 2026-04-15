<?php
require __DIR__ . '/../connection/config.php';
$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$month = $data['month'] ?? date('Y-m'); // Defaults to '2026-04'

if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id as category_id, 
            c.name as category_name, 
            COALESCE(b.budget_amount, 0) as budget_amount,
            (
                SELECT COALESCE(SUM(amount), 0) 
                FROM transactions t 
                WHERE t.category_id = c.id AND t.user_id = c.user_id AND DATE_FORMAT(t.date, '%Y-%m') = ?
            ) as spent_amount
        FROM categories c
        LEFT JOIN budgets b ON c.id = b.category_id AND b.month = ? AND b.user_id = ?
        WHERE c.user_id = ? AND c.type = 'expense'
    ");
    $stmt->execute([$month, $month, $user_id, $user_id]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
?>