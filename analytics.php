<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = requireAuth();
$db     = getDB();

// Core stats
$stmt = $db->prepare("
    SELECT
        COUNT(*) AS total_trades,
        SUM(CASE
            WHEN trade_type='Buy'  THEN (exit_price - entry_price) * quantity
            ELSE                       (entry_price - exit_price) * quantity
        END) AS net_profit,
        SUM(CASE WHEN (
            CASE WHEN trade_type='Buy' THEN exit_price - entry_price
                 ELSE entry_price - exit_price END
        ) > 0 THEN 1 ELSE 0 END) AS win_count,
        SUM(CASE WHEN (
            CASE WHEN trade_type='Buy' THEN exit_price - entry_price
                 ELSE entry_price - exit_price END
        ) < 0 THEN 1 ELSE 0 END) AS loss_count,
        MAX(CASE
            WHEN trade_type='Buy'  THEN (exit_price - entry_price) * quantity
            ELSE                       (entry_price - exit_price) * quantity
        END) AS best_trade,
        MIN(CASE
            WHEN trade_type='Buy'  THEN (exit_price - entry_price) * quantity
            ELSE                       (entry_price - exit_price) * quantity
        END) AS worst_trade
    FROM trades
    WHERE user_id = ?
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
        ROUND(SUM(
            CASE WHEN trade_type='Buy'
                 THEN (exit_price - entry_price) * quantity
                 ELSE (entry_price - exit_price) * quantity
            END
        ), 2) AS monthly_pnl
    FROM trades
    WHERE user_id = ?
      AND trade_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
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
        ROUND(CASE WHEN trade_type='Buy'
              THEN (exit_price - entry_price) * quantity
              ELSE (entry_price - exit_price) * quantity
        END, 2) AS pnl
    FROM trades
    WHERE user_id = ?
    ORDER BY trade_date DESC, id DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$recentTrades = $stmt->fetchAll();

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
]);
