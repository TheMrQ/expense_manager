<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT id, name, type FROM categories WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_cats = $stmt->fetchAll();

    $defaults = [
        ['name' => 'Salary', 'type' => 'income'],
        ['name' => 'Food & Groceries', 'type' => 'expense'],
        ['name' => 'Rent/Mortgage', 'type' => 'expense'],
        ['name' => 'Utilities', 'type' => 'expense'],
        ['name' => 'Shopping', 'type' => 'expense'],
        ['name' => 'Entertainment', 'type' => 'expense']
    ];

    $final = [];
    $seen = [];

    foreach ($user_cats as $c) {
        $name = strtolower(trim($c['name']));
        if (!in_array($name, $seen)) {
            $final[] = $c;
            $seen[] = $name;
        }
    }

    foreach ($defaults as $d) {
        $name = strtolower(trim($d['name']));
        if (!in_array($name, $seen)) {
            $d['id'] = $d['type'] . '-' . str_replace(' ', '-', $name);
            $final[] = $d;
        }
    }

    usort($final, fn($a, $b) => strcmp($a['name'], $b['name']));
    echo json_encode(['success' => true, 'data' => $final]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
