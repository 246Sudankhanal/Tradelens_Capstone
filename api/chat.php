<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST required.');
}

$body        = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($body['message'] ?? '');
$history     = $body['history'] ?? [];

if ($userMessage === '') {
    jsonResponse(false, 'Message cannot be empty.');
}

// ---- Check Ollama is reachable ----
$pingCh = curl_init(OLLAMA_HOST . '/api/tags');
curl_setopt_array($pingCh, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
$pingRes = curl_exec($pingCh);
$pingErr = curl_error($pingCh);
curl_close($pingCh);

if ($pingErr || !$pingRes) {
    jsonResponse(false, 'Ollama is not running. Open Terminal and run: ollama serve');
}

// ---- Fetch user trade context from DB ----
$db = getDB();

$stmt = $db->prepare("
    SELECT
        COUNT(*) AS total_trades,
        ROUND(SUM(CASE WHEN trade_type='Buy'  THEN (exit_price-entry_price)*quantity
                       ELSE (entry_price-exit_price)*quantity END), 2) AS net_pnl,
        SUM(CASE WHEN (CASE WHEN trade_type='Buy'  THEN exit_price-entry_price
                            ELSE entry_price-exit_price END) > 0 THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN (CASE WHEN trade_type='Buy'  THEN exit_price-entry_price
                            ELSE entry_price-exit_price END) < 0 THEN 1 ELSE 0 END) AS losses,
        MAX(CASE WHEN trade_type='Buy'  THEN (exit_price-entry_price)*quantity
                 ELSE (entry_price-exit_price)*quantity END) AS best_trade,
        MIN(CASE WHEN trade_type='Buy'  THEN (exit_price-entry_price)*quantity
                 ELSE (entry_price-exit_price)*quantity END) AS worst_trade
    FROM trades WHERE user_id = ?
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
        ROUND(CASE WHEN trade_type='Buy'  THEN (exit_price-entry_price)*quantity
                   ELSE (entry_price-exit_price)*quantity END, 2) AS pnl
    FROM trades WHERE user_id = ?
    ORDER BY trade_date DESC, id DESC LIMIT 10
");
$stmt->execute([$userId]);
$recentTrades = $stmt->fetchAll();

// Emotion breakdown
$stmt = $db->prepare("
    SELECT emotion, COUNT(*) AS cnt
    FROM trades WHERE user_id = ? AND emotion IS NOT NULL AND emotion != ''
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

## Trader: {$userName}

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

// ---- Build messages for Ollama (uses system/user/assistant roles) ----
$messages = [['role' => 'system', 'content' => $systemPrompt]];

// Append conversation history (last 20 turns)
foreach (array_slice($history, -20) as $turn) {
    $role    = $turn['role']    ?? '';
    $content = $turn['content'] ?? '';
    if (!in_array($role, ['user', 'assistant']) || !is_string($content) || $content === '') continue;
    $messages[] = ['role' => $role, 'content' => $content];
}

$messages[] = ['role' => 'user', 'content' => $userMessage];

// ---- Call Ollama API ----
$payload = json_encode([
    'model'    => OLLAMA_MODEL,
    'messages' => $messages,
    'stream'   => false,
    'options'  => ['temperature' => 0.7, 'num_predict' => 1024],
]);

$ch = curl_init(OLLAMA_HOST . '/api/chat');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 120,   // local models can be slow on first response
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);

$raw     = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode= curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr) {
    jsonResponse(false, 'Could not reach Ollama: ' . $curlErr);
}

$result = json_decode($raw, true);

if ($httpCode !== 200) {
    $errMsg = $result['error'] ?? ('Ollama error — HTTP ' . $httpCode);
    // Model not pulled yet
    if (str_contains($errMsg, 'not found') || str_contains($errMsg, 'pull')) {
        $errMsg = 'Model "' . OLLAMA_MODEL . '" not found. Run: ollama pull ' . OLLAMA_MODEL;
    }
    jsonResponse(false, $errMsg);
}

$reply = $result['message']['content'] ?? '';
if (!$reply) {
    jsonResponse(false, 'Empty response from Ollama. Try again.');
}

jsonResponse(true, '', ['reply' => $reply]);
