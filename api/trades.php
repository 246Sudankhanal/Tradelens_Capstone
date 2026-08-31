<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

// Parse body for PUT/DELETE
$input = [];
if ($method === 'PUT' || $method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
}

// Helper to get contract/lot size multiplier based on asset name
function getAssetMultiplier(string $assetName): float {
    $asset = strtoupper(trim($assetName));
    if (strpos($asset, 'XAU') !== false || strpos($asset, 'GOLD') !== false) {
        return 100.0; // 1 lot XAUUSD = 100 oz
    }
    if (strpos($asset, 'XAG') !== false || strpos($asset, 'SILVER') !== false) {
        return 5000.0; // 1 lot XAGUSD = 5,000 oz
    }
    return 1.0; // Default standard multiplier for forex/indices
}

switch ($method) {

    case 'GET':
        $db = getDB();
        $where  = ['t.user_id = ?'];
        $params = [$userId];

        // Search by asset name
        if (!empty($_GET['search'])) {
            $where[]  = 't.asset_name LIKE ?';
            $params[] = '%' . $_GET['search'] . '%';
        }

        // Filter by trade type
        if (!empty($_GET['type']) && in_array($_GET['type'], ['Buy', 'Sell'])) {
            $where[]  = 't.trade_type = ?';
            $params[] = $_GET['type'];
        }

        // Filter by date range
        if (!empty($_GET['date_from'])) {
            $where[]  = 't.trade_date >= ?';
            $params[] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $where[]  = 't.trade_date <= ?';
            $params[] = $_GET['date_to'];
        }

        // Sorting
        $sortMap = [
            'date_desc'  => 't.trade_date DESC, t.id DESC',
            'date_asc'   => 't.trade_date ASC, t.id ASC',
            'asset_asc'  => 't.asset_name ASC',
            'asset_desc' => 't.asset_name DESC',
            'pnl_desc'   => 'pnl DESC',
            'pnl_asc'    => 'pnl ASC',
        ];
        $sort   = $_GET['sort'] ?? 'date_desc';
        $orderBy= $sortMap[$sort] ?? 't.trade_date DESC, t.id DESC';

        $whereStr = implode(' AND ', $where);

        // SQL P&L calculation handles multipliers dynamically based on asset name
        $sql = "
            SELECT
                t.id,
                t.asset_name,
                t.trade_type,
                t.entry_price,
                t.exit_price,
                t.quantity,
                t.trade_date,
                t.notes,
                t.emotion,
                t.created_at,
                CASE
                    WHEN t.asset_name LIKE '%XAU%' OR t.asset_name LIKE '%GOLD%'
                    THEN ROUND(
                        CASE WHEN t.trade_type = 'Buy' THEN (t.exit_price - t.entry_price) ELSE (t.entry_price - t.exit_price) END 
                        * t.quantity * 100, 2)
                    WHEN t.asset_name LIKE '%XAG%' OR t.asset_name LIKE '%SILVER%'
                    THEN ROUND(
                        CASE WHEN t.trade_type = 'Buy' THEN (t.exit_price - t.entry_price) ELSE (t.entry_price - t.exit_price) END 
                        * t.quantity * 5000, 2)
                    ELSE ROUND(
                        CASE WHEN t.trade_type = 'Buy' THEN (t.exit_price - t.entry_price) ELSE (t.entry_price - t.exit_price) END 
                        * t.quantity, 2)
                END AS pnl
            FROM trades t
            WHERE $whereStr
            ORDER BY $orderBy
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $trades = $stmt->fetchAll();

        jsonResponse(true, '', $trades);

    case 'POST':
        $data = $_POST;
        $errors = validateTradeInput($data);
        if ($errors) jsonResponse(false, $errors);

        $db = getDB();
        $stmt = $db->prepare('
            INSERT INTO trades (user_id, asset_name, trade_type, entry_price, exit_price, quantity, trade_date, notes, emotion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $userId,
            trim($data['asset_name']),
            $data['trade_type'],
            (float)$data['entry_price'],
            (float)$data['exit_price'],
            (float)($data['quantity'] ?: 1),
            $data['trade_date'],
            trim($data['notes'] ?? ''),
            $data['emotion'] ?? null,
        ]);

        jsonResponse(true, 'Trade added successfully.', ['id' => $db->lastInsertId()]);

    case 'PUT':
        $tradeId = (int)($input['id'] ?? 0);
        if (!$tradeId) jsonResponse(false, 'Trade ID required.');

        // Ownership check
        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM trades WHERE id = ? AND user_id = ?');
        $stmt->execute([$tradeId, $userId]);
        if (!$stmt->fetch()) jsonResponse(false, 'Trade not found.');

        $errors = validateTradeInput($input);
        if ($errors) jsonResponse(false, $errors);

        $stmt = $db->prepare('
            UPDATE trades
            SET asset_name=?, trade_type=?, entry_price=?, exit_price=?, quantity=?, trade_date=?, notes=?, emotion=?
            WHERE id=? AND user_id=?
        ');
        $stmt->execute([
            trim($input['asset_name']),
            $input['trade_type'],
            (float)$input['entry_price'],
            (float)$input['exit_price'],
            (float)($input['quantity'] ?: 1),
            $input['trade_date'],
            trim($input['notes'] ?? ''),
            $input['emotion'] ?? null,
            $tradeId,
            $userId,
        ]);

        jsonResponse(true, 'Trade updated successfully.');

    case 'DELETE':
        $tradeId = (int)($input['id'] ?? 0);
        if (!$tradeId) jsonResponse(false, 'Trade ID required.');

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM trades WHERE id = ? AND user_id = ?');
        $stmt->execute([$tradeId, $userId]);

        if ($stmt->rowCount() === 0) jsonResponse(false, 'Trade not found.');

        jsonResponse(true, 'Trade deleted successfully.');

    default:
        jsonResponse(false, 'Method not allowed.');
}

function validateTradeInput(array $data): string {
    if (empty($data['asset_name']))  return 'Asset name is required.';
    if (empty($data['trade_type']) || !in_array($data['trade_type'], ['Buy','Sell']))
        return 'Trade type must be Buy or Sell.';
    if (!isset($data['entry_price']) || !is_numeric($data['entry_price']) || $data['entry_price'] <= 0)
        return 'Entry price must be a positive number.';
    if (!isset($data['exit_price'])  || !is_numeric($data['exit_price'])  || $data['exit_price']  <= 0)
        return 'Exit price must be a positive number.';
    if (isset($data['quantity']) && $data['quantity'] !== '' && (!is_numeric($data['quantity']) || $data['quantity'] <= 0))
        return 'Quantity must be a positive number.';
    if (empty($data['trade_date'])) return 'Trade date is required.';
    return '';
}
