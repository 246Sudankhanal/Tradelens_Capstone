<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Trade - TradeLens</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-theme">
    <div class="container">
        <h1>Add New Trade</h1>
        <form id="addTradeForm" method="POST" action="api/trades.php">
            <div class="form-group">
                <label>Asset Name (e.g., BTC/USD)</label>
                <input type="text" name="asset_name" required>
            </div>
            <div class="form-group">
                <label>Trade Type</label>
                <select name="trade_type" required>
                    <option value="Buy">Buy</option>
                    <option value="Sell">Sell</option>
                </select>
            </div>
            <div class="row">
                <div class="col">
                    <label>Entry Price</label>
                    <input type="number" step="0.0001" name="entry_price" required>
                </div>
                <div class="col">
                    <label>Exit Price</label>
                    <input type="number" step="0.0001" name="exit_price" required>
                </div>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" step="0.0001" name="quantity" value="1" required>
            </div>
            <div class="form-group">
                <label>Trade Date</label>
                <input type="date" name="trade_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Emotional Tag</label>
                <select name="emotion">
                    <option value="Calm">Calm</option>
                    <option value="Anxious">Anxious</option>
                    <option value="Greedy">Greedy</option>
                    <option value="Disciplined">Disciplined</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Trade</button>
        </form>
    </div>
</body>
</html>
