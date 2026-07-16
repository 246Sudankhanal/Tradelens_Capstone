<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trade History - TradeLens</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-theme">
    <div class="container">
        <h1>Trade History</h1>
        <div class="actions">
            <a href="add_trade.php" class="btn btn-secondary">Add New Trade</a>
        </div>

        <form id="filterForm" class="filter-bar">
            <input type="text" name="search" placeholder="Search Asset..." id="searchInput">
            <select name="type" id="typeSelect">
                <option value="">All Types</option>
                <option value="Buy">Buy</option>
                <option value="Sell">Sell</option>
            </select>
            <input type="date" name="from" id="fromDate">
            <input type="date" name="to" id="toDate">
            <button type="button" class="btn btn-primary" onclick="loadTrades()">Filter</button>
            <button type="button" class="btn btn-outline" onclick="resetFilters()">Reset</button>
        </form>
        
        <div class="table-container">
            <table class="trade-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Asset</th>
                        <th>Type</th>
                        <th>Entry</th>
                        <th>Exit</th>
                        <th>Qty</th>
                        <th>P&L</th>
                        <th>Emotion</th>
                    </tr>
                </thead>
                <tbody id="tradeHistoryBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        async function loadTrades() {
            const search = document.getElementById('searchInput').value;
            const type = document.getElementById('typeSelect').value;
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;

            const url = `api/trades.php?search=${search}&type=${type}&from=${from}&to=${to}`;
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                const tbody = document.getElementById('tradeHistoryBody');
                tbody.innerHTML = result.data.map(trade => `
                    <tr>
                        <td>${trade.trade_date}</td>
                        <td>${trade.asset_name}</td>
                        <td class="${trade.trade_type === 'Buy' ? 'text-buy' : 'text-sell'}">${trade.trade_type}</td>
                        <td>${trade.entry_price}</td>
                        <td>${trade.exit_price}</td>
                        <td>${trade.quantity}</td>
                        <td class="${trade.pnl >= 0 ? 'text-profit' : 'text-loss'}">$${trade.pnl}</td>
                        <td>${trade.emotion || '-'}</td>
                        <td><a href="edit_trade.php?id=${trade.id}" class="btn-small">Edit</a></td>
                    </tr>
                `).join('');
            }
        }

        function resetFilters() {
            document.getElementById('filterForm').reset();
            loadTrades();
        }

        window.onload = loadTrades;
    </script>
</body>
</html>
