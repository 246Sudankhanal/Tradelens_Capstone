<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { $_SESSION['user_id'] = 1; }
$userId = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Fetch single trade
        $stmt = $db->prepare('SELECT * FROM trades WHERE id = ? AND user_id = ?');
        $stmt->execute([$_GET['id'], $userId]);
        $trade = $stmt->fetch();
        echo json_encode(['success' => !!$trade, 'data' => $trade]);
        exit;
    }
    // Fetch all (with filters)
    $where  = ['user_id = ?']; $params = [$userId];
    if (!empty($_GET['search'])) { $where[] = 'asset_name LIKE ?'; $params[] = '%' . $_GET['search'] . '%'; }
    if (!empty($_GET['type'])) { $where[] = 'trade_type = ?'; $params[] = $_GET['type']; }
    if (!empty($_GET['from'])) { $where[] = 'trade_date >= ?'; $params[] = $_GET['from']; }
    if (!empty($_GET['to'])) { $where[] = 'trade_date <= ?'; $params[] = $_GET['to']; }
    $whereStr = implode(' AND ', $where);
    $stmt = $db->prepare("SELECT * FROM trades WHERE $whereStr ORDER BY trade_date DESC");
    $stmt->execute($params);
    $trades = $stmt->fetchAll();
    foreach ($trades as &$trade) { $trade['pnl'] = calculateProfitLoss($trade['trade_type'], $trade['entry_price'], $trade['exit_price'], $trade['quantity']); }
    echo json_encode(['success' => true, 'data' => $trades]);

} elseif ($method === 'POST') {
    $data = $_POST;
    if (isset($data['_method']) && $data['_method'] === 'PUT') {
        // Handle as PUT
        $tradeId = (int)$data['id'];
        $stmt = $db->prepare('UPDATE trades SET asset_name=?, trade_type=?, entry_price=?, exit_price=?, quantity=?, trade_date=?, notes=?, emotion=? WHERE id=? AND user_id=?');
        try {
            $stmt->execute([trim($data['asset_name']), $data['trade_type'], (float)$data['entry_price'], (float)$data['exit_price'], (float)$data['quantity'], $data['trade_date'], trim($data['notes'] ?? ''), $data['emotion'] ?? null, $tradeId, $userId]);
            echo json_encode(['success' => true, 'message' => 'Trade updated successfully.']);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => 'Update failed.']); }
        exit;
    }
    // Regular POST
    $stmt = $db->prepare('INSERT INTO trades (user_id, asset_name, trade_type, entry_price, exit_price, quantity, trade_date, notes, emotion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    try {
        $stmt->execute([$userId, trim($data['asset_name']), $data['trade_type'], (float)$data['entry_price'], (float)$data['exit_price'], (float)($data['quantity'] ?: 1), $data['trade_date'], trim($data['notes'] ?? ''), $data['emotion'] ?? null]);
        echo json_encode(['success' => true, 'message' => 'Trade added successfully.']);
    } catch (Exception $e) { echo json_encode(['success' => false, 'message' => 'Save failed.']); }
}
?>
