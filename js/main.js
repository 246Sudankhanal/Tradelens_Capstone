// TradeLens — Shared JavaScript

// Live date in topbar
(function () {
    const el = document.getElementById('topbar-date');
    if (!el) return;
    const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    el.textContent = new Date().toLocaleDateString('en-US', opts);
})();

window.readApiJson = async function (res) {
    const text = await res.text();
    const start = text.indexOf('{');
    const payload = start >= 0 ? text.slice(start) : text;
    return JSON.parse(payload);
};

async function createManualDashboard() {
    const base = window.TRADELENS_BASE || '';
    const name = prompt('Name this dashboard (manual trades and CSV import stay here):', 'My journal');
    if (!name || !name.trim()) return;
    const data = new FormData();
    data.append('action', 'create_manual');
    data.append('display_name', name.trim());
    try {
        const res = await fetch(base + '/api/accounts.php', { method: 'POST', body: data });
        const json = await (window.readApiJson ? window.readApiJson(res) : res.json());
        if (!json.success) {
            alert(json.message || 'Could not create dashboard.');
            return;
        }
        window.location.href = base + '/dashboard.php';
    } catch (e) {
        alert('Could not create dashboard. Try again.');
    }
}

document.getElementById('new-dashboard-btn')?.addEventListener('click', createManualDashboard);

document.getElementById('account-switcher')?.addEventListener('change', async function () {
    const base = window.TRADELENS_BASE || '';
    if (this.value === '__create__') {
        this.value = this.getAttribute('data-current') || 'all';
        createManualDashboard();
        return;
    }
    const data = new FormData();
    data.append('action', 'select');
    data.append('id', this.value);
    this.disabled = true;
    try {
        await fetch(base + '/api/accounts.php', { method: 'POST', body: data });
        window.location.reload();
    } catch (e) {
        this.disabled = false;
        alert('Could not switch account. Try again.');
    }
});

// Mobile sidebar toggle
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('open');
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
}

// Close sidebar on resize
window.addEventListener('resize', function () {
    if (window.innerWidth > 768) closeSidebar();
});

// Sign-out confirmation
function openLogoutModal() {
    const modal = document.getElementById('logout-modal');
    if (!modal) return;
    modal.classList.add('open');
    closeSidebar();
}

function closeLogoutModal() {
    document.getElementById('logout-modal')?.classList.remove('open');
}

document.getElementById('logout-modal')?.addEventListener('click', function (e) {
    if (e.target === this) closeLogoutModal();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLogoutModal();
});
