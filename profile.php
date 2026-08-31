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

    <!-- Broker auto-sync -->

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-arrows-rotate" style="color:var(--accent);margin-right:8px"></i>Broker auto-sync</h3>
            <span class="broker-card-status" id="broker-global-status">Ready</span>
        </div>
        <div class="card-body">
            <p class="text-muted text-sm mb-4">Connect your MetaTrader account via MetaApi to pull closed trades automatically.</p>
            
            <div class="alert alert-error" id="broker-conn-error" style="display:none; margin-bottom: 12px;"></div>
            <div class="alert alert-success" id="broker-conn-success" style="display:none; margin-bottom: 12px;"></div>

            <!-- Broker Connection Form -->
            <form id="broker-connect-form" novalidate style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="connect_broker">
                <input type="hidden" name="broker_key" value="metatrader5">
                <input type="hidden" name="platform" value="mt5">

                <div class="form-group">
                    <label class="form-label">Broker Server</label>
                    <input type="text" name="server" class="form-control" placeholder="e.g. Exness-Trial" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Account Login ID</label>
                    <input type="text" name="login" class="form-control" placeholder="e.g. 12345678" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Trading Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="broker-connect-btn">
                    <i class="fa-solid fa-plug"></i> Connect MetaTrader Account
                </button>
            </form>

            <div class="broker-list" id="broker-list">
                <div class="text-muted text-sm">Loading connections…</div>
            </div>
            
            <button type="button" class="btn btn-outline btn-block mt-4" id="broker-sync-btn" onclick="runBrokerSync()">
                <i class="fa-solid fa-cloud-arrow-down"></i> Sync now
            </button>
            <div class="alert alert-info" id="broker-msg" style="margin-top:14px;margin-bottom:0"></div>
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
// Broker connection form handler
document.getElementById('broker-connect-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('broker-connect-btn');
    const err = document.getElementById('broker-conn-error');
    const suc = document.getElementById('broker-conn-success');
    
    err.style.display = 'none'; 
    suc.style.display = 'none';
    btn.disabled = true; 
    btn.innerHTML = '<span class="spinner"></span> Connecting to MetaApi...';

    const data = new FormData(this);

    try {
        const res  = await fetch(BASE + '/api/broker_sync.php', { method: 'POST', body: data });
        const json = await res.json();
        
        if (json.success) {
            suc.textContent = json.message || 'Account successfully connected!';
            suc.style.display = 'block';
            this.reset();
            showToast('Broker connected successfully', 'success');
            loadBrokerStatus();
            loadStats();
        } else {
            err.textContent = json.message || 'Failed to connect account.';
            err.style.display = 'block';
        }
    } catch (errCatch) {
        err.textContent = 'Network error occurred.';
        err.style.display = 'block';
    } finally {
        btn.disabled = false; 
        btn.innerHTML = '<i class="fa-solid fa-plug"></i> Connect MetaTrader Account';
    }
});

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
loadBrokerStatus();

async function loadBrokerStatus() {
    try {
        const res  = await fetch(BASE + '/api/broker_sync.php?action=status');
        const json = await res.json();
        if (!json.success) return;
        const enabled = json.data.enabled;
        document.getElementById('broker-global-status').textContent = enabled ? 'Ready' : 'Placeholders only';
        const list = document.getElementById('broker-list');
        list.innerHTML = json.data.brokers.map(b => `
            <div class="broker-row">
                <i class="fa-solid fa-plug"></i>
                <div style="flex:1;min-width:0">
                    <div class="broker-name">${escHtml(b.label)}</div>
                    <div class="broker-sub">${escHtml(b.status)}${b.last_sync_at ? ' · last sync ' + escHtml(b.last_sync_at) : ''}</div>
                    ${b.last_error ? `<div class="broker-sub" style="color:var(--red)">${escHtml(b.last_error)}</div>` : ''}
                </div>
            </div>
        `).join('');
    } catch (e) {}
}

async function runBrokerSync() {
    const btn = document.getElementById('broker-sync-btn');
    const msg = document.getElementById('broker-msg');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Syncing...';
    msg.classList.remove('show');
    try {
        const data = new FormData();
        data.append('action', 'sync');
        const res  = await fetch(BASE + '/api/broker_sync.php', { method: 'POST', body: data });
        const json = await res.json();
        msg.textContent = json.message;
        msg.className = 'alert ' + (json.success ? 'alert-info' : 'alert-error') + ' show';
        loadBrokerStatus();
        loadStats();
    } catch {
        msg.textContent = 'Network error.';
        msg.className = 'alert alert-error show';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Sync now';
    }
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
