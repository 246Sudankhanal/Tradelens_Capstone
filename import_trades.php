<?php
require_once 'config/db.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Trades - TradeLens</title>
</head>
<body>
    <h2>Import Trades</h2>
    <p>Upload a CSV file to import your trading records.</p>

    <form action="api/import_trades.php" method="POST" enctype="multipart/form-data">
        <label>Select CSV File:</label><br><br>

        <input type="file" name="trade_file" accept=".csv" required><br><br>

        <button type="submit">Upload Trades</button>
    </form>

    <br>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>