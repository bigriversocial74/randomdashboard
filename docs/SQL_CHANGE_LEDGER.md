# SQL Change Ledger

## Production database rule

- Never import `database/gruber_ai_procurement_single_install_v3.sql` into a populated production database.
- Keep `config.php` outside repository commits and deployment packages.
- Sections 1–10 introduced no incremental SQL migration.
- Import all deferred migrations in strict numerical order.
- Every migration uses repeat-safe table creation and an idempotent `schema_migrations` record and must pass MySQL 8.0 and MariaDB 10.11 cumulative validation.

## Deferred migrations

### Section 11 — Supplier Comparison & Strategic Sourcing
`database/20260726_section11_supplier_comparison.sql`

Creates governed supplier-comparison decisions. Requires the corrected Version 3 schema. Production comparison writes fail closed before import.

### Section 12 — Scenario Planning & Procurement Risk Simulation
`database/20260726_section12_procurement_scenarios.sql`

Creates governed procurement scenarios. Requires Section 11. Production scenario writes fail closed before import.

### Section 13 — Mitigation Action Plans & Supplier Contingency Management
`database/20260726_section13_mitigation_action_plans.sql`

Creates mitigation plans and actions. Requires Sections 11–12. Production mitigation writes fail closed before import.

### Section 14 — Mitigation Execution, Recovery Verification & Change Control
`database/20260726_section14_execution_recovery_verification.sql`

Creates mitigation executions, immutable execution events, and independent recovery verifications. Requires Sections 11–13.

### Section 15 — Supplier Performance & Continuous Improvement
`database/20260726_section15_supplier_performance_improvement.sql`

Creates supplier-performance reviews, corrective-action plans, and immutable performance events. Requires Sections 11–14.

### Section 16 — Contract Lifecycle, SLA Compliance & Renewal Governance
`database/20260726_section16_contract_lifecycle_renewal_governance.sql`

Preserves `supplier_contracts` as canonical and adds contract profiles, obligations, amendments, renewals, and events. Requires Sections 11–15.

### Section 17 — Demand Intake, Purchase Requisitions & Budget Governance
`database/20260727_section17_demand_requisition_budget_governance.sql`

Preserves canonical purchase requests and adds governance profiles, budgets, forecasts, sourcing assessments, and events. Requires Sections 11–16.

### Section 18 — PO Fulfillment, Receiving, Invoice Match & Exception Governance
`database/20260727_section18_fulfillment_receiving_invoice_match.sql`

Preserves purchase orders, receipts, and inventory as canonical while adding fulfillment profiles, supplier invoices and lines, match results, exceptions, and events. Requires Sections 11–17.

### Section 19 — Inventory Operations, Replenishment & Transfer Governance
`database/20260727_section19_inventory_operations_replenishment_governance.sql`

Preserves canonical inventory records and adds policies, recommendations, reservations, transfers, and immutable inventory events. Requires Sections 11–18.

### Section 20 — Savings Realization & Finance Validation
`database/20260727_section20_savings_realization_finance_governance.sql`

Preserves `savings_opportunities` as canonical and adds baselines, realization periods, evidence, finance validations, leakage, and events. Requires Sections 11–19.

### Section 21 — Enterprise Spend Analytics & Category Strategy
`database/20260727_section21_enterprise_spend_category_strategy_planning.sql`

Preserves operational records as canonical and adds spend snapshots, classifications, category strategies and actions, planning periods and targets, and strategy events. Requires Sections 11–20.

### Section 22 — Supplier Portal & External Collaboration
`database/20260727_section22_supplier_portal_collaboration_exchange.sql`

Adds isolated supplier accounts, invitations, grants, staged PO responses, ASNs, staged invoices, documents, sourcing and quality responses, messages, and immutable portal events. Requires Sections 11–21.

### Section 23 — Accounts Payable, Payment Execution & Financial Close
`database/20260727_section23_accounts_payable_payment_close_governance.sql`

Preserves `supplier_invoices` as canonical and adds payment schedules, dual-controlled batches and items, finance-validated supplier credits, verified external payment-instruction references, idempotent execution evidence, supplier-visible remittances, independent reconciliations, accounting periods, GRNI accruals, close certifications, immutable AP events, and `accounts_payable.*` permissions. Stores no raw banking credentials and performs no autonomous bank transmission. Requires Sections 11–22. Production AP writes fail closed before import.

## Required populated-database import order

1. `database/20260726_section11_supplier_comparison.sql`
2. `database/20260726_section12_procurement_scenarios.sql`
3. `database/20260726_section13_mitigation_action_plans.sql`
4. `database/20260726_section14_execution_recovery_verification.sql`
5. `database/20260726_section15_supplier_performance_improvement.sql`
6. `database/20260726_section16_contract_lifecycle_renewal_governance.sql`
7. `database/20260727_section17_demand_requisition_budget_governance.sql`
8. `database/20260727_section18_fulfillment_receiving_invoice_match.sql`
9. `database/20260727_section19_inventory_operations_replenishment_governance.sql`
10. `database/20260727_section20_savings_realization_finance_governance.sql`
11. `database/20260727_section21_enterprise_spend_category_strategy_planning.sql`
12. `database/20260727_section22_supplier_portal_collaboration_exchange.sql`
13. `database/20260727_section23_accounts_payable_payment_close_governance.sql`

The user will import deferred migrations during the final deployment window. Code merge does not confirm production SQL import or deployment.