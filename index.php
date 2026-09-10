<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$loggedOut = ($_GET['msg'] ?? '') === 'logged_out';
$base = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeLens — Trading journal with analytics, broker sync, and AI</title>
    <meta name="description" content="TradeLens is a personal trading journal. Log trades, import CSVs, sync MetaTrader, and review P&amp;L with an AI copilot.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="landing-body">

<header class="lp-nav">
    <a href="<?= BASE_URL ?>/index.php" class="lp-brand">
        <span class="lp-logo"><i class="fa-solid fa-chart-line"></i></span>
        TradeLens
    </a>
    <nav class="lp-links">
        <a href="#features">Features</a>
        <a href="#how">How it works</a>
        <a href="<?= BASE_URL ?>/login.php">Sign in</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Get started</a>
    </nav>
</header>

<?php if ($loggedOut): ?>
<div class="lp-banner">You have been signed out. <a href="<?= BASE_URL ?>/login.php">Sign in again</a></div>
<?php endif; ?>

<section class="lp-hero">
    <div class="lp-hero-copy">
        <p class="lp-kicker">Personal trading journal</p>
        <h1>See your edge clearly — not just your last trade.</h1>
        <p class="lp-lead">Log fills, import CSVs, or sync MetaTrader. Each account gets its own dashboard. An AI copilot answers from <em>your</em> stats, not generic market talk.</p>
        <div class="lp-hero-actions">
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Create free account</a>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">Sign in</a>
        </div>
        <ul class="lp-pills">
            <li>Multi-account books</li>
            <li>MetaTrader sync</li>
            <li>CSV import</li>
            <li>Journal AI</li>
        </ul>
    </div>
    <div class="lp-hero-card" aria-hidden="true">
        <div class="lp-fake-top">
            <span></span><span></span><span></span>
            <strong>Dashboard · Live book</strong>
        </div>
        <div class="lp-metrics">
            <div>
                <small>Win rate</small>
                <b>54.2%</b>
            </div>
            <div>
                <small>Net P&amp;L</small>
                <b class="pos">+$1,284</b>
            </div>
            <div>
                <small>Trades</small>
                <b>86</b>
            </div>
        </div>
        <div class="lp-bars">
            <i style="height:42%"></i>
            <i style="height:68%"></i>
            <i style="height:35%"></i>
            <i style="height:80%"></i>
            <i style="height:58%"></i>
            <i style="height:90%"></i>
            <i style="height:48%"></i>
        </div>
        <p class="lp-fake-note"><i class="fa-solid fa-robot"></i> “Your Friday XAUUSD shorts are dragging win rate. Size down after two losses.”</p>
    </div>
</section>

<section class="lp-section" id="features">
    <h2>Built for how traders actually review</h2>
    <p class="lp-section-lead">One place for execution, psychology notes, and account-level stats.</p>
    <div class="lp-grid">
        <article class="lp-feature">
            <i class="fa-solid fa-book-open"></i>
            <h3>Trade journal</h3>
            <p>Buy/sell, size, notes, and emotion tags. Filter and sort the book the way you review a session.</p>
        </article>
        <article class="lp-feature">
            <i class="fa-solid fa-chart-area"></i>
            <h3>Analytics</h3>
            <p>Win rate, net P&amp;L, heatmaps, weekday and asset breakdowns so patterns show up faster than a spreadsheet.</p>
        </article>
        <article class="lp-feature">
            <i class="fa-solid fa-arrows-rotate"></i>
            <h3>Broker sync</h3>
            <p>Connect MetaTrader 4/5 and pull closed trades. Keep live, demo, and prop books on separate dashboards.</p>
        </article>
        <article class="lp-feature">
            <i class="fa-solid fa-file-import"></i>
            <h3>CSV import</h3>
            <p>Drop a file into the account you have selected. Imports stay on that dashboard — they are not moved to Manual by themselves.</p>
        </article>
        <article class="lp-feature">
            <i class="fa-solid fa-layer-group"></i>
            <h3>Manual dashboards</h3>
            <p>Create extra journals for paper, backtests, or a challenge. Switch them from the top bar like a broker account.</p>
        </article>
        <article class="lp-feature">
            <i class="fa-solid fa-robot"></i>
            <h3>AI copilot</h3>
            <p>Ask about win rate, emotions, and recent fills. Answers stay tied to the journal you are viewing.</p>
        </article>
    </div>
</section>

<section class="lp-section lp-how" id="how">
    <h2>How it works</h2>
    <ol class="lp-steps">
        <li>
            <span>1</span>
            <div>
                <h3>Create an account</h3>
                <p>Email or Google. You land on a dashboard with a default manual journal.</p>
            </div>
        </li>
        <li>
            <span>2</span>
            <div>
                <h3>Add trades your way</h3>
                <p>Log them, import CSV, or sync MetaTrader. Switch books when you want a clean view.</p>
            </div>
        </li>
        <li>
            <span>3</span>
            <div>
                <h3>Review and ask</h3>
                <p>Charts show the numbers. The assistant talks through behavior — without telling you what to buy.</p>
            </div>
        </li>
    </ol>
</section>

<section class="lp-cta">
    <h2>Start the journal you will actually keep.</h2>
    <p>Free to create an account. Your trades stay on your books.</p>
    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Get started</a>
</section>

<footer class="lp-foot">
    <span>TradeLens</span>
    <span>Capstone trading journal · not financial advice</span>
    <a href="<?= BASE_URL ?>/login.php">Sign in</a>
</footer>

</body>
</html>
