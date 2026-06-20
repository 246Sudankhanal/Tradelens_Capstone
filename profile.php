<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$db   = getDB();
$stmt = $db->prepare('SELECT name, email, created_at FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$pageTitle  = 'Profile';
$activePage = 'profile';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Profile Settings</h2>
        <p>Manage your account information</p>
    </div>
</div>

<div class="toast-container" id="toast-container"></div>

<div class="profile-grid">

    <!-- Profile Info Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-user" style="color:var(--accent);margin-right:8px"></i>Personal Information</h3>
        </div>
        <div class="card-body">
            <div style="text-align:center;margin-bottom:24px">
                <div style="width:72px;height:72px;background:linear-gradient(135deg,var(--accent),#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:white;margin:0 auto 12px">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div style="font-size:13px;color:var(--text-muted)">Member since <?= date('M Y', strtotime($user['created_at'])) ?></div>
            </div>

            <div class="alert alert-error"   id="profile-error"></div>
            <div class="alert alert-success" id="profile-success"></div>

            <form id="profile-form" novalidate>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="profile-btn">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-lock" style="color:var(--accent);margin-right:8px"></i>Change Password</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-error"   id="pass-error"></div>
            <div class="alert alert-success" id="pass-success"></div>

            <form id="password-form" novalidate>
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password <span class="text-muted text-sm">(min. 6 chars)</span></label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="pass-btn">
                    <i class="fa-solid fa-key"></i> Update Password
                </button>
            </form>
        </div>
    </div>

</div>

<!-- Account Stats -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-chart-bar" style="color:var(--accent);margin-right:8px"></i>Account Summary</h3>
    </div>
    <div class="card-body">
        <div class="metrics-grid" id="account-stats">
            <div class="metric-card blue">
                <div class="metric-label">Total Trades</div>
                <div class="metric-value neutral" id="as-total">—</div>
                <i class="fa-solid fa-list metric-icon"></i>
            </div>
            <div class="metric-card green">
                <div class="metric-label">Win Rate</div>
                <div class="metric-value" id="as-wr">—</div>
                <i class="fa-solid fa-trophy metric-icon"></i>
            </div>
            <div class="metric-card" id="as-profit-card">
                <div class="metric-label">Net P&L</div>
                <div class="metric-value" id="as-pnl">—</div>
                <i class="fa-solid fa-dollar-sign metric-icon"></i>
            </div>
            <div class="metric-card yellow">
                <div class="metric-label">Best Trade</div>
                <div class="metric-value positive" id="as-best">—</div>
                <i class="fa-solid fa-star metric-icon"></i>
            </div>
        </div>
    </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';

// Profile form
document.getElementById('profile-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('profile-btn');
    const err = document.getElementById('profile-error');
    const suc = document.getElementById('profile-success');
    err.classList.remove('show'); suc.classList.remove('show');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

    const data = new FormData(this);
    data.append('action', 'update_profile');

    try {
        const res  = await fetch(BASE + '/api/auth.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            suc.textContent = json.message; suc.classList.add('show');
            showToast(json.message, 'success');
        } else {
            err.textContent = json.message; err.classList.add('show');
        }
    } catch { err.textContent = 'Network error.'; err.classList.add('show'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Changes'; }
});

// Password form
document.getElementById('password-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('pass-btn');
    const err = document.getElementById('pass-error');
    const suc = document.getElementById('pass-success');
    err.classList.remove('show'); suc.classList.remove('show');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Updating...';

    const data = new FormData(this);
    data.append('action', 'change_password');

    try {
        const res  = await fetch(BASE + '/api/auth.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            suc.textContent = json.message; suc.classList.add('show');
            this.reset();
            showToast(json.message, 'success');
        } else {
            err.textContent = json.message; err.classList.add('show');
        }
    } catch { err.textContent = 'Network error.'; err.classList.add('show'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-key"></i> Update Password'; }
});

// Load account stats
async function loadStats() {
    try {
        const res  = await fetch(BASE + '/api/analytics.php');
        const json = await res.json();
        if (!json.success) return;
        const d = json.data;
        document.getElementById('as-total').textContent = d.total_trades;
        const wrEl = document.getElementById('as-wr');
        wrEl.textContent = d.win_rate + '%';
        wrEl.className   = 'metric-value ' + (d.win_rate >= 50 ? 'positive' : 'negative');
        const pnlEl = document.getElementById('as-pnl');
        const pnl   = parseFloat(d.net_profit);
        pnlEl.textContent = (pnl >= 0 ? '+' : '') + '$' + Math.abs(pnl).toFixed(2);
        pnlEl.className   = 'metric-value ' + (pnl > 0 ? 'positive' : pnl < 0 ? 'negative' : 'neutral');
        document.getElementById('as-profit-card').classList.add(pnl >= 0 ? 'green' : 'red');
        document.getElementById('as-best').textContent = '+$' + parseFloat(d.best_trade || 0).toFixed(2);
    } catch(e) {}
}

function showToast(msg, type='success') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = `<i class="fa-solid fa-${type==='success'?'circle-check':'circle-exclamation'}"></i> ${msg}`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

loadStats();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
