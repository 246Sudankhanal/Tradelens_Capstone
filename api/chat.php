<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../includes/trading_accounts.php';
require_once __DIR__ . '/../includes/pnl.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST required.');
}

$body        = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($body['message'] ?? '');
$history     = $body['history'] ?? [];
$conversationId = (int) ($body['conversation_id'] ?? 0);

if ($userMessage === '') {
    jsonResponse(false, 'Message cannot be empty.');
}

// ---- Fetch user trade context from DB ----
$db = getDB();
bootstrapUserAccounts($db, $userId);
$accSql = accountSql();
$pnl = tradePnlSql();
$accLabel = 'All accounts';
$aid = currentAccountId();
if ($aid) {
    $owned = ownedTradingAccount($db, $userId, $aid);
    if ($owned) $accLabel = (string) $owned['display_name'];
}

$stmt = $db->prepare("
    SELECT
        COUNT(*) AS total_trades,
        ROUND(SUM($pnl), 2) AS net_pnl,
        SUM(CASE WHEN ($pnl) > 0 THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN ($pnl) < 0 THEN 1 ELSE 0 END) AS losses,
        MAX($pnl) AS best_trade,
        MIN($pnl) AS worst_trade
    FROM trades WHERE user_id = ?{$accSql}
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

$total   = (int)($stats['total_trades'] ?? 0);
$wins    = (int)($stats['wins']         ?? 0);
$losses  = (int)($stats['losses']       ?? 0);
$netPnl  = round((float)($stats['net_pnl']    ?? 0), 2);
$best    = round((float)($stats['best_trade']  ?? 0), 2);
$worst   = round((float)($stats['worst_trade'] ?? 0), 2);
$winRate = $total > 0 ? round(($wins / $total) * 100, 1) : 0;

// Recent 10 trades
$stmt = $db->prepare("
    SELECT asset_name, trade_type, entry_price, exit_price, quantity, trade_date, emotion, notes,
        ROUND($pnl, 2) AS pnl
    FROM trades WHERE user_id = ?{$accSql}
    ORDER BY trade_date DESC, id DESC LIMIT 10
");
$stmt->execute([$userId]);
$recentTrades = $stmt->fetchAll();

// Emotion breakdown
$stmt = $db->prepare("
    SELECT emotion, COUNT(*) AS cnt
    FROM trades WHERE user_id = ? AND emotion IS NOT NULL AND emotion != ''{$accSql}
    GROUP BY emotion ORDER BY cnt DESC
");
$stmt->execute([$userId]);
$emotions = $stmt->fetchAll();

// ---- Build system prompt ----
$userName    = $_SESSION['user_name'] ?? 'Trader';

$recentLines = '';
foreach ($recentTrades as $t) {
    $sign = (float)$t['pnl'] >= 0 ? '+' : '';
    $recentLines .= sprintf(
        "  • %s | %s %s | Entry \$%s → Exit \$%s (qty %s) | P&L: %s\$%.2f%s\n",
        $t['trade_date'],
        $t['trade_type'],
        $t['asset_name'],
        number_format((float)$t['entry_price'], 2),
        number_format((float)$t['exit_price'],  2),
        number_format((float)$t['quantity'],    2),
        $sign, abs((float)$t['pnl']),
        $t['emotion'] ? ' [' . $t['emotion'] . ']' : ''
    );
    if ($t['notes']) $recentLines .= '    Note: "' . substr($t['notes'], 0, 80) . "\"\n";
}
if (!$recentLines) $recentLines = "  • No trades logged yet.\n";

$emotionLines = '';
foreach ($emotions as $e) {
    $emotionLines .= "  • {$e['emotion']}: {$e['cnt']} trade(s)\n";
}
if (!$emotionLines) $emotionLines = "  • No emotion data logged yet.\n";

$systemPrompt = "You are TradeLens AI, a personal trading journal assistant built into TradeLens.
GUARDRAILS:
1. Only answer questions related to the user's trading performance, risk management, emotions, and trade history provided below.
2. Never give explicit financial advice, price predictions, or tell the user to buy or sell specific assets. 
3. If the user asks about anything unrelated to trading, politely decline and redirect them back to their journal.
4. Keep answers concise, data-driven, and focused on behavioral patterns.

## Trader: {$userName}
## Viewing account: {$accLabel}

## Their Current Stats
- Total trades: {$total}
- Wins: {$wins} | Losses: {$losses}
- Win rate: {$winRate}%
- Net P&L: \${$netPnl}
- Best trade: +\${$best} | Worst trade: \${$worst}

## Recent Trades (last 10)
{$recentLines}
## Emotion / Psychology Log
{$emotionLines}
## Your Role
- Use the actual data above when answering questions about their performance
- Give honest, specific, actionable feedback referencing their real numbers
- Help them spot emotional patterns (e.g. poor results when trading 'Impulsive')
- Answer trading questions: strategy, risk management, concepts
- Keep replies concise (2-4 short paragraphs), conversational and encouraging
- If they have no trades yet, encourage them to start logging
- Do not invent trades or numbers that are not in the data above";

$messages = [['role' => 'system', 'content' => $systemPrompt]];

foreach (array_slice($history, -20) as $turn) {
    $role    = $turn['role']    ?? '';
    $content = $turn['content'] ?? '';
    if (!in_array($role, ['user', 'assistant']) || !is_string($content) || $content === '') continue;
    $messages[] = ['role' => $role, 'content' => $content];
}

$messages[] = ['role' => 'user', 'content' => $userMessage];

if (!defined('OPENROUTER_API_KEY') || OPENROUTER_API_KEY === '' || strpos(OPENROUTER_API_KEY, 'YOUR_') !== false) {
    jsonResponse(false, 'OpenRouter is not configured. Add OPENROUTER_API_KEY in config/ai.php.');
}

$payload = json_encode([
    'model'       => OPENROUTER_MODEL,
    'messages'    => $messages,
    'temperature' => 0.7,
    'max_tokens'  => 1024,
]);

$ch = curl_init(OPENROUTER_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 45,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENROUTER_API_KEY,
        'HTTP-Referer: ' . OPENROUTER_SITE_URL,
        'X-Title: ' . OPENROUTER_APP_NAME,
    ],
]);

$raw      = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($curlErr) {
    jsonResponse(false, 'Could not reach OpenRouter: ' . $curlErr);
}

$result = json_decode($raw, true);

if ($httpCode !== 200) {
    $errMsg = $result['error']['message'] ?? ('OpenRouter error — HTTP ' . $httpCode);
    jsonResponse(false, $errMsg);
}

$reply = $result['choices'][0]['message']['content'] ?? '';
if (!$reply) {
    jsonResponse(false, 'Empty response from OpenRouter. Try again.');
}

try {
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

    if ($conversationId > 0) {
        $own = $db->prepare('SELECT id, title FROM chat_conversations WHERE id = ? AND user_id = ?');
        $own->execute([$conversationId, $userId]);
        $conv = $own->fetch();
        if (!$conv) $conversationId = 0;
    }

    if ($conversationId < 1) {
        $title = trim(preg_replace('/\s+/', ' ', $userMessage));
        $title = function_exists('mb_substr') ? mb_substr($title, 0, 72) : substr($title, 0, 72);
        if ($title === '') $title = 'New chat';
        $db->prepare('INSERT INTO chat_conversations (user_id, title) VALUES (?, ?)')->execute([$userId, $title]);
        $conversationId = (int) $db->lastInsertId();
    } else {
        $own = $db->prepare('SELECT title FROM chat_conversations WHERE id = ?');
        $own->execute([$conversationId]);
        $row = $own->fetch();
        if ($row && ($row['title'] === 'New chat' || $row['title'] === '')) {
            $title = trim(preg_replace('/\s+/', ' ', $userMessage));
            $title = function_exists('mb_substr') ? mb_substr($title, 0, 72) : substr($title, 0, 72);
            $db->prepare('UPDATE chat_conversations SET title = ? WHERE id = ?')->execute([$title, $conversationId]);
        }
    }

    $ins = $db->prepare('INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, ?, ?)');
    $ins->execute([$conversationId, 'user', $userMessage]);
    $ins->execute([$conversationId, 'assistant', $reply]);
    $db->prepare('UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$conversationId]);
} catch (PDOException $e) {
    // Reply still succeeds even if history save fails.
}

jsonResponse(true, '', ['reply' => $reply, 'conversation_id' => $conversationId]);