// TradeLens — Shared JavaScript

// Live date in topbar
(function () {
    const el = document.getElementById('topbar-date');
    if (!el) return;
    const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    el.textContent = new Date().toLocaleDateString('en-US', opts);
})();

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
