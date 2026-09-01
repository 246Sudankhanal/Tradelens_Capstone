<?php
/**
 * Multi-account trading journals: one dashboard per synced (or manual) account.
 */

function ensureTradingAccountSchema(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS trading_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            display_name VARCHAR(120) NOT NULL,
            account_type VARCHAR(40) NOT NULL DEFAULT 'manual',
            broker_login VARCHAR(50) NULL,
            broker_server VARCHAR(120) NULL,
            metaapi_id VARCHAR(120) NULL,
            region VARCHAR(50) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            last_sync_at DATETIME NULL,
            last_error TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ta_user (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    try {
        $db->exec('ALTER TABLE trades ADD COLUMN account_id INT NULL');
    } catch (PDOException $e) { /* exists */ }
    try {
        $db->exec('ALTER TABLE trades ADD INDEX idx_trades_account (account_id)');
    } catch (PDOException $e) { /* exists */ }
    try {
        $db->exec('ALTER TABLE trades ADD UNIQUE KEY uniq_account_broker_trade (user_id, account_id, broker_trade_id)');
    } catch (PDOException $e) { /* exists or duplicates */ }
}

function ensureManualAccount(PDO $db, int $userId): int {
    ensureTradingAccountSchema($db);
    $stmt = $db->prepare("SELECT id FROM trading_accounts WHERE user_id = ? AND account_type = 'manual' ORDER BY id ASC LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $id = (int) $row['id'];
        $db->prepare('UPDATE trades SET account_id = ? WHERE user_id = ? AND account_id IS NULL AND (source IS NULL OR source = ? OR source = ?)')
           ->execute([$id, $userId, 'manual', '']);
        return $id;
    }
    $db->prepare("INSERT INTO trading_accounts (user_id, display_name, account_type, status) VALUES (?, ?, 'manual', 'active')")
       ->execute([$userId, 'Manual journal']);
    $id = (int) $db->lastInsertId();
    try {
        $db->prepare('UPDATE trades SET account_id = ? WHERE user_id = ? AND account_id IS NULL')->execute([$id, $userId]);
    } catch (PDOException $e) { /* ignore */ }
    return $id;
}

function migrateLegacyBrokerConnections(PDO $db, int $userId): void {
    try {
        $stmt = $db->prepare('SELECT * FROM broker_connections WHERE user_id = ?');
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        return;
    }
    foreach ($stmt->fetchAll() as $row) {
        $meta = (string) ($row['account_id'] ?? '');
        if ($meta === '') continue;
        $exists = $db->prepare('SELECT id FROM trading_accounts WHERE user_id = ? AND metaapi_id = ? LIMIT 1');
        $exists->execute([$userId, $meta]);
        if ($exists->fetch()) continue;
        $key = (string) ($row['broker_key'] ?? 'metatrader5');
        $name = ($key === 'metatrader4' ? 'MetaTrader 4' : 'MetaTrader 5') . ' account';
        $db->prepare('
            INSERT INTO trading_accounts (user_id, display_name, account_type, broker_login, metaapi_id, region, status, last_sync_at, last_error)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([
            $userId,
            $name,
            $key,
            null,
            $meta,
            $row['region'] ?? null,
            $row['status'] ?? 'connected',
            $row['last_sync_at'] ?? null,
            $row['last_error'] ?? null,
        ]);
        $newId = (int) $db->lastInsertId();
        if ($newId > 0) {
            $db->prepare('UPDATE trades SET account_id = ? WHERE user_id = ? AND source = ? AND account_id IS NULL')
               ->execute([$newId, $userId, $key]);
        }
    }
}

function bootstrapUserAccounts(PDO $db, int $userId): void {
    ensureTradingAccountSchema($db);
    migrateLegacyBrokerConnections($db, $userId);
    ensureManualAccount($db, $userId);
}

function currentAccountId(): ?int {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $raw = $_SESSION['active_account_id'] ?? 'all';
    if ($raw === 'all' || $raw === null || $raw === '' || $raw === '0' || $raw === 0) {
        return null;
    }
    return (int) $raw;
}

function accountSql(string $column = 'account_id'): string {
    $id = currentAccountId();
    if ($id === null) return '';
    return ' AND ' . $column . ' = ' . (int) $id;
}

function writeAccountId(PDO $db, int $userId): int {
    $current = currentAccountId();
    if ($current) {
        $own = $db->prepare('SELECT id FROM trading_accounts WHERE id = ? AND user_id = ?');
        $own->execute([$current, $userId]);
        if ($own->fetch()) return $current;
    }
    return ensureManualAccount($db, $userId);
}

function writeAccountMeta(PDO $db, int $userId): array {
    $id = writeAccountId($db, $userId);
    $row = ownedTradingAccount($db, $userId, $id);
    return [
        'id'   => $id,
        'name' => $row['display_name'] ?? 'Manual journal',
        'viewing_all' => currentAccountId() === null,
    ];
}

function createManualJournal(PDO $db, int $userId, string $name): int {
    ensureTradingAccountSchema($db);
    $name = trim($name);
    if ($name === '') {
        $name = 'Manual journal';
    }
    $db->prepare("INSERT INTO trading_accounts (user_id, display_name, account_type, status) VALUES (?, ?, 'manual', 'active')")
       ->execute([$userId, $name]);
    return (int) $db->lastInsertId();
}

function defaultManualAccountId(PDO $db, int $userId): int {
    return ensureManualAccount($db, $userId);
}

function listTradingAccounts(PDO $db, int $userId): array {
    bootstrapUserAccounts($db, $userId);
    $stmt = $db->prepare('
        SELECT a.*,
            (SELECT COUNT(*) FROM trades t WHERE t.user_id = a.user_id AND t.account_id = a.id) AS trade_count
        FROM trading_accounts a
        WHERE a.user_id = ?
        ORDER BY a.account_type = \'manual\' DESC, a.created_at ASC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function activeAccountLabel(array $accounts): string {
    $id = currentAccountId();
    if ($id === null) return 'All accounts';
    foreach ($accounts as $a) {
        if ((int) $a['id'] === $id) return (string) $a['display_name'];
    }
    return 'All accounts';
}

function ownedTradingAccount(PDO $db, int $userId, int $id): ?array {
    $stmt = $db->prepare('SELECT * FROM trading_accounts WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}
