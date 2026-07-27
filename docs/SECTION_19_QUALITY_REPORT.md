# Section 19 Quality Report — Inventory Operations, Replenishment & Internal Transfer Governance

## Release score

- Initial audit: **2.9/10**
- Target and release score: **10/10** after exact-head validation
- SQL impact: deferred migration

## Canonical records preserved

Section 19 does not replace the existing inventory ledger. The following Version 3 records remain canonical:

- `inventory_locations`
- `inventory_balances`
- `inventory_transactions`
- `cycle_counts`
- `cycle_count_lines`
- `items`
- `purchase_orders` and `purchase_order_lines`
- Section 17 demand records
- Section 18 receipt and quality evidence

## Governance records added

- replenishment policies
- replenishment recommendations
- inventory reservations
- internal transfer requests and lines
- immutable transfer events
- immutable inventory governance events

## Operational controls

- minimum, maximum, reorder-point, safety-stock, lead-time, supplier, owner, reviewer, and evidence policies
- recommendation calculations using available inventory, allocations, open-PO coverage, demand forecasts, safety stock, maximum position, internal surplus, unit cost, required date, and supplier evidence
- approval-gated recommendation conversion to a purchase request or internal transfer
- reservations that update allocated quantity without changing on-hand quantity
- controlled reservation release and canonical reservation/release transactions
- transfer source availability checks before submission and again before dispatch
- approval-gated transfer dispatch
- source decrement only at dispatch and destination increment only at confirmed receipt
- carrier, tracking, lot/serial, freight, condition, required-date, owner, reviewer, and custody evidence
- governed issue, return-to-stock, return-to-supplier, adjustment, scrap, core-return, donor-harvest, refurbishment, and sale movements
- cycle-count scheduling, blind-count evidence, quantity/value variance calculation, approval routing, and approval-gated balance adjustment
- prevention of negative on-hand, negative allocation, or allocation above on-hand
- company-scope enforcement for policies, reservations, transfers, counts, balances, transactions, and events
- Production Data writes blocked until the Section 19 migration exists

## Intelligence and reporting

- inventory value
- available and reserved quantities
- active reservations
- open and in-transit transfers
- governed replenishment value
- stockout risk
- excess inventory exposure
- open cycle counts
- internal-transfer versus new-purchase recommendation
- protected CSV export
- Agent Workspace prompt with inventory, demand, transfer, supplier, custody, count, and controlled-movement evidence

## Automated gates

- PHP 8.1 and PHP 8.3 cumulative Sections 1–19 quality
- focused policy, recommendation, reservation, transfer, balance, event, cycle-count, controlled-movement, export, render, handler, and workflow-handoff tests
- all primary Demo Mode pages render
- MySQL 8 cumulative migration import with Section 19 imported twice
- MariaDB 10.11 cumulative migration import with Section 19 imported twice
- migration version and all seven new tables verified
- repository-wide authentication integrations retained
- no committed `config.php`

## Deployment boundary

Import `database/20260727_section19_inventory_operations_replenishment_governance.sql` only after Sections 11 through 18 during the final populated-database deployment window. Never reimport the fresh-install Version 3 schema into a populated database.
