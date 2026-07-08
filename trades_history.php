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
        <a href="add_trade.php" class="btn btn-secondary">Add New Trade</a>
        
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
                <tbody id="tradeHistoryBody">
                    <!-- Data loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        async function loadTrades() {
            const response = await fetch('api/trades.php');
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
                    </tr>
                `).join('');
            }
        }
        window.onload = loadTrades;
    </script>
</body>
</html>
