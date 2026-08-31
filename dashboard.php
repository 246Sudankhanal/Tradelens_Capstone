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

<div class="card section-span">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-table-cells" style="color:var(--accent);margin-right:8px"></i>Daily P&amp;L heatmap</h3>
        <span class="text-muted text-sm">Last 30 days</span>
    </div>
    <div class="card-body">
        <div class="heatmap-wrap" id="pnl-heatmap"></div>
        <div class="heatmap-legend">
            <span class="heat-swatch win-hi"></span> Profit day
            <span class="heat-swatch loss-hi" style="margin-left:12px"></span> Loss day
            <span class="text-muted" style="margin-left:12px">Each cell shows day P&amp;L and trade count</span>
        </div>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-calendar-week" style="color:var(--accent);margin-right:8px"></i>P&amp;L by weekday</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="weekday-chart"></canvas>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-pie" style="color:var(--accent);margin-right:8px"></i>Win vs loss</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="winloss-chart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-layer-group" style="color:var(--accent);margin-right:8px"></i>P&amp;L by asset</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="asset-chart"></canvas>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-brain" style="color:var(--accent);margin-right:8px"></i>Avg P&amp;L by emotion</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="emotion-chart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
let pnlChart = null;
let weekdayChart = null;
let winlossChart = null;
let assetChart = null;
let emotionChart = null;

const chartTheme = {
    legend: { labels: { color: '#94a3b8', font: { size: 12 } } },
    tooltip: {
        backgroundColor: '#1a2234',
        borderColor: '#1e2d45',
        borderWidth: 1,
        titleColor: '#e2e8f0',
        bodyColor:  '#94a3b8',
        callbacks: {
            label: ctx => ' ' + (ctx.dataset.label || ctx.label) + ': $' + Number(ctx.parsed.y ?? ctx.parsed).toFixed(2)
        }
    }
};
const scaleTheme = {
    x: { ticks: { color: '#64748b', font:{size:11} }, grid: { color: '#1e2d45' } },
    y: {
        ticks: { color: '#64748b', font:{size:11}, callback: v => '$' + v },
        grid: { color: '#1e2d45' }
    }
};

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

        renderHeatmap(d.heatmap || {});
        renderWeekdayChart(d.weekday_labels || [], d.weekday_pnl || []);
        renderWinLossChart(d.win_count, d.loss_count);
        renderAssetChart(d.asset_labels || [], d.asset_pnl || []);
        renderEmotionChart(d.emotion_labels || [], d.emotion_pnl || []);

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

function ymd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function dayStats(map, key) {
    const raw = map[key];
    if (raw == null) return { pnl: 0, trades: 0, has: false };
    if (typeof raw === 'object') {
        return { pnl: Number(raw.pnl) || 0, trades: Number(raw.trades) || 0, has: true };
    }
    return { pnl: Number(raw) || 0, trades: Number(raw) !== 0 ? 1 : 0, has: true };
}

function renderHeatmap(map) {
    const host = document.getElementById('pnl-heatmap');
    if (!host) return;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const rangeStart = new Date(today);
    rangeStart.setDate(today.getDate() - 29);

    const gridStart = new Date(rangeStart);
    const startOffset = (gridStart.getDay() + 6) % 7; // Monday = 0
    gridStart.setDate(gridStart.getDate() - startOffset);

    const gridEnd = new Date(today);
    const endPad = (7 - ((gridEnd.getDay() + 6) % 7) - 1);
    gridEnd.setDate(gridEnd.getDate() + endPad);

    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    let html = '<div class="cal-heat">';
    html += '<div class="cal-heat-row cal-heat-head">';
    days.forEach(d => { html += `<div class="cal-head-cell">${d}</div>`; });
    html += '<div class="cal-head-cell cal-week-head">Weekly P&L</div></div>';

    const cursor = new Date(gridStart);
    while (cursor <= gridEnd) {
        html += '<div class="cal-heat-row">';
        let weekPnl = 0;
        let weekTrades = 0;
        for (let i = 0; i < 7; i++) {
            const key = ymd(cursor);
            const inRange = cursor >= rangeStart && cursor <= today;
            const st = inRange ? dayStats(map, key) : { pnl: 0, trades: 0, has: false };
            if (st.has) {
                weekPnl += st.pnl;
                weekTrades += st.trades;
            }
            let cls = 'cal-day out';
            if (inRange) {
                cls = 'cal-day';
                if (st.has && st.pnl > 0) cls += ' win';
                else if (st.has && st.pnl < 0) cls += ' loss';
                else if (st.has) cls += ' flat';
            }
            const label = cursor.getDate();
            const n = st.trades || 0;
            const tradeLabel = n === 1 ? '1 trade' : n + ' trades';
            const dayPnl = inRange ? formatPnl(st.pnl) : '';
            const title = inRange
                ? `${key} · ${tradeLabel} · ${formatPnl(st.pnl)}`
                : '';
            html += `<div class="${cls}" title="${title}">
                <span class="cal-date">${label}</span>
                ${inRange ? `<span class="cal-pnl">${dayPnl}</span><span class="cal-count">${tradeLabel}</span>` : ''}
            </div>`;
            cursor.setDate(cursor.getDate() + 1);
        }
        const wcls = weekPnl > 0 ? 'pnl-positive' : weekPnl < 0 ? 'pnl-negative' : 'pnl-neutral';
        html += `<div class="cal-week-total ${wcls}">${formatPnl(weekPnl)}<span>${weekTrades} trades</span></div>`;
        html += '</div>';
    }
    html += '</div>';
    host.innerHTML = html;
}

function renderWeekdayChart(labels, data) {
    const ctx = document.getElementById('weekday-chart');
    if (weekdayChart) weekdayChart.destroy();
    weekdayChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'P&L',
                data,
                backgroundColor: data.map(v => v >= 0 ? 'rgba(0,200,150,0.65)' : 'rgba(255,90,90,0.65)'),
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: chartTheme.tooltip },
            scales: scaleTheme
        }
    });
}

function renderWinLossChart(wins, losses) {
    const ctx = document.getElementById('winloss-chart');
    if (winlossChart) winlossChart.destroy();
    winlossChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Wins', 'Losses'],
            datasets: [{
                data: [wins || 0, losses || 0],
                backgroundColor: ['#00c896', '#ff5a5a'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: chartTheme.legend,
                tooltip: {
                    ...chartTheme.tooltip,
                    callbacks: { label: c => ' ' + c.label + ': ' + c.parsed }
                }
            }
        }
    });
}

function renderAssetChart(labels, data) {
    const ctx = document.getElementById('asset-chart');
    if (assetChart) assetChart.destroy();
    assetChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['No assets'],
            datasets: [{
                label: 'Net P&L',
                data: labels.length ? data : [0],
                backgroundColor: (labels.length ? data : [0]).map(v => v >= 0 ? 'rgba(79,142,247,0.7)' : 'rgba(255,90,90,0.65)'),
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: chartTheme.tooltip },
            scales: {
                x: scaleTheme.y,
                y: { ticks: { color: '#94a3b8', font:{size:11} }, grid: { display: false } }
            }
        }
    });
}

function renderEmotionChart(labels, data) {
    const ctx = document.getElementById('emotion-chart');
    if (emotionChart) emotionChart.destroy();
    emotionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['No emotion tags'],
            datasets: [{
                label: 'Avg P&L',
                data: labels.length ? data : [0],
                backgroundColor: 'rgba(124,58,237,0.7)',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: chartTheme.tooltip },
            scales: scaleTheme
        }
    });
}

loadDashboard();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
