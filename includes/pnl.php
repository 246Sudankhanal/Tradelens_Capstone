<?php
/** Shared P&L SQL so dashboard, journal, and chat use the same lot multipliers. */
function tradePnlSql(string $alias = ''): string {
    $p = $alias === '' ? '' : rtrim($alias, '.') . '.';
    return "
        CASE
            WHEN {$p}asset_name LIKE '%XAU%' OR {$p}asset_name LIKE '%GOLD%'
            THEN (CASE WHEN {$p}trade_type = 'Buy' THEN ({$p}exit_price - {$p}entry_price) ELSE ({$p}entry_price - {$p}exit_price) END) * {$p}quantity * 100
            WHEN {$p}asset_name LIKE '%XAG%' OR {$p}asset_name LIKE '%SILVER%'
            THEN (CASE WHEN {$p}trade_type = 'Buy' THEN ({$p}exit_price - {$p}entry_price) ELSE ({$p}entry_price - {$p}exit_price) END) * {$p}quantity * 5000
            ELSE (CASE WHEN {$p}trade_type = 'Buy' THEN ({$p}exit_price - {$p}entry_price) ELSE ({$p}entry_price - {$p}exit_price) END) * {$p}quantity
        END
    ";
}
