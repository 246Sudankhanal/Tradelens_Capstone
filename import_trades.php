<?php
session_start();
require_once 'config/db.php';

$message = $_SESSION['import_message'] ?? '';
$errors = $_SESSION['import_errors'] ?? [];

unset($_SESSION['import_message']);
unset($_SESSION['import_errors']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Trades - TradeLens</title>
</head>
<body>
    <h2>Import Trades</h2>
    <p>Upload a CSV file to import your trading records.</p>

    <?php if (!empty($message)): ?>
        <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <h3>Import Errors</h3>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="api/import_trades.php" method="POST" enctype="multipart/form-data">
        <label>Select CSV File:</label><br><br>
        <input type="file" name="trade_file" accept=".csv" required><br><br>
        <button type="submit">Upload Trades</button>
    </form>

    <h3>Expected CSV Columns</h3>
    <pre>asset_name,trade_type,entry_price,exit_price,quantity,trade_date,notes,emotion,emotion_note</pre>

    <h3>Example</h3>
    <pre>BTC,Buy,50000,52000,1,2026-07-02,Good trade,Confident,Followed my plan</pre>

    <br>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>