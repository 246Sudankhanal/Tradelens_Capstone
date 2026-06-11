<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — TradeLens</title>
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

    <h2 class="auth-title">Create account</h2>
    <p class="auth-subtitle">Start tracking your trades today</p>

    <div class="alert alert-error"   id="error-msg"></div>
    <div class="alert alert-success" id="success-msg"></div>

    <form id="register-form" novalidate>
        <div class="form-group">
            <label class="form-label">Full name</label>
            <input type="text" name="name" class="form-control" placeholder="John Doe" required autocomplete="name">
        </div>
        <div class="form-group">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div class="form-group">
            <label class="form-label">Password <span class="text-muted text-sm">(min. 6 chars)</span></label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block" id="register-btn">
            Create Account
        </button>
    </form>

    <p class="auth-link">Already have an account? <a href="<?= BASE_URL ?>/index.php">Sign in</a></p>
</div>

<script>
document.getElementById('register-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn  = document.getElementById('register-btn');
    const errEl = document.getElementById('error-msg');
    const sucEl = document.getElementById('success-msg');
    errEl.classList.remove('show');
    sucEl.classList.remove('show');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Creating account...';

    const data = new FormData(this);
    data.append('action', 'register');

    try {
        const res  = await fetch('<?= BASE_URL ?>/api/auth.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            sucEl.textContent = json.message + ' Redirecting...';
            sucEl.classList.add('show');
            this.reset();
            setTimeout(() => window.location.href = '<?= BASE_URL ?>/index.php', 1800);
        } else {
            errEl.textContent = json.message;
            errEl.classList.add('show');
        }
    } catch {
        errEl.textContent = 'Network error. Please try again.';
        errEl.classList.add('show');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Create Account';
    }
});
</script>
</body>
</html>
