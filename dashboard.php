<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Dashboard</h2>
        <p>Overview of your trading performance</p>
    </div>
    <a href="<?= BASE_URL ?>/trades.php" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add Trade
    </a>
</div>

<!-- Metric Cards -->
<div class="metrics-grid" id="metrics-grid">
    <div class="metric-card blue">
        <div class="metric-label">Total Trades</div>
        <div class="metric-value neutral" id="m-total">—</div>
        <div class="metric-sub">All recorded trades</div>
        <i class="fa-solid fa-list metric-icon"></i>
    </div>
    <div class="metric-card green">
        <div class="metric-label">Win Rate</div>
        <div class="metric-value" id="m-winrate">—</div>
        <div class="metric-sub" id="m-winloss">— wins / — losses</div>
        <i class="fa-solid fa-trophy metric-icon"></i>
    </div>
    <div class="metric-card" id="profit-card">
        <div class="metric-label">Net Profit / Loss</div>
        <div class="metric-value" id="m-profit">—</div>
        <div class="metric-sub">Across all trades</div>
        <i class="fa-solid fa-dollar-sign metric-icon"></i>
    </div>
    <div class="metric-card yellow">
        <div class="metric-label">Best Trade</div>
        <div class="metric-value" id="m-best">—</div>
        <div class="metric-sub" id="m-worst">Worst: —</div>
        <i class="fa-solid fa-star metric-icon"></i>
    </div>
</div>

<!-- Chart + Recent Trades -->
<div class="section-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-area" style="color:var(--accent);margin-right:8px"></i>Cumulative P&L</h3>
            <span class="text-muted text-sm">Last 12 months</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="pnl-chart"></canvas>
            </div>
            <div id="no-chart-data" style="display:none;text-align:center;padding:60px 0;color:var(--text-muted);">
                <i class="fa-solid fa-chart-simple" style="font-size:36px;opacity:0.2;margin-bottom:12px;display:block"></i>
                No trade data yet — add your first trade!
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--accent);margin-right:8px"></i>Recent Trades</h3>
            <a href="<?= BASE_URL ?>/trades.php" class="btn btn-outline btn-sm">View all</a>
        </div>
        <div class="card-body">
            <div id="recent-trades-list">
                <div style="text-align:center;padding:40px 0;color:var(--text-muted);">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
let pnlChart = null;

async function loadDashboard() {
    try {
        const res  = await fetch('<?= BASE_URL ?>/api/analytics.php');
        const json = await res.json();
        if (!json.success) return;

        const d = json.data;

        // Metrics
        document.getElementById('m-total').textContent    = d.total_trades;
        document.getElementById('m-winrate').textContent  = d.win_rate + '%';
        document.getElementById('m-winrate').className    = 'metric-value ' + (d.win_rate >= 50 ? 'positive' : 'negative');
        document.getElementById('m-winloss').textContent  = d.win_count + ' wins / ' + d.loss_count + ' losses';

        const profitEl = document.getElementById('m-profit');
        profitEl.textContent  = formatPnl(d.net_profit);
        profitEl.className    = 'metric-value ' + pnlClass(d.net_profit);
        document.getElementById('profit-card').classList.add(d.net_profit >= 0 ? 'green' : 'red');

        document.getElementById('m-best').textContent  = formatPnl(d.best_trade);
        document.getElementById('m-best').className    = 'metric-value positive';
        document.getElementById('m-worst').textContent = 'Worst: ' + formatPnl(d.worst_trade);

        // Chart
        const chartCanvas = document.getElementById('pnl-chart');
        const noData      = document.getElementById('no-chart-data');

        if (d.chart_labels.length === 0) {
            chartCanvas.style.display = 'none';
            noData.style.display      = 'block';
        } else {
            if (pnlChart) pnlChart.destroy();
            pnlChart = new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: d.chart_labels,
                    datasets: [{
                        label: 'Cumulative P&L',
                        data: d.chart_cumulative,
                        borderColor: '#4f8ef7',
                        backgroundColor: 'rgba(79,142,247,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4f8ef7',
                        pointRadius: 4,
                    }, {
                        label: 'Monthly P&L',
                        data: d.chart_monthly,
                        borderColor: '#00c896',
                        backgroundColor: 'rgba(0,200,150,0.07)',
                        fill: false,
                        tension: 0.4,
                        pointBackgroundColor: '#00c896',
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            labels: { color: '#94a3b8', font: { size: 12 } }
                        },
                        tooltip: {
                            backgroundColor: '#1a2234',
                            borderColor: '#1e2d45',
                            borderWidth: 1,
                            titleColor: '#e2e8f0',
                            bodyColor:  '#94a3b8',
                            callbacks: {
                                label: ctx => ' ' + ctx.dataset.label + ': $' + ctx.parsed.y.toFixed(2)
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { color: '#64748b', font:{size:11} }, grid: { color: '#1e2d45' } },
                        y: {
                            ticks: { color: '#64748b', font:{size:11}, callback: v => '$' + v.toFixed(0) },
                            grid: { color: '#1e2d45' }
                        }
                    }
                }
            });
        }

        // Recent trades
        const container = document.getElementById('recent-trades-list');
        if (d.recent_trades.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:40px 0;color:var(--text-muted);">' +
                '<i class="fa-solid fa-inbox" style="font-size:32px;opacity:0.2;display:block;margin-bottom:12px"></i>' +
                'No trades yet. <a href="<?= BASE_URL ?>/trades.php" style="color:var(--accent)">Add your first trade</a></div>';
        } else {
            container.innerHTML = d.recent_trades.map(t => `
                <div class="recent-item">
                    <span class="badge badge-${t.trade_type.toLowerCase()}">${t.trade_type}</span>
                    <div style="flex:1">
                        <div class="recent-asset">${escHtml(t.asset_name)}</div>
                        <div class="recent-date">${formatDate(t.trade_date)}</div>
                    </div>
                    <div class="recent-pnl ${pnlClass(t.pnl)}">${formatPnl(t.pnl)}</div>
                </div>
            `).join('');
        }

    } catch(e) {
        console.error(e);
    }
}

function formatPnl(v) {
    v = parseFloat(v) || 0;
    return (v >= 0 ? '+' : '') + '$' + Math.abs(v).toFixed(2);
}
function pnlClass(v) { return parseFloat(v) > 0 ? 'pnl-positive' : parseFloat(v) < 0 ? 'pnl-negative' : 'pnl-neutral'; }
function escHtml(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function formatDate(d) { return new Date(d).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'}); }

loadDashboard();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
