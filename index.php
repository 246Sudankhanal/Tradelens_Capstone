<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$loggedOut = isset($_GET['msg']) && $_GET['msg'] === 'logged_out';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — TradeLens</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">

<div class="auth-card">
    <div class="auth-brand">
        <div class="logo-icon"><i class="fa-solid fa-chart-line"></i></div>
        <h1>TradeLens</h1>
        <p>Your personal trading journal</p>
    </div>

    <h2 class="auth-title">Welcome back</h2>
    <p class="auth-subtitle">Sign in to your account</p>

    <?php if ($loggedOut): ?>
    <div class="alert alert-info show">You have been logged out successfully.</div>
    <?php endif; ?>

    <div class="alert alert-error" id="error-msg"></div>

    <form id="login-form" novalidate>
        <div class="form-group">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block" id="login-btn">
            Sign In
        </button>
    </form>

    <p class="auth-link">Don't have an account? <a href="<?= BASE_URL ?>/register.php">Create one</a></p>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('login-btn');
    const errEl = document.getElementById('error-msg');
    errEl.classList.remove('show');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Signing in...';

    const data = new FormData(this);
    data.append('action', 'login');

    try {
        const res  = await fetch('<?= BASE_URL ?>/api/auth.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            window.location.href = json.data.redirect;
        } else {
            errEl.textContent = json.message;
            errEl.classList.add('show');
            btn.disabled = false;
            btn.innerHTML = 'Sign In';
        }
    } catch {
        errEl.textContent = 'Network error. Please try again.';
        errEl.classList.add('show');
        btn.disabled = false;
        btn.innerHTML = 'Sign In';
    }
});
</script>
</body>
</html>
