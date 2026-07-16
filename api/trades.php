<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}
$userId = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

$db = getDB();

if ($method === 'GET') {
    // Basic retrieval for Part 4 (Search/Filter comes in Part 5)
    $stmt = $db->prepare('SELECT * FROM trades WHERE user_id = ? ORDER BY trade_date DESC');
    $stmt->execute([$userId]);
    $trades = $stmt->fetchAll();

    // Enhance with calculated P&L
    foreach ($trades as &$trade) {
        $trade['pnl'] = calculateProfitLoss($trade['trade_type'], $trade['entry_price'], $trade['exit_price'], $trade['quantity']);
    }

    echo json_encode(['success' => true, 'data' => $trades]);
} elseif ($method === 'POST') {
    $data = $_POST;
    if (empty($data['asset_name']) || empty($data['trade_type']) || empty($data['entry_price']) || empty($data['exit_price'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }
    $stmt = $db->prepare('
        INSERT INTO trades (user_id, asset_name, trade_type, entry_price, exit_price, quantity, trade_date, notes, emotion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    try {
        $stmt->execute([
            $userId, trim($data['asset_name']), $data['trade_type'], (float)$data['entry_price'], 
            (float)$data['exit_price'], (float)($data['quantity'] ?: 1), $data['trade_date'], 
            trim($data['notes'] ?? ''), $data['emotion'] ?? null
        ]);
        echo json_encode(['success' => true, 'message' => 'Trade added successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}
?>
