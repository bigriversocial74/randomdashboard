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

- Dependencies: corrected Version 3 schema, Section 11 migration, and Section 12 migration.
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

The user will import the deferred migrations together during the final deployment window. Do not import the fresh-install Version 3 schema into an already populated database.

## Deployment rule

- Never import the fresh-install schema into a populated production database.
- Keep `config.php` outside deployment packages and repository commits.
- Import the deferred migrations in strict Section 11 → Section 12 → Section 13 → Section 14 → Section 15 order.
- If a future section introduces SQL, record the migration filename, dependencies, idempotency, MySQL/MariaDB validation, and required import order in this ledger.