<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = requireAuth();
$db     = getDB();
ensureChatTables($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === '') {
    $action = 'list';
}

function ensureChatTables(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS chat_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(160) NOT NULL DEFAULT 'New chat',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_chat_user (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            role ENUM('user','assistant') NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_chat_conv (conversation_id),
            FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
        )
    ");
}

function ownedConversation(PDO $db, int $userId, int $id): ?array {
    $stmt = $db->prepare('SELECT id, title, created_at, updated_at FROM chat_conversations WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

switch ($action) {
    case 'list':
        $stmt = $db->prepare('
            SELECT c.id, c.title, c.updated_at,
                   (SELECT content FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS preview
            FROM chat_conversations c
            WHERE c.user_id = ?
            ORDER BY c.updated_at DESC
            LIMIT 50
        ');
        $stmt->execute([$userId]);
        jsonResponse(true, '', ['conversations' => $stmt->fetchAll()]);

    case 'messages':
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $conv = ownedConversation($db, $userId, $id);
        if (!$conv) jsonResponse(false, 'Conversation not found.');
        $stmt = $db->prepare('SELECT role, content, created_at FROM chat_messages WHERE conversation_id = ? ORDER BY id ASC');
        $stmt->execute([$id]);
        jsonResponse(true, '', ['conversation' => $conv, 'messages' => $stmt->fetchAll()]);

    case 'new':
        $db->prepare('INSERT INTO chat_conversations (user_id, title) VALUES (?, ?)')->execute([$userId, 'New chat']);
        jsonResponse(true, '', ['id' => (int) $db->lastInsertId()]);

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        if (!ownedConversation($db, $userId, $id)) jsonResponse(false, 'Conversation not found.');
        $db->prepare('DELETE FROM chat_conversations WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
        jsonResponse(true, 'Chat deleted.');

    default:
        jsonResponse(false, 'Invalid action.');
}
