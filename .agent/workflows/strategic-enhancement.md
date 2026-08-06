# Strategic Enhancement Plan - Phases 1, 2, & 3

This document outlines the implementation plan for the three key areas of improvement: Intelligent Inventory Forecasting, Advanced Logistics, and Financial P&L Tracking.

## Phase 1: Intelligent Inventory Forecasting (AI Insights)
**Objective**: Shift from reactive to proactive inventory management.

### 1.1 Database Updates
- Create `inventory_forecasts` table to store calculated predictions.
- Add `forecast_enabled` to `products` table.

### 1.2 Model & Service
- Create `models/InventoryForecaster.php`:
    - `calculateVelocity($productId)`: Determine sales per day.
    - `predictStockout($productId)`: Estimated days remaining.
    - `suggestMinStock($productId)`: Recommend optimized safety stock.

### 1.3 UI Enhancements
- Add "Insights" tab to Product View.
- Dashboard card for "Predicted Stockouts in < 7 Days".

---

## Phase 2: Advanced Logistics & Driver "Control Center"
**Objective**: Close the loop between dispatchers and drivers with real-time data.

### 2.1 Backend Enhancements
- API for signature/photo upload (Store in `uploads/signatures/` and `uploads/proofs/`).
- Update `route_deliveries` status transitions (Assigned -> In Transit -> Arrived -> Completed).

### 2.2 Driver Interface
- Create `routes/driver_center.php`:
    - Mobile-first design using Bootstrap.
    - List of assigned Deliveries.
    - "Start Delivery" and "Complete Stop" buttons.
    - Signature pad integration.

### 2.3 Dispatcher Live Map
- Enhance `routes/index.php` with a "Fleet View" using Google Maps/Mapbox to show route progress visually.

---

## Phase 3: Full Financial "Profit & Loss" (P&L) Tracking
**Objective**: Unified view of business health beyond just sales revenue.

### 3.1 Database Updates
- Create `business_expenses` table:
    - `id`, `category`, `amount`, `date`, `description`, `reference_id` (e.g., vehicle_id), `created_at`.
- Create `expense_categories` table.

### 3.2 Financial Model
- Create `models/Finance.php`:
    - `getGrossProfit($startDate, $endDate)`: Sales - COGS.
    - `getTotalExpenses($startDate, $endDate)`: Sum of all expenses + route costs.
    - `getNetProfit($startDate, $endDate)`: Gross Profit - Expenses.

### 3.3 Financial Dashboard
- Create `reports/finance.php`:
    - Net Profit over time chart (Line chart).
    - Expense breakdown (Doughnut chart).
    - Exportable P&L Statement (PDF/CSV).

---

## Next Steps
1.  **Initialize Phase 1**: Database migrations for Forecasting.
2.  **Initialize Phase 2**: Create the Mobile Driver Control Center foundation.
3.  **Initialize Phase 3**: Build the Expense Management interface.
