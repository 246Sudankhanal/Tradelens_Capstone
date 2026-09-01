<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'TradeLens') ?> — TradeLens</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="app-body">
<script>
window.readApiJson = async function (res) {
    const text = await res.text();
    const start = text.indexOf('{');
    const payload = start >= 0 ? text.slice(start) : text;
    return JSON.parse(payload);
};
</script>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-chart-line"></i></div>
        <span class="brand-name">TradeLens</span>
    </div>

    <div class="sidebar-menu">
        <a href="<?= BASE_URL ?>/dashboard.php" class="menu-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/trades.php" class="menu-item <?= ($activePage ?? '') === 'trades' ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Trade Journal</span>
        </a>
        <a href="<?= BASE_URL ?>/profile.php" class="menu-item <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-circle"></i>
            <span>Profile</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                <div class="user-role">Trader</div>
            </div>
        </div>
        <button type="button" class="signout-btn" onclick="openLogoutModal()" title="Sign out">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Sign out</span>
        </button>
    </div>
</nav>

<div class="app-overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="app-content">
    <header class="topbar">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'TradeLens') ?></h1>
        <div class="topbar-right">
            <?php
            $headerAccounts = [];
            $headerActiveId = null;
            try {
                require_once __DIR__ . '/trading_accounts.php';
                $headerDb = getDB();
                $headerAccounts = listTradingAccounts($headerDb, (int) $_SESSION['user_id']);
                $headerActiveId = currentAccountId();
            } catch (Throwable $e) {
                $headerAccounts = [];
            }
            ?>
            <label class="account-switcher" title="Switch trading account">
                <i class="fa-solid fa-layer-group"></i>
                <select id="account-switcher" aria-label="Trading account" data-current="<?= $headerActiveId === null ? 'all' : (int) $headerActiveId ?>">
                    <option value="all"<?= $headerActiveId === null ? ' selected' : '' ?>>All accounts</option>
                    <?php foreach ($headerAccounts as $acc): ?>
                        <option value="<?= (int) $acc['id'] ?>"<?= $headerActiveId === (int) $acc['id'] ? ' selected' : '' ?>>
                            <?= htmlspecialchars($acc['display_name']) ?>
                            (<?= (int) ($acc['trade_count'] ?? 0) ?>)
                        </option>
                    <?php endforeach; ?>
                    <option value="__create__">+ New manual dashboard…</option>
                </select>
            </label>
            <button type="button" class="btn btn-outline btn-sm" id="new-dashboard-btn" title="Create a dashboard for manual trades and CSV import">
                <i class="fa-solid fa-plus"></i> New dashboard
            </button>
            <span class="topbar-date" id="topbar-date"></span>
        </div>
    </header>
    <main class="main-content">
