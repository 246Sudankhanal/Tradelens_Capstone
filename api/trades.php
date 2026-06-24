<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
header('Content-Type: application/json');

// Mocking requireAuth for Sudan's isolated part
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default to user 1 for demonstration
}
$userId = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = $_POST;
    
    // Simple validation
    if (empty($data['asset_name']) || empty($data['trade_type']) || empty($data['entry_price']) || empty($data['exit_price'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare('
        INSERT INTO trades (user_id, asset_name, trade_type, entry_price, exit_price, quantity, trade_date, notes, emotion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    try {
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
        echo json_encode(['success' => true, 'message' => 'Trade added successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>
