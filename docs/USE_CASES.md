# Use Cases - Sudan Khanal

## UC-03: Add Trade Record
**Primary Actor:** Registered Trader
**Preconditions:** Trader is logged in.
**Main Flow:**
1. Trader opens Add Trade page.
2. Trader enters trade details (Asset, Type, Entry, Exit, Quantity, Date, Notes, Emotion).
3. System validates values and calculates P&L server-side.
4. System saves trade and redirects to trade history.

## UC-04: View Trade History
**Primary Actor:** Registered Trader
**Preconditions:** Trader is logged in.
**Main Flow:**
1. Trader opens Trade History.
2. System retrieves records belonging to the user.
3. System displays table with calculated P&L.

## UC-05: Search and Filter Trades
**Primary Actor:** Registered Trader
**Preconditions:** Trader is viewing trade history.
**Main Flow:**
1. Trader enters search term (Asset) or filters (Type, Date Range).
2. System returns matching records.
3. Table refreshes with filtered results.

## UC-06: Edit Trade Record
**Primary Actor:** Registered Trader
**Preconditions:** Trader owns the selected trade record.
**Main Flow:**
1. Trader selects Edit on a record.
2. System loads existing trade details.
3. Trader updates fields and saves.
4. System recalculates P&L and updates the record.
