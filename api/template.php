<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAuth();

$format = $_GET['format'] ?? 'csv';

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tradelens_import_template.csv"');
    header('Cache-Control: no-cache, must-revalidate');

    // UTF-8 BOM so Excel opens it correctly
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Asset Name', 'Trade Type', 'Entry Price', 'Exit Price', 'Quantity', 'Trade Date', 'Notes', 'Emotion']);
    fputcsv($out, ['AAPL',     'Buy',  '150.00', '165.00', '10',  '2024-01-15', 'Bought on pullback after earnings dip',   'Calm']);
    fputcsv($out, ['BTC/USD',  'Sell', '45000',  '42000',  '0.5', '2024-01-20', 'Shorted resistance — quick scalp',        'Confident']);
    fputcsv($out, ['TSLA',     'Buy',  '220.00', '195.00', '5',   '2024-02-01', 'Stop hit — rushed entry, lesson learned', 'Impulsive']);
    fputcsv($out, ['ETH/USD',  'Buy',  '2200',   '2500',   '1',   '2024-02-10', 'Swing trade on bullish structure',        'Patient']);
    fclose($out);
    exit;
}

// Unknown format
http_response_code(400);
echo 'Unsupported format.';
exit;
