<?php
/**
 * Trade P&L Calculation logic (Pseudocode 7.2)
 *
 * @param string $tradeType 'Buy' or 'Sell'
 * @param float $entryPrice
 * @param float $exitPrice
 * @param float $quantity
 * @return float Calculated P&L
 */
function calculateProfitLoss($tradeType, $entryPrice, $exitPrice, $quantity) {
    if ($entryPrice <= 0 || $exitPrice <= 0 || $quantity <= 0) {
        return 0.00;
    }

    if ($tradeType === 'Buy') {
        // P&L = (Exit - Entry) * Quantity
        $pnl = ($exitPrice - $entryPrice) * $quantity;
    } elseif ($tradeType === 'Sell') {
        // P&L = (Entry - Exit) * Quantity
        $pnl = ($entryPrice - $exitPrice) * $quantity;
    } else {
        return 0.00;
    }

    return round($pnl, 2);
}
?>
