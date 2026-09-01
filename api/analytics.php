<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/trading_accounts.php';
require_once __DIR__ . '/../includes/pnl.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = requireAuth();
$db     = getDB();
bootstrapUserAccounts($db, $userId);
$accSql = accountSql();
$pnl = tradePnlSql();

// Core stats
$stmt = $db->prepare("
    SELECT
        COUNT(*) AS total_trades,
        SUM($pnl) AS net_profit,
        SUM(CASE WHEN ($pnl) > 0 THEN 1 ELSE 0 END) AS win_count,
        SUM(CASE WHEN ($pnl) < 0 THEN 1 ELSE 0 END) AS loss_count,
        MAX($pnl) AS best_trade,
        MIN($pnl) AS worst_trade
    FROM trades
    WHERE user_id = ?{$accSql}
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

$total    = (int)$stats['total_trades'];
$wins     = (int)$stats['win_count'];
$losses   = (int)$stats['loss_count'];
$winRate  = $total > 0 ? round(($wins / $total) * 100, 1) : 0;
$netProfit= round((float)($stats['net_profit'] ?? 0), 2);
$best     = round((float)($stats['best_trade'] ?? 0), 2);
$worst    = round((float)($stats['worst_trade'] ?? 0), 2);

// Monthly P&L for chart (last 12 months)
$stmt = $db->prepare("
    SELECT
        DATE_FORMAT(trade_date, '%Y-%m') AS month,
        ROUND(SUM($pnl), 2) AS monthly_pnl
    FROM trades
    WHERE user_id = ?
      AND trade_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH){$accSql}
    GROUP BY DATE_FORMAT(trade_date, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute([$userId]);
$monthlyRows = $stmt->fetchAll();

$monthlyLabels = [];
$monthlyPnl    = [];
$cumulative    = [];
$running       = 0;

foreach ($monthlyRows as $row) {
    $monthlyLabels[] = date('M Y', strtotime($row['month'] . '-01'));
    $monthlyPnl[]    = (float)$row['monthly_pnl'];
    $running        += (float)$row['monthly_pnl'];
    $cumulative[]    = round($running, 2);
}

// Recent 5 trades
$stmt = $db->prepare("
    SELECT id, asset_name, trade_type, trade_date,
        ROUND($pnl, 2) AS pnl
    FROM trades
    WHERE user_id = ?{$accSql}
    ORDER BY trade_date DESC, id DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$recentTrades = $stmt->fetchAll();

$pnlExpr = tradePnlSql();

$stmt = $db->prepare("
    SELECT trade_date AS d, ROUND(SUM($pnlExpr), 2) AS pnl, COUNT(*) AS trades
    FROM trades
    WHERE user_id = ? AND trade_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY){$accSql}
    GROUP BY trade_date
");
$stmt->execute([$userId]);
$heatmap = [];
foreach ($stmt->fetchAll() as $row) {
    $heatmap[$row['d']] = [
        'pnl'    => (float) $row['pnl'],
        'trades' => (int) $row['trades'],
    ];
}

$weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$weekdayPnl = array_fill(0, 7, 0.0);
$stmt = $db->prepare("
    SELECT WEEKDAY(trade_date) AS wd, ROUND(SUM($pnlExpr), 2) AS pnl
    FROM trades WHERE user_id = ?{$accSql}
    GROUP BY WEEKDAY(trade_date)
");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $row) {
    $i = (int) $row['wd'];
    if ($i >= 0 && $i <= 6) $weekdayPnl[$i] = (float) $row['pnl'];
}

$stmt = $db->prepare("
    SELECT asset_name, ROUND(SUM($pnlExpr), 2) AS pnl, COUNT(*) AS cnt
    FROM trades WHERE user_id = ?{$accSql}
    GROUP BY asset_name
    ORDER BY ABS(SUM($pnlExpr)) DESC
    LIMIT 8
");
$stmt->execute([$userId]);
$assetRows = $stmt->fetchAll();
$assetLabels = array_column($assetRows, 'asset_name');
$assetPnl = array_map('floatval', array_column($assetRows, 'pnl'));

$stmt = $db->prepare("
    SELECT emotion, ROUND(AVG($pnlExpr), 2) AS avg_pnl
    FROM trades
    WHERE user_id = ? AND emotion IS NOT NULL AND emotion != ''{$accSql}
    GROUP BY emotion
    ORDER BY avg_pnl DESC
");
$stmt->execute([$userId]);
$emotionRows = $stmt->fetchAll();
$emotionLabels = array_column($emotionRows, 'emotion');
$emotionPnl = array_map('floatval', array_column($emotionRows, 'avg_pnl'));

jsonResponse(true, '', [
    'total_trades'  => $total,
    'win_count'     => $wins,
    'loss_count'    => $losses,
    'win_rate'      => $winRate,
    'net_profit'    => $netProfit,
    'best_trade'    => $best,
    'worst_trade'   => $worst,
    'chart_labels'  => $monthlyLabels,
    'chart_monthly' => $monthlyPnl,
    'chart_cumulative' => $cumulative,
    'recent_trades' => $recentTrades,
    'heatmap'       => $heatmap,
    'weekday_labels'=> $weekdayLabels,
    'weekday_pnl'   => $weekdayPnl,
    'asset_labels'  => $assetLabels,
    'asset_pnl'     => $assetPnl,
    'emotion_labels'=> $emotionLabels,
    'emotion_pnl'   => $emotionPnl,
]);
