# SQL Change Ledger

## Quality catch-up pass

No incremental SQL migration was added for Sections 1–10.

The application continues to rely on the existing corrected Version 3 schema imported manually from:

`database/gruber_ai_procurement_single_install_v3.sql`

The quality pass changed PHP, JavaScript, documentation, tests, configuration examples, and CI only. No additional SQL import is required when these changes are deployed over an existing Version 3 database.

## Section 11 — Supplier Comparison & Strategic Sourcing

Deferred migration:

`database/20260726_section11_supplier_comparison.sql`

- Dependency: corrected Version 3 schema.
- Purpose: creates `sourcing_comparisons` for saved supplier matrices, weights, preferred/alternate suppliers, rationale, owner, approval reference, and decision status.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record.
- Compatibility gate: validated on MySQL 8.0 and MariaDB 10.11 in the Section 11 workflow.
- Required import order: Version 3 schema first, then the Section 11 migration.
- Existing installation behavior before import: live supplier comparison and CSV export remain available; Production Data save and approval routing are intentionally blocked until the table exists.

## Section 12 — Scenario Planning & Procurement Risk Simulation

Deferred migration:

`database/20260726_section12_procurement_scenarios.sql`

- Dependencies: corrected Version 3 schema and the Section 11 migration.
- Purpose: creates `procurement_scenarios` for saved assumptions, calculated results, supplier/category scope, risk classification, owner, approval reference, and decision status.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record.
- Compatibility gate: validated on MySQL 8.0 and MariaDB 10.11 in the Section 12 workflow.
- Required import order: Version 3 schema, Section 11 migration, then Section 12 migration.
- Existing installation behavior before import: simulation and protected CSV export remain available; Production Data scenario save and approval routing are intentionally blocked until the table exists.

## Section 13 — Mitigation Action Plans & Supplier Contingency Management

Deferred migration:

`database/20260726_section13_mitigation_action_plans.sql`

- Dependencies: corrected Version 3 schema, the Section 11 migration, and the Section 12 migration.
- Purpose: creates `procurement_mitigation_plans` and `procurement_mitigation_actions` for governed contingency triggers, scenario linkage, supplier/category scope, action ownership, due dates, execution state, cost, recovery value, service-risk reduction, readiness, approval reference, activation evidence, and containment tracking.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record; the Section 13 workflow imports this migration twice on both database engines.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Section 11 migration, Section 12 migration, then Section 13 migration.
- Existing installation behavior before import: scenario-to-plan analysis remains available in Demo Mode; Production Data plan/action saving, approval routing, and activation are intentionally blocked until both Section 13 tables exist.

## Section 14 — Mitigation Execution, Recovery Verification & Procurement Change Control

Deferred migration:

`database/20260726_section14_execution_recovery_verification.sql`

- Dependencies: corrected Version 3 schema and the Section 11, Section 12, and Section 13 migrations.
- Purpose: creates `procurement_mitigation_executions`, `procurement_mitigation_execution_events`, and `procurement_recovery_verifications` for controlled operational changes, immutable status evidence, before/target/actual snapshots, approval references, rollback plans, independent verification, and planned-versus-actual recovery measures.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record; the Section 14 workflow imports this migration twice on both database engines.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Section 11 migration, Section 12 migration, Section 13 migration, then Section 14 migration.
- Existing installation behavior before import: Demo Mode execution and recovery verification remain available; Production Data execution, event, verification, approval, rollback, and containment-verification writes are intentionally blocked until all three Section 14 tables exist.

## Section 15 — Supplier Performance Monitoring, Corrective Action & Continuous Improvement

Deferred migration:

`database/20260726_section15_supplier_performance_improvement.sql`

- Dependencies: corrected Version 3 schema and the Section 11, Section 12, Section 13, and Section 14 migrations.
- Purpose: creates `supplier_performance_reviews`, `supplier_corrective_action_plans`, and `supplier_performance_events` for 30/60/90-day monitoring, baseline/current/target KPI evidence, supplier recommendations, risk tiers, repeated failures, spend exposure, retained savings, corrective-action ownership, regression events, approval references, and continuous-improvement closure.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record; the Section 15 workflow imports this migration twice on both database engines.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Section 11 migration, Section 12 migration, Section 13 migration, Section 14 migration, then Section 15 migration.
- Existing installation behavior before import: Demo Mode performance reviews, regression analysis, corrective actions, export, and Agent analysis remain available; Production Data review, action, event, approval, monitoring, notification, and closure writes are intentionally blocked until all three Section 15 tables exist.

## Section 16 — Contract Lifecycle, SLA Compliance & Renewal Governance

Deferred migration:

`database/20260726_section16_contract_lifecycle_renewal_governance.sql`

- Dependencies: corrected Version 3 schema and the Section 11 through Section 15 migrations.
- Purpose: preserves `supplier_contracts` as the contract master and creates governance profiles, obligations, amendments, renewal decisions, and immutable contract events for notice management, SLA evidence, spend governance, commercial alternatives, approval routing, and controlled implementation.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record; the Section 16 workflow imports this migration twice on MySQL 8.0 and MariaDB 10.11.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Section 11 migration, Section 12 migration, Section 13 migration, Section 14 migration, Section 15 migration, then Section 16 migration.
- Existing installation behavior before import: existing contract master records remain readable and Demo Mode contract governance remains available; Production Data profile, obligation, amendment, renewal, event, approval, notification, and implementation writes are intentionally blocked until all five Section 16 tables exist.

## Section 17 — Demand Intake, Purchase Requisitions & Budget Governance

Deferred migration:

`database/20260727_section17_demand_requisition_budget_governance.sql`

- Dependencies: corrected Version 3 schema and the Section 11 through Section 16 migrations.
- Purpose: preserves `purchase_requests` and `purchase_request_lines` as canonical demand records and creates governance profiles, budget envelopes, demand forecasts, sourcing assessments, and immutable request events for inventory checks, budget validation, contract coverage, supplier-performance evidence, duplicate-demand detection, approval routing, and controlled request-to-PO conversion.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record; the Section 17 workflow imports this migration twice on MySQL 8.0 and MariaDB 10.11.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Section 11 migration, Section 12 migration, Section 13 migration, Section 14 migration, Section 15 migration, Section 16 migration, then Section 17 migration.
- Existing installation behavior before import: existing canonical request records remain readable and Demo Mode demand governance remains available; Production Data profile, budget, forecast, assessment, event, approval, notification, and conversion writes are intentionally blocked until all five Section 17 tables exist.

## Section 18 — PO Fulfillment, Receiving, Invoice Match & Exception Governance

Deferred migration:

`database/20260727_section18_fulfillment_receiving_invoice_match.sql`

- Dependencies: corrected Version 3 schema and the Section 11 through Section 17 migrations.
- Purpose: preserves purchase orders, purchase-order lines, receipts, receipt lines, inventory balances, inventory transactions, and quality events as canonical records while creating fulfillment profiles, supplier invoices, invoice lines, three-way match results, governed invoice exceptions, and immutable fulfillment events.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record; the Section 18 workflow imports this migration twice on MySQL 8.0 and MariaDB 10.11.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Section 11 migration, Section 12 migration, Section 13 migration, Section 14 migration, Section 15 migration, Section 16 migration, Section 17 migration, then Section 18 migration.
- Existing installation behavior before import: canonical purchase orders and existing receipt data remain readable and Demo Mode fulfillment governance remains available; Production Data profile, invoice, match, exception, event, payment-release, and governed inventory-posting writes are intentionally blocked until all six Section 18 tables exist.

## Section 19 — Inventory Operations, Replenishment & Internal Transfer Governance

Deferred migration:

`database/20260727_section19_inventory_operations_replenishment_governance.sql`

- Dependencies: corrected Version 3 schema and the Section 11 through Section 18 migrations.
- Purpose: preserves inventory locations, balances, transactions, cycle counts, and count lines as canonical records while adding replenishment policies, replenishment recommendations, reservations, internal transfer requests and lines, transfer events, and immutable inventory governance events.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record; the Section 19 workflow imports this migration twice on MySQL 8.0 and MariaDB 10.11.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Section 11 migration, Section 12 migration, Section 13 migration, Section 14 migration, Section 15 migration, Section 16 migration, Section 17 migration, Section 18 migration, then Section 19 migration.
- Existing installation behavior before import: canonical inventory balances, transactions, and cycle counts remain readable and Demo Mode operations remain available; Production Data policy, recommendation, reservation, transfer, event, controlled-movement, and approval-gated inventory-governance writes are intentionally blocked until all seven Section 19 tables exist.

## Section 20 — Savings Realization, Finance Validation & Procurement Value Governance

Deferred migration:

`database/20260727_section20_savings_realization_finance_governance.sql`

- Dependencies: corrected Version 3 schema and the Section 11 through Section 19 migrations.
- Purpose: preserves `savings_opportunities` as the canonical opportunity record while adding versioned baselines, fiscal realization periods, transaction evidence links, independent finance validations, leakage records, and immutable savings governance events.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS` and an idempotent `schema_migrations` record; the Section 20 workflow imports this migration twice on MySQL 8.0 and MariaDB 10.11.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Section 11 migration, Section 12 migration, Section 13 migration, Section 14 migration, Section 15 migration, Section 16 migration, Section 17 migration, Section 18 migration, Section 19 migration, then Section 20 migration.
- Existing installation behavior before import: canonical savings opportunities remain readable and Demo Mode realization governance remains available; Production Data baseline, period, evidence, validation, leakage, event, finance-rollup, and period-close writes are intentionally blocked until all six Section 20 tables exist.

## Section 21 — Enterprise Spend Analytics, Category Strategy & Executive Procurement Planning

Deferred migration:

`database/20260727_section21_enterprise_spend_category_strategy_planning.sql`

- Dependencies: corrected Version 3 schema and the Section 11 through Section 20 migrations.
- Purpose: preserves purchasing, receiving, invoice, contract, supplier, scorecard, inventory, and savings records as canonical evidence while adding spend snapshots, classifications, category strategies and actions, procurement planning periods and targets, immutable strategy events, and `strategy.*` permissions.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE`, and an idempotent `schema_migrations` record; the Section 21 workflow imports this migration twice on MySQL 8.0 and MariaDB 10.11.
- Compatibility gate: MySQL 8.0 and MariaDB 10.11 cumulative import tests are required before merge.
- Required import order: Version 3 schema, Sections 11 through 20, then the Section 21 migration.
- Existing installation behavior before import: canonical operational records remain readable and Demo Mode strategy governance remains available; Production Data snapshot, classification, strategy, plan, target, event, approval, and lock writes are intentionally blocked until all seven Section 21 tables exist.

The user will import the deferred migrations together during the final deployment window. Do not import the fresh-install Version 3 schema into an already populated database.

## Deployment rule

- Never import the fresh-install schema into a populated production database.
- Keep `config.php` outside deployment packages and repository commits.
- Import the deferred migrations in strict Section 11 → Section 12 → Section 13 → Section 14 → Section 15 → Section 16 → Section 17 → Section 18 → Section 19 → Section 20 → Section 21 order.
- If a future section introduces SQL, record the migration filename, dependencies, idempotency, MySQL/MariaDB validation, and required import order in this ledger.
