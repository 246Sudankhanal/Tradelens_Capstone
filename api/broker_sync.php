<?php
/**
 * TradeLens — Broker auto-sync (MetaApi → journal)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/brokers.php';
require_once __DIR__ . '/../includes/trading_accounts.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    ob_start();
}
@set_time_limit(90);

define('METAAPI_PROVISIONING', 'https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai');

function brokerJson($success, $message = '', $data = null) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out);
    exit;
}

function metaApiToken(): string {
    global $BROKER_CREDENTIALS;
    return trim((string) ($BROKER_CREDENTIALS['metatrader5']['api_token'] ?? $BROKER_CREDENTIALS['metatrader4']['api_token'] ?? ''));
}

function metaApiRequest(string $url, string $method = 'GET', $body = null, int $retries = 6): array {
    $token = metaApiToken();
    if ($token === '') {
        throw new RuntimeException('MetaApi token is missing in config/brokers.php.');
    }

    $payload = is_array($body) ? json_encode($body) : $body;
    $attempt = 0;

    while ($attempt < $retries) {
        $attempt++;
        $ch = curl_init($url);
        $headers = [
            'Accept: application/json',
            'auth-token: ' . $token,
        ];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ];
        if (strtoupper($method) !== 'GET') {
            $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            if ($payload !== null) {
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'transaction-id: ' . bin2hex(random_bytes(16));
                $opts[CURLOPT_HTTPHEADER] = $headers;
                $opts[CURLOPT_POSTFIELDS] = $payload;
            } else {
                $headers[] = 'transaction-id: ' . bin2hex(random_bytes(16));
                $opts[CURLOPT_HTTPHEADER] = $headers;
            }
        }
        curl_setopt_array($ch, $opts);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);

        if ($raw === false) {
            throw new RuntimeException('MetaApi network error: ' . ($err ?: 'unknown'));
        }

        // History is often not ready yet right after connecting.
        if ($code === 202) {
            sleep(2);
            continue;
        }

        $decoded = json_decode($raw, true);

        if ($code >= 200 && $code < 300) {
            return is_array($decoded) ? $decoded : [];
        }

        $msg = is_array($decoded)
            ? (string) ($decoded['message'] ?? $decoded['error'] ?? $raw)
            : $raw;
        if (strlen($msg) > 400) $msg = substr($msg, 0, 400) . '…';
        throw new RuntimeException("MetaApi HTTP {$code}: {$msg}");
    }

    throw new RuntimeException('MetaApi is still synchronizing this account. Wait ~30 seconds and click Sync now.');
}

function ensureBrokerTables(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS broker_connections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            broker_key VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'disconnected',
            account_id VARCHAR(120) NULL,
            region VARCHAR(50) NULL,
            last_sync_at DATETIME NULL,
            last_error TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_broker (user_id, broker_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    foreach (['account_id VARCHAR(120) NULL', 'region VARCHAR(50) NULL'] as $col) {
        try {
            $db->exec("ALTER TABLE broker_connections ADD COLUMN {$col}");
        } catch (PDOException $e) { /* exists */ }
    }
    try {
        $db->exec("ALTER TABLE trades ADD COLUMN source VARCHAR(30) NOT NULL DEFAULT 'manual'");
    } catch (PDOException $e) { /* exists */ }
    try {
        $db->exec("ALTER TABLE trades ADD COLUMN broker_trade_id VARCHAR(120) NULL");
    } catch (PDOException $e) { /* exists */ }
}

function getMetaApiAccount(string $accountId): array {
    return metaApiRequest(METAAPI_PROVISIONING . '/users/current/accounts/' . rawurlencode($accountId));
}

function deployMetaApiAccount(string $accountId): void {
    try {
        metaApiRequest(METAAPI_PROVISIONING . '/users/current/accounts/' . rawurlencode($accountId) . '/deploy', 'POST', new stdClass(), 2);
    } catch (Throwable $e) {
        // Already deployed is fine.
        if (stripos($e->getMessage(), 'deploy') === false && stripos($e->getMessage(), 'HTTP 2') === false) {
            // keep going; polling will tell us if it connected
        }
    }
}

function waitForMetaApiConnected(string $accountId, int $seconds = 40): array {
    $deadline = time() + $seconds;
    $account = [];
    while (time() < $deadline) {
        $account = getMetaApiAccount($accountId);
        $state = strtoupper((string) ($account['state'] ?? ''));
        $conn  = strtoupper((string) ($account['connectionStatus'] ?? ''));
        if ($conn === 'CONNECTED' || $state === 'DEPLOYED' && $conn === 'CONNECTED') {
            return $account;
        }
        if (in_array($state, ['UNDEPLOYED', 'DEPLOY_FAILED'], true)) {
            deployMetaApiAccount($accountId);
        }
        sleep(2);
    }
    return $account;
}

function createMetaApiAccount(array $accountDetails): array {
    $url = METAAPI_PROVISIONING . '/users/current/accounts';
    $data = [
        'name'          => $accountDetails['name'],
        'type'          => 'cloud',
        'login'         => (string) $accountDetails['login'],
        'password'      => $accountDetails['password'],
        'server'        => $accountDetails['server'],
        'platform'      => $accountDetails['platform'] === 'mt4' ? 'mt4' : 'mt5',
        'magic'         => 0,
    ];

    try {
        $created = metaApiRequest($url, 'POST', $data, 2);
    } catch (Throwable $e) {
        $created = findExistingMetaApiAccount((string) $accountDetails['login']);
        if (!$created) {
            throw $e;
        }
    }

    $accountId = (string) ($created['id'] ?? '');
    if ($accountId === '') {
        throw new RuntimeException('MetaApi did not return an account id. Check login, password, and exact server name.');
    }

    deployMetaApiAccount($accountId);
    $account = waitForMetaApiConnected($accountId);
    $account['id'] = $accountId;
    return $account;
}

function findExistingMetaApiAccount(string $login): ?array {
    try {
        $list = metaApiRequest(METAAPI_PROVISIONING . '/users/current/accounts', 'GET', null, 2);
    } catch (Throwable $e) {
        return null;
    }
    if (!is_array($list)) return null;
    foreach ($list as $row) {
        if (!is_array($row)) continue;
        if ((string) ($row['login'] ?? '') === $login) {
            return $row;
        }
    }
    return null;
}

function clientApiHosts(?string $region): array {
    $hosts = [];
    $region = strtolower(trim((string) $region));
    if ($region !== '') {
        $hosts[] = 'https://mt-client-api-v1.' . $region . '.agiliumtrade.ai';
    }
    foreach (['new-york', 'london', 'singapore', 'vint-hill', 'tokyo'] as $r) {
        $h = 'https://mt-client-api-v1.' . $r . '.agiliumtrade.ai';
        if (!in_array($h, $hosts, true)) $hosts[] = $h;
    }
    return $hosts;
}

function mapBrokerFill(array $fill): array {
    $type = strtolower((string) ($fill['side'] ?? $fill['type'] ?? 'buy'));
    $tradeType = in_array($type, ['sell', 'short', 's'], true) ? 'Sell' : 'Buy';
    $date = $fill['closed_at'] ?? $fill['trade_date'] ?? date('Y-m-d');
    if (strlen($date) > 10) $date = substr($date, 0, 10);

    $notes = 'Imported via MetaTrader auto-sync';
    if (isset($fill['profit']) && $fill['profit'] !== null && $fill['profit'] !== '') {
        $notes .= ' · MT profit ' . $fill['profit'];
    }

    return [
        'asset_name'      => strtoupper(trim((string) ($fill['symbol'] ?? $fill['asset_name'] ?? ''))),
        'trade_type'      => $tradeType,
        'entry_price'     => (float) ($fill['entry_price'] ?? 0),
        'exit_price'      => (float) ($fill['exit_price'] ?? 0),
        'quantity'        => (float) ($fill['quantity'] ?? $fill['qty'] ?? 1),
        'trade_date'      => $date,
        'notes'           => $notes,
        'broker_trade_id' => (string) ($fill['id'] ?? $fill['broker_trade_id'] ?? ''),
    ];
}

function metaDealsToFills(array $deals): array {
    $ins = [];
    $outs = [];

    foreach ($deals as $deal) {
        if (!is_array($deal)) continue;
        $dtype = (string) ($deal['type'] ?? '');
        if (!in_array($dtype, ['DEAL_TYPE_BUY', 'DEAL_TYPE_SELL'], true)) continue;

        $entry = (string) ($deal['entryType'] ?? $deal['entry'] ?? '');
        $posId = (string) ($deal['positionId'] ?? '');

        if ($entry === 'DEAL_ENTRY_IN' && $posId !== '') {
            if (!isset($ins[$posId]) || strcmp((string) ($deal['time'] ?? ''), (string) ($ins[$posId]['time'] ?? '')) < 0) {
                $ins[$posId] = $deal;
            }
        }
        if (in_array($entry, ['DEAL_ENTRY_OUT', 'DEAL_ENTRY_INOUT', 'DEAL_ENTRY_OUT_BY'], true)) {
            $outs[] = $deal;
        }
    }

    $fills = [];
    foreach ($outs as $out) {
        $posId = (string) ($out['positionId'] ?? '');
        $in    = $ins[$posId] ?? null;
        $exit  = (float) ($out['price'] ?? 0);
        $entry = $in ? (float) ($in['price'] ?? 0) : 0;
        if ($entry <= 0) $entry = $exit;

        $inType = (string) ($in['type'] ?? '');
        if ($inType === 'DEAL_TYPE_SELL') {
            $side = 'sell';
        } elseif ($inType === 'DEAL_TYPE_BUY') {
            $side = 'buy';
        } else {
            $side = (($out['type'] ?? '') === 'DEAL_TYPE_SELL') ? 'buy' : 'sell';
        }

        $fills[] = [
            'id'          => (string) ($out['id'] ?? ''),
            'symbol'      => $out['symbol'] ?? '',
            'side'        => $side,
            'entry_price' => $entry,
            'exit_price'  => $exit,
            'quantity'    => (float) ($out['volume'] ?? 1),
            'closed_at'   => $out['time'] ?? date('c'),
            'profit'      => $out['profit'] ?? null,
        ];
    }
    return $fills;
}

function fetchClosedTrades(string $brokerKey, array $creds, int $lookbackDays, ?string $accountId, ?string $region = null): array {
    if (!in_array($brokerKey, ['metatrader5', 'metatrader4'], true)) {
        return [];
    }
    if (empty($accountId) || metaApiToken() === '') {
        throw new RuntimeException('MetaTrader is not fully connected yet (missing account id). Reconnect on Profile.');
    }

    $startTime = gmdate('Y-m-d\TH:i:s.000\Z', strtotime("-{$lookbackDays} days"));
    $endTime   = gmdate('Y-m-d\TH:i:s.000\Z', time() + 86400);
    $path = '/users/current/accounts/' . rawurlencode($accountId)
        . '/history-deals/time/' . rawurlencode($startTime) . '/' . rawurlencode($endTime)
        . '?limit=1000';

    $lastError = null;
    foreach (clientApiHosts($region) as $host) {
        try {
            $deals = metaApiRequest($host . $path);
            if (isset($deals['deals']) && is_array($deals['deals'])) {
                $deals = $deals['deals'];
            }
            if (!is_array($deals)) $deals = [];
            return metaDealsToFills($deals);
        } catch (Throwable $e) {
            $lastError = $e;
            continue;
        }
    }

    throw $lastError ?? new RuntimeException('Could not reach MetaApi client servers.');
}

function listBrokerStatus(PDO $db, int $userId): array {
    global $BROKER_CREDENTIALS;
    bootstrapUserAccounts($db, $userId);
    $accounts = [];
    foreach (listTradingAccounts($db, $userId) as $row) {
        $accounts[] = [
            'id'            => (int) $row['id'],
            'display_name'  => $row['display_name'],
            'account_type'  => $row['account_type'],
            'broker_login'  => $row['broker_login'],
            'status'        => $row['status'],
            'last_sync_at'  => $row['last_sync_at'],
            'last_error'    => $row['last_error'],
            'trade_count'   => (int) ($row['trade_count'] ?? 0),
            'connected'     => !empty($row['metaapi_id']) && $row['account_type'] !== 'manual',
        ];
    }
    $brokers = [];
    foreach ($BROKER_CREDENTIALS as $key => $cfg) {
        $brokers[] = [
            'key'        => $key,
            'label'      => $cfg['label'],
            'configured' => !empty($cfg['enabled']) && BROKER_SYNC_ENABLED,
        ];
    }
    return $accounts;
}

function syncUserBrokers(PDO $db, int $userId, ?int $onlyAccountId = null): array {
    global $BROKER_CREDENTIALS;

    bootstrapUserAccounts($db, $userId);

    if (!BROKER_SYNC_ENABLED) {
        return ['imported' => 0, 'skipped' => 0, 'accounts' => [], 'notice' => 'Broker sync is disabled.'];
    }

    $imported = 0;
    $skipped  = 0;
    $perAccount = [];

    $insert = $db->prepare('
        INSERT INTO trades (user_id, account_id, asset_name, trade_type, entry_price, exit_price, quantity, trade_date, notes, emotion, source, broker_trade_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)
    ');

    $sql = "SELECT * FROM trading_accounts WHERE user_id = ? AND account_type != 'manual' AND metaapi_id IS NOT NULL AND metaapi_id != ''";
    $params = [$userId];
    if ($onlyAccountId) {
        $sql .= ' AND id = ?';
        $params[] = $onlyAccountId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $connections = $stmt->fetchAll();

    foreach ($connections as $userConn) {
        $key = (string) ($userConn['account_type'] ?? 'metatrader5');
        if (!isset($BROKER_CREDENTIALS[$key])) {
            $key = 'metatrader5';
        }
        $creds = $BROKER_CREDENTIALS[$key] ?? [];
        $taId  = (int) $userConn['id'];

        if (empty($creds['enabled'])) {
            $perAccount[$taId] = ['imported' => 0, 'skipped' => 0, 'status' => 'disabled', 'name' => $userConn['display_name']];
            continue;
        }

        $error = null;
        $fills = [];
        try {
            $fills = fetchClosedTrades(
                $key,
                $creds,
                BROKER_SYNC_LOOKBACK_DAYS,
                (string) $userConn['metaapi_id'],
                $userConn['region'] ?? null
            );
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $bImported = 0;
        $bSkipped  = 0;

        foreach ($fills as $fill) {
            $mapped = mapBrokerFill($fill);
            if ($mapped['asset_name'] === '' || $mapped['entry_price'] <= 0 || $mapped['exit_price'] <= 0 || $mapped['broker_trade_id'] === '') {
                $bSkipped++;
                continue;
            }

            $exists = $db->prepare('SELECT id, account_id FROM trades WHERE user_id = ? AND broker_trade_id = ? LIMIT 1');
            $exists->execute([$userId, $mapped['broker_trade_id']]);
            $existing = $exists->fetch();
            if ($existing) {
                if (empty($existing['account_id'])) {
                    $db->prepare('UPDATE trades SET account_id = ? WHERE id = ? AND user_id = ?')
                       ->execute([$taId, $existing['id'], $userId]);
                    $bImported++;
                    continue;
                }
                $bSkipped++;
                continue;
            }

            try {
                $insert->execute([
                    $userId,
                    $taId,
                    $mapped['asset_name'],
                    $mapped['trade_type'],
                    $mapped['entry_price'],
                    $mapped['exit_price'],
                    $mapped['quantity'] > 0 ? $mapped['quantity'] : 1,
                    $mapped['trade_date'],
                    $mapped['notes'],
                    $key,
                    $mapped['broker_trade_id'],
                ]);
                $bImported++;
            } catch (PDOException $e) {
                $bSkipped++;
            }
        }

        $db->prepare('
            UPDATE trading_accounts
            SET last_sync_at = NOW(), last_error = ?, status = ?
            WHERE id = ? AND user_id = ?
        ')->execute([$error, $error ? 'error' : 'connected', $taId, $userId]);

        $imported += $bImported;
        $skipped  += $bSkipped;
        $perAccount[$taId] = [
            'id'       => $taId,
            'name'     => $userConn['display_name'],
            'imported' => $bImported,
            'skipped'  => $bSkipped,
            'status'   => $error ? 'error' : 'synced',
            'error'    => $error,
        ];
    }

    $notice = null;
    if (!$connections) {
        $notice = 'No MetaTrader accounts are connected yet.';
    } elseif ($imported === 0 && $skipped === 0) {
        $notice = 'Connected, but MetaApi returned no closed trades in the last ' . BROKER_SYNC_LOOKBACK_DAYS . ' days. Open positions are not imported.';
    }

    return compact('imported', 'skipped') + ['accounts' => $perAccount, 'brokers' => $perAccount, 'notice' => $notice];
}

$db = getDB();
ensureBrokerTables($db);
ensureTradingAccountSchema($db);

if ($isCli) {
    $opts   = getopt('', ['user:', 'secret:']);
    $secret = $opts['secret'] ?? '';
    $userId = (int) ($opts['user'] ?? 0);
    if ($secret !== BROKER_SYNC_SECRET || strpos(BROKER_SYNC_SECRET, 'YOUR_') !== false) {
        fwrite(STDERR, "Invalid or placeholder cron secret.\n");
        exit(1);
    }
    if ($userId < 1) {
        fwrite(STDERR, "Pass --user=<id>\n");
        exit(1);
    }
    $result = syncUserBrokers($db, $userId);
    echo json_encode(['success' => true, 'data' => $result], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

$userId = requireAuth();
bootstrapUserAccounts($db, $userId);
$action = $_POST['action'] ?? $_GET['action'] ?? 'status';

if ($action === 'status') {
    $accounts = listBrokerStatus($db, $userId);
    brokerJson(true, 'Broker sync status.', [
        'enabled'  => BROKER_SYNC_ENABLED,
        'accounts' => $accounts,
        'brokers'  => $accounts,
        'active_id'=> currentAccountId(),
    ]);
}

if ($action === 'sync') {
    $onlyId = (int) ($_POST['account_id'] ?? $_GET['account_id'] ?? 0);
    $result = syncUserBrokers($db, $userId, $onlyId > 0 ? $onlyId : null);
    $msg = $result['notice'] ?: ('Imported ' . $result['imported'] . ' trade(s)' . ($result['skipped'] ? ', skipped ' . $result['skipped'] : '') . '.');
    $errors = [];
    foreach ($result['brokers'] as $b) {
        if (!empty($b['error'])) $errors[] = ($b['name'] ?? 'Account') . ': ' . $b['error'];
    }
    if ($errors && $result['imported'] === 0) {
        brokerJson(false, implode(' ', $errors), $result);
    }
    brokerJson(true, $msg, $result);
}

if ($action === 'connect_broker') {
    $brokerKey = $_POST['broker_key'] ?: 'metatrader5';
    $server    = trim($_POST['server'] ?? '');
    $login     = trim($_POST['login'] ?? '');
    $password  = $_POST['password'] ?? '';
    $platform  = $_POST['platform'] ?? 'mt5';
    $nickname  = trim($_POST['display_name'] ?? '');

    if ($platform === 'mt4') {
        $brokerKey = 'metatrader4';
    } elseif ($brokerKey !== 'metatrader4') {
        $brokerKey = 'metatrader5';
        $platform  = 'mt5';
    }

    if ($server === '' || $login === '' || $password === '') {
        brokerJson(false, 'Please fill in all broker credentials.');
    }

    if ($nickname === '') {
        $nickname = ($platform === 'mt4' ? 'MT4' : 'MT5') . ' · ' . $login;
    }

    try {
        $account = createMetaApiAccount([
            'name'     => 'TradeLens ' . $userId . ' ' . $login,
            'login'    => $login,
            'password' => $password,
            'server'   => $server,
            'platform' => $platform,
        ]);
        $accountId = (string) ($account['id'] ?? '');
        $region    = (string) ($account['region'] ?? '');

        if ($accountId === '') {
            brokerJson(false, 'Failed to provision the MetaTrader account. Check login, investor/trading password, and exact server name.');
        }

        $ins = $db->prepare('
            INSERT INTO trading_accounts
                (user_id, display_name, account_type, broker_login, broker_server, metaapi_id, region, status, last_sync_at, last_error)
            VALUES (?, ?, ?, ?, ?, ?, ?, "connected", NOW(), NULL)
        ');
        $ins->execute([$userId, $nickname, $brokerKey, $login, $server, $accountId, $region ?: null]);
        $taId = (int) $db->lastInsertId();
        $_SESSION['active_account_id'] = $taId;

        $result = syncUserBrokers($db, $userId, $taId);
        $imported = (int) ($result['imported'] ?? 0);
        $msg = 'Connected "' . $nickname . '". Switch accounts from the top bar to view its own dashboard.';
        if ($imported > 0) {
            $msg .= ' Imported ' . $imported . ' closed trade(s).';
        } else {
            $msg .= ' No closed trades in the last ' . BROKER_SYNC_LOOKBACK_DAYS . ' days yet. Open positions are not synced.';
        }
        brokerJson(true, $msg, $result + ['account_id' => $taId]);
    } catch (Exception $e) {
        brokerJson(false, 'Connection error: ' . $e->getMessage());
    }
}

brokerJson(false, 'Invalid action.');
