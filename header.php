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
        <a href="<?= BASE_URL ?>/logout.php" class="logout-btn" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
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
            <span class="topbar-date" id="topbar-date"></span>
        </div>
    </header>
    <main class="main-content">
