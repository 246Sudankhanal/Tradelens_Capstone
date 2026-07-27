<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle  = 'Trade Journal';
$activePage = 'trades';
include __DIR__ . '/includes/header.php';
?>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h2>Trade Journal</h2>
        <p>Log, review, and manage your trades</p>
    </div>
    <div class="flex-gap">
        <button class="btn btn-outline" onclick="openImportModal()">
            <i class="fa-solid fa-file-import"></i> Import
        </button>
        <button class="btn btn-success" onclick="openModal()">
            <i class="fa-solid fa-plus"></i> Add Trade
        </button>
    </div>
</div>

<!-- QUICK STATS -->
<div class="stats-bar" id="quick-stats">
    <div class="stat-item"><div class="stat-val" id="qs-total">—</div><div class="stat-key">Total</div></div>
    <div class="stat-item"><div class="stat-val pnl-positive" id="qs-wins">—</div><div class="stat-key">Wins</div></div>
    <div class="stat-item"><div class="stat-val pnl-negative" id="qs-losses">—</div><div class="stat-key">Losses</div></div>
    <div class="stat-item"><div class="stat-val" id="qs-winrate">—</div><div class="stat-key">Win Rate</div></div>
    <div class="stat-item"><div class="stat-val" id="qs-pnl">—</div><div class="stat-key">Net P&L</div></div>
</div>

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="form-control" id="search-input" placeholder="Search asset..." oninput="debouncedLoad()">
    </div>
    <div class="filters">
        <select class="form-control" id="filter-type" onchange="loadTrades()">
            <option value="">All Types</option>
            <option value="Buy">Buy</option>
            <option value="Sell">Sell</option>
        </select>
        <input type="date" class="form-control" id="filter-from" onchange="loadTrades()" title="From date">
        <input type="date" class="form-control" id="filter-to"   onchange="loadTrades()" title="To date">
        <select class="form-control" id="sort-by" onchange="loadTrades()">
            <option value="date_desc">Newest first</option>
            <option value="date_asc">Oldest first</option>
            <option value="asset_asc">Asset A-Z</option>
            <option value="asset_desc">Asset Z-A</option>
            <option value="pnl_desc">Best P&L first</option>
            <option value="pnl_asc">Worst P&L first</option>
        </select>
        <button class="btn btn-outline" onclick="clearFilters()"><i class="fa-solid fa-xmark"></i> Clear</button>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="table-wrapper">
        <table id="trades-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Asset</th>
                    <th>Type</th>
                    <th>Entry</th>
                    <th>Exit</th>
                    <th>Qty</th>
                    <th>P&amp;L</th>
                    <th>Emotion</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="trades-body">
                <tr><td colspan="10" style="text-align:center;padding:60px;color:var(--text-muted);">
                    <span class="spinner"></span> Loading trades...
                </td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ============ ADD / EDIT MODAL ============ -->
<div class="modal-backdrop" id="trade-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Add New Trade</h3>
            <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-error" id="modal-error"></div>
            <form id="trade-form" novalidate>
                <input type="hidden" id="trade-id" name="id" value="">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Asset Name *</label>
                        <input type="text" name="asset_name" id="f-asset" class="form-control" placeholder="e.g. AAPL, BTC/USD" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trade Type *</label>
                        <select name="trade_type" id="f-type" class="form-control" required>
                            <option value="">Select type</option>
                            <option value="Buy">Buy (Long)</option>
                            <option value="Sell">Sell (Short)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Entry Price *</label>
                        <input type="number" name="entry_price" id="f-entry" class="form-control" placeholder="0.00" step="any" min="0.0001" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exit Price *</label>
                        <input type="number" name="exit_price" id="f-exit" class="form-control" placeholder="0.00" step="any" min="0.0001" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="f-qty" class="form-control" placeholder="1" step="any" min="0.0001" value="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trade Date *</label>
                        <input type="date" name="trade_date" id="f-date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emotion / State</label>
                        <select name="emotion" id="f-emotion" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Confident">Confident</option>
                            <option value="Calm">Calm</option>
                            <option value="Patient">Patient</option>
                            <option value="Fearful">Fearful</option>
                            <option value="Greedy">Greedy</option>
                            <option value="Impulsive">Impulsive</option>
                            <option value="Uncertain">Uncertain</option>
                        </select>
                    </div>
                    <div class="form-group" style="align-self:end">
                        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px">
                            <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">Estimated P&L</div>
                            <div style="font-size:20px;font-weight:700;" id="pnl-preview">$0.00</div>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Notes / Reflection</label>
                        <textarea name="notes" id="f-notes" class="form-control" placeholder="What did you do well? Any mistakes? Market context..."></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" id="modal-submit-btn" onclick="submitTrade()">
                <i class="fa-solid fa-check"></i> Save Trade
            </button>
        </div>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-backdrop" id="delete-modal">
    <div class="modal" style="max-width:380px">
        <div class="modal-header">
            <h3 class="modal-title">Delete Trade</h3>
            <button class="modal-close" onclick="closeDeleteModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-secondary)">Are you sure you want to delete this trade? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirm-delete-btn" onclick="confirmDelete()">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
        </div>
    </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
let deleteId = null;
let debounceTimer = null;

// ---- Load trades ----
async function loadTrades() {
    const params = new URLSearchParams({
        search:    document.getElementById('search-input').value.trim(),
        type:      document.getElementById('filter-type').value,
        date_from: document.getElementById('filter-from').value,
        date_to:   document.getElementById('filter-to').value,
        sort:      document.getElementById('sort-by').value,
    });

    const tbody = document.getElementById('trades-body');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted);"><span class="spinner"></span></td></tr>';

    try {
        const res  = await fetch(BASE + '/api/trades.php?' + params);
        const json = await res.json();
        if (!json.success) return;
        renderTrades(json.data);
        updateQuickStats(json.data);
    } catch(e) { console.error(e); }
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadTrades, 350);
}

function renderTrades(trades) {
    const tbody = document.getElementById('trades-body');
    if (trades.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10">
            <div class="empty-state">
                <i class="fa-solid fa-book-open"></i>
                <p>No trades found. Add your first trade!</p>
                <button class="btn btn-success" onclick="openModal()"><i class="fa-solid fa-plus"></i> Add Trade</button>
            </div>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = trades.map(t => {
        const pnlVal   = parseFloat(t.pnl);
        const pnlCls   = pnlVal > 0 ? 'pnl-positive' : pnlVal < 0 ? 'pnl-negative' : 'pnl-neutral';
        const pnlStr   = (pnlVal >= 0 ? '+' : '') + '$' + Math.abs(pnlVal).toFixed(2);
        const emotion  = t.emotion ? `<span style="font-size:12px;color:var(--text-muted)">${escHtml(t.emotion)}</span>` : '<span class="text-muted">—</span>';
        const notes    = t.notes ? `<span class="notes-text" title="${escAttr(t.notes)}">${escHtml(t.notes)}</span>` : '<span class="text-muted">—</span>';
        return `<tr>
            <td>${formatDate(t.trade_date)}</td>
            <td style="font-weight:600">${escHtml(t.asset_name)}</td>
            <td><span class="badge badge-${t.trade_type.toLowerCase()}">${t.trade_type}</span></td>
            <td>$${parseFloat(t.entry_price).toFixed(4)}</td>
            <td>$${parseFloat(t.exit_price).toFixed(4)}</td>
            <td>${parseFloat(t.quantity).toFixed(2)}</td>
            <td class="${pnlCls}">${pnlStr}</td>
            <td>${emotion}</td>
            <td>${notes}</td>
            <td>
                <div class="flex-gap">
                    <button class="btn btn-outline btn-sm" onclick='editTrade(${JSON.stringify(t)})'>
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="promptDelete(${t.id})">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function updateQuickStats(trades) {
    const total  = trades.length;
    const wins   = trades.filter(t => parseFloat(t.pnl) > 0).length;
    const losses = trades.filter(t => parseFloat(t.pnl) < 0).length;
    const wr     = total > 0 ? (wins / total * 100).toFixed(1) : 0;
    const netPnl = trades.reduce((s, t) => s + parseFloat(t.pnl), 0);
    const pnlCls = netPnl > 0 ? 'pnl-positive' : netPnl < 0 ? 'pnl-negative' : 'pnl-neutral';

    document.getElementById('qs-total').textContent   = total;
    document.getElementById('qs-wins').textContent    = wins;
    document.getElementById('qs-losses').textContent  = losses;
    document.getElementById('qs-winrate').textContent = wr + '%';
    const pnlEl = document.getElementById('qs-pnl');
    pnlEl.textContent = (netPnl >= 0 ? '+' : '') + '$' + Math.abs(netPnl).toFixed(2);
    pnlEl.className   = 'stat-val ' + pnlCls;
}

// ---- Modal ----
function openModal(trade = null) {
    document.getElementById('modal-title').textContent = trade ? 'Edit Trade' : 'Add New Trade';
    document.getElementById('modal-submit-btn').innerHTML = trade
        ? '<i class="fa-solid fa-check"></i> Update Trade'
        : '<i class="fa-solid fa-check"></i> Save Trade';
    document.getElementById('modal-error').classList.remove('show');
    document.getElementById('trade-form').reset();

    if (trade) {
        document.getElementById('trade-id').value    = trade.id;
        document.getElementById('f-asset').value     = trade.asset_name;
        document.getElementById('f-type').value      = trade.trade_type;
        document.getElementById('f-entry').value     = trade.entry_price;
        document.getElementById('f-exit').value      = trade.exit_price;
        document.getElementById('f-qty').value       = trade.quantity;
        document.getElementById('f-date').value      = trade.trade_date;
        document.getElementById('f-emotion').value   = trade.emotion || '';
        document.getElementById('f-notes').value     = trade.notes || '';
        updatePnlPreview();
    } else {
        document.getElementById('trade-id').value = '';
        document.getElementById('f-date').value   = new Date().toISOString().split('T')[0];
        document.getElementById('pnl-preview').textContent = '$0.00';
        document.getElementById('pnl-preview').style.color = '';
    }

    document.getElementById('trade-modal').classList.add('open');
}

function closeModal() {
    document.getElementById('trade-modal').classList.remove('open');
}

function editTrade(t) { openModal(t); }

// ---- Live P&L Preview ----
function updatePnlPreview() {
    const entry = parseFloat(document.getElementById('f-entry').value) || 0;
    const exit  = parseFloat(document.getElementById('f-exit').value)  || 0;
    const qty   = parseFloat(document.getElementById('f-qty').value)   || 1;
    const type  = document.getElementById('f-type').value;
    const el    = document.getElementById('pnl-preview');

    let pnl = 0;
    if (type === 'Buy')  pnl = (exit - entry) * qty;
    if (type === 'Sell') pnl = (entry - exit) * qty;

    el.textContent = (pnl >= 0 ? '+' : '') + '$' + Math.abs(pnl).toFixed(2);
    el.style.color = pnl > 0 ? 'var(--green)' : pnl < 0 ? 'var(--red)' : '';
}

['f-entry','f-exit','f-qty','f-type'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updatePnlPreview);
    document.getElementById(id)?.addEventListener('change', updatePnlPreview);
});

// ---- Submit ----
async function submitTrade() {
    const btn    = document.getElementById('modal-submit-btn');
    const errEl  = document.getElementById('modal-error');
    const tradeId= document.getElementById('trade-id').value;
    errEl.classList.remove('show');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';

    const form = document.getElementById('trade-form');
    const data = new FormData(form);

    try {
        let res, json;
        if (tradeId) {
            // PUT — convert FormData to URLSearchParams
            const body = new URLSearchParams(data);
            res  = await fetch(BASE + '/api/trades.php', { method: 'PUT', body });
        } else {
            res  = await fetch(BASE + '/api/trades.php', { method: 'POST', body: data });
        }
        json = await res.json();

        if (json.success) {
            closeModal();
            showToast(json.message, 'success');
            loadTrades();
        } else {
            errEl.textContent = json.message;
            errEl.classList.add('show');
        }
    } catch(e) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.classList.add('show');
    } finally {
        btn.disabled = false;
        btn.innerHTML = tradeId
            ? '<i class="fa-solid fa-check"></i> Update Trade'
            : '<i class="fa-solid fa-check"></i> Save Trade';
    }
}

// ---- Delete ----
function promptDelete(id) {
    deleteId = id;
    document.getElementById('delete-modal').classList.add('open');
}

function closeDeleteModal() {
    deleteId = null;
    document.getElementById('delete-modal').classList.remove('open');
}

async function confirmDelete() {
    if (!deleteId) return;
    const btn = document.getElementById('confirm-delete-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';

    try {
        const res  = await fetch(BASE + '/api/trades.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + deleteId
        });
        const json = await res.json();
        if (json.success) {
            closeDeleteModal();
            showToast(json.message, 'success');
            loadTrades();
        } else {
            showToast(json.message, 'error');
        }
    } catch {
        showToast('Network error.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-trash"></i> Delete';
    }
}

// ---- Filters ----
function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('filter-type').value  = '';
    document.getElementById('filter-from').value  = '';
    document.getElementById('filter-to').value    = '';
    document.getElementById('sort-by').value      = 'date_desc';
    loadTrades();
}

// ---- Helpers ----
function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
}
function escHtml(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function escAttr(s) { return s.replace(/"/g, '&quot;'); }

function showToast(msg, type = 'success') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i> ${escHtml(msg)}`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ---- Close modal on backdrop click ----
document.getElementById('trade-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('delete-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

loadTrades();
</script>

<!-- ============ IMPORT MODAL ============ -->
<div class="modal-backdrop" id="import-modal">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-file-import" style="color:var(--accent);margin-right:8px"></i>Import Trades</h3>
            <button class="modal-close" onclick="closeImportModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">

            <!-- Step 1: Upload -->
            <div id="import-step-upload">
                <div class="alert alert-info show" style="display:flex;gap:10px;align-items:flex-start">
                    <i class="fa-solid fa-circle-info" style="margin-top:2px;flex-shrink:0"></i>
                    <div>
                        Upload a <strong>CSV</strong> or <strong>Excel (.xlsx)</strong> file.
                        Columns must include: <code>Asset Name</code>, <code>Trade Type</code>,
                        <code>Entry Price</code>, <code>Exit Price</code>, <code>Trade Date</code>.
                    </div>
                </div>

                <!-- Drop zone -->
                <div class="drop-zone" id="drop-zone" onclick="document.getElementById('import-file').click()">
                    <div class="drop-zone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="drop-zone-text">Drag &amp; drop your file here</div>
                    <div class="drop-zone-sub">or click to browse — CSV, XLSX up to 5 MB</div>
                    <div class="drop-zone-file" id="drop-zone-file" style="display:none"></div>
                </div>
                <input type="file" id="import-file" accept=".csv,.xlsx" style="display:none" onchange="onFileSelect(this)">

                <div class="alert alert-error" id="import-error"></div>

                <div style="margin-top:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <a href="<?= BASE_URL ?>/api/template.php?format=csv" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-download"></i> Download CSV Template
                    </a>
                    <div class="flex-gap">
                        <button class="btn btn-outline" onclick="closeImportModal()">Cancel</button>
                        <button class="btn btn-primary" id="import-btn" onclick="doImport()" disabled>
                            <i class="fa-solid fa-file-import"></i> Import
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Results -->
            <div id="import-step-results" style="display:none">
                <div id="import-result-summary"></div>
                <div id="import-result-errors" style="margin-top:14px;max-height:220px;overflow-y:auto"></div>
                <div style="text-align:right;margin-top:20px">
                    <button class="btn btn-primary" onclick="finishImport()">
                        <i class="fa-solid fa-check"></i> Done
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Drop Zone */
.drop-zone {
    border: 2px dashed var(--border-light);
    border-radius: var(--radius);
    padding: 36px 24px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    background: var(--bg-secondary);
    margin: 16px 0;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--accent);
    background: var(--accent-glow);
}
.drop-zone-icon {
    font-size: 36px;
    color: var(--text-muted);
    margin-bottom: 10px;
    transition: var(--transition);
}
.drop-zone:hover .drop-zone-icon, .drop-zone.drag-over .drop-zone-icon {
    color: var(--accent);
}
.drop-zone-text { font-size: 15px; font-weight: 500; margin-bottom: 4px; }
.drop-zone-sub  { font-size: 13px; color: var(--text-muted); }
.drop-zone-file {
    margin-top: 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--accent);
}
.drop-zone.has-file {
    border-color: var(--green);
    background: var(--green-bg);
}
.drop-zone.has-file .drop-zone-icon { color: var(--green); }

/* Import result boxes */
.import-stat-row {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
}
.import-stat {
    flex: 1;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 14px;
    text-align: center;
}
.import-stat.is-success { border-color: var(--green-border); }
.import-stat.is-warning { border-color: rgba(251,191,36,0.3); }
.import-stat .is-val { font-size: 26px; font-weight: 700; }
.import-stat .is-key { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.is-success .is-val { color: var(--green); }
.is-warning .is-val { color: var(--yellow); }

.error-list {
    background: var(--red-bg);
    border: 1px solid var(--red-border);
    border-radius: var(--radius-sm);
    padding: 12px 14px;
}
.error-list-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--red);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.error-list ul { padding-left: 18px; }
.error-list li { font-size: 13px; color: var(--text-secondary); margin-bottom: 4px; }
</style>

<script>
// ---- Import Modal ----
let importFile = null;

function openImportModal() {
    resetImportModal();
    document.getElementById('import-modal').classList.add('open');
}

function closeImportModal() {
    document.getElementById('import-modal').classList.remove('open');
}

function resetImportModal() {
    importFile = null;
    document.getElementById('import-file').value        = '';
    document.getElementById('import-error').classList.remove('show');
    document.getElementById('import-btn').disabled      = true;
    document.getElementById('drop-zone').className      = 'drop-zone';
    document.getElementById('drop-zone-file').style.display = 'none';
    document.getElementById('drop-zone-file').textContent   = '';
    document.getElementById('import-step-upload').style.display  = 'block';
    document.getElementById('import-step-results').style.display = 'none';
}

function onFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    setImportFile(file);
}

function setImportFile(file) {
    const allowed = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                     'application/vnd.ms-excel', 'text/plain'];
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['csv','xlsx'].includes(ext)) {
        showImportError('Unsupported file type. Only CSV and XLSX files are allowed.');
        return;
    }
    importFile = file;
    document.getElementById('drop-zone').className     = 'drop-zone has-file';
    document.getElementById('drop-zone-file').style.display = 'block';
    document.getElementById('drop-zone-file').textContent   = '📄 ' + file.name + ' (' + formatBytes(file.size) + ')';
    document.getElementById('import-btn').disabled    = false;
    document.getElementById('import-error').classList.remove('show');
}

// Drag & drop
const dz = document.getElementById('drop-zone');
dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave', ()  => dz.classList.remove('drag-over'));
dz.addEventListener('drop', e => {
    e.preventDefault();
    dz.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) setImportFile(file);
});

async function doImport() {
    if (!importFile) return;
    const btn = document.getElementById('import-btn');
    const err = document.getElementById('import-error');
    err.classList.remove('show');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Importing...';

    const data = new FormData();
    data.append('trade_file', importFile);

    try {
        const res  = await fetch(BASE + '/api/import_trades.php', { method: 'POST', body: data });
        const json = await res.json();

        if (!json.success) {
            showImportError(json.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-file-import"></i> Import';
            return;
        }

        // Show results
        showImportResults(json);

    } catch(e) {
        showImportError('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-file-import"></i> Import';
    }
}

function showImportResults(json) {
    const d = json.data;
    document.getElementById('import-step-upload').style.display  = 'none';
    document.getElementById('import-step-results').style.display = 'block';

    const summary = document.getElementById('import-result-summary');
    summary.innerHTML = `
        <div class="import-stat-row">
            <div class="import-stat is-success">
                <div class="is-val">${d.imported}</div>
                <div class="is-key">Imported</div>
            </div>
            <div class="import-stat is-warning">
                <div class="is-val">${d.skipped}</div>
                <div class="is-key">Skipped</div>
            </div>
            <div class="import-stat">
                <div class="is-val">${d.imported + d.skipped}</div>
                <div class="is-key">Total Rows</div>
            </div>
        </div>
        <div class="alert ${d.imported > 0 ? 'alert-success' : 'alert-error'} show">
            ${escHtml(json.message)}
        </div>
    `;

    const errContainer = document.getElementById('import-result-errors');
    if (d.errors && d.errors.length > 0) {
        errContainer.innerHTML = `
            <div class="error-list">
                <div class="error-list-title">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    ${d.errors.length} row issue(s) detected:
                </div>
                <ul>${d.errors.map(e => `<li>${escHtml(e)}</li>`).join('')}</ul>
            </div>
        `;
    } else {
        errContainer.innerHTML = '';
    }
}

function finishImport() {
    closeImportModal();
    loadTrades();
    showToast('Trades imported successfully!', 'success');
}

function showImportError(msg) {
    const el = document.getElementById('import-error');
    el.textContent = msg;
    el.classList.add('show');
}

function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

// Close import modal on backdrop click
document.getElementById('import-modal').addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
