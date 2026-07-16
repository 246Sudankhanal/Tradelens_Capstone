<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Trade - TradeLens</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-theme">
    <div class="container">
        <h1>Edit Trade</h1>
        <form id="editTradeForm" method="POST" action="api/trades.php">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" id="tradeId">
            
            <div class="form-group">
                <label>Asset Name</label>
                <input type="text" name="asset_name" id="assetName" required>
            </div>
            <div class="form-group">
                <label>Trade Type</label>
                <select name="trade_type" id="tradeType" required>
                    <option value="Buy">Buy</option>
                    <option value="Sell">Sell</option>
                </select>
            </div>
            <div class="row">
                <div class="col">
                    <label>Entry Price</label>
                    <input type="number" step="0.0001" name="entry_price" id="entryPrice" required>
                </div>
                <div class="col">
                    <label>Exit Price</label>
                    <input type="number" step="0.0001" name="exit_price" id="exitPrice" required>
                </div>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" step="0.0001" name="quantity" id="quantity" required>
            </div>
            <div class="form-group">
                <label>Trade Date</label>
                <input type="date" name="trade_date" id="tradeDate" required>
            </div>
            <div class="form-group">
                <label>Emotional Tag</label>
                <select name="emotion" id="emotion">
                    <option value="Calm">Calm</option>
                    <option value="Anxious">Anxious</option>
                    <option value="Greedy">Greedy</option>
                    <option value="Disciplined">Disciplined</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" id="notes" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Trade</button>
            <a href="trades_history.php" class="btn btn-outline">Cancel</a>
        </form>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const tradeId = urlParams.get('id');

        async function loadTradeData() {
            if (!tradeId) return;
            const response = await fetch(`api/trades.php?id=${tradeId}`);
            const result = await response.json();
            
            if (result.success) {
                const t = result.data;
                document.getElementById('tradeId').value = t.id;
                document.getElementById('assetName').value = t.asset_name;
                document.getElementById('tradeType').value = t.trade_type;
                document.getElementById('entryPrice').value = t.entry_price;
                document.getElementById('exitPrice').value = t.exit_price;
                document.getElementById('quantity').value = t.quantity;
                document.getElementById('tradeDate').value = t.trade_date;
                document.getElementById('emotion').value = t.emotion;
                document.getElementById('notes').value = t.notes;
            }
        }
        window.onload = loadTradeData;
    </script>
</body>
</html>
