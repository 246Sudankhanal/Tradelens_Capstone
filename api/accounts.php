<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/trading_accounts.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = requireAuth();
$db     = getDB();
bootstrapUserAccounts($db, $userId);

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'list') {
    $accounts = listTradingAccounts($db, $userId);
    jsonResponse(true, '', [
        'active_id' => currentAccountId(),
        'accounts'  => $accounts,
    ]);
}

if ($action === 'select') {
    $id = $_POST['id'] ?? $_GET['id'] ?? 'all';
    if ($id === 'all' || $id === '' || $id === '0') {
        $_SESSION['active_account_id'] = 'all';
        jsonResponse(true, 'Showing all accounts.', ['active_id' => null]);
    }
    $id = (int) $id;
    if (!ownedTradingAccount($db, $userId, $id)) {
        jsonResponse(false, 'Account not found.');
    }
    $_SESSION['active_account_id'] = $id;
    jsonResponse(true, 'Account switched.', ['active_id' => $id]);
}

if ($action === 'create_manual') {
    $name = trim($_POST['display_name'] ?? '');
    if ($name === '') jsonResponse(false, 'Give this journal a name.');
    $id = createManualJournal($db, $userId, $name);
    $_SESSION['active_account_id'] = $id;
    jsonResponse(true, 'Manual journal created. Add and import trades go here until you switch.', ['id' => $id]);
}

if ($action === 'rename') {
    $id   = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['display_name'] ?? '');
    if ($name === '') jsonResponse(false, 'Name is required.');
    if (!ownedTradingAccount($db, $userId, $id)) jsonResponse(false, 'Account not found.');
    $db->prepare('UPDATE trading_accounts SET display_name = ? WHERE id = ? AND user_id = ?')->execute([$name, $id, $userId]);
    jsonResponse(true, 'Account renamed.');
}

if ($action === 'disconnect') {
    $id = (int) ($_POST['id'] ?? 0);
    $acc = ownedTradingAccount($db, $userId, $id);
    if (!$acc) jsonResponse(false, 'Account not found.');
    $isManual = ($acc['account_type'] ?? '') === 'manual';
    if ($isManual) {
        $count = $db->prepare("SELECT COUNT(*) FROM trading_accounts WHERE user_id = ? AND account_type = 'manual'");
        $count->execute([$userId]);
        if ((int) $count->fetchColumn() <= 1) {
            jsonResponse(false, 'Keep at least one manual journal.');
        }
        $fallback = $db->prepare("SELECT id FROM trading_accounts WHERE user_id = ? AND account_type = 'manual' AND id != ? ORDER BY id ASC LIMIT 1");
        $fallback->execute([$userId, $id]);
        $fallbackId = (int) $fallback->fetchColumn();
        $db->prepare('UPDATE trades SET account_id = ? WHERE user_id = ? AND account_id = ?')->execute([$fallbackId, $userId, $id]);
        $db->prepare('DELETE FROM trading_accounts WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
        if (currentAccountId() === $id) {
            $_SESSION['active_account_id'] = $fallbackId;
        }
        jsonResponse(true, 'Journal removed. Its trades were moved to another manual journal.');
    }
    $manualId = ensureManualAccount($db, $userId);
    $db->prepare('UPDATE trades SET account_id = ? WHERE user_id = ? AND account_id = ?')->execute([$manualId, $userId, $id]);
    $db->prepare('DELETE FROM trading_accounts WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
    if (currentAccountId() === $id) {
        $_SESSION['active_account_id'] = 'all';
    }
    jsonResponse(true, 'Account disconnected. Its trades were moved to your default Manual journal.');
}

jsonResponse(false, 'Invalid action.');
