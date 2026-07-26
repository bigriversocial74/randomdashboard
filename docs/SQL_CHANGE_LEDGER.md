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

The user will import the deferred migrations together during the final deployment window. Do not import the fresh-install Version 3 schema into an already populated database.

## Deployment rule

- Never import the fresh-install schema into a populated production database.
- Keep `config.php` outside deployment packages and repository commits.
- If a future section introduces SQL, record the migration filename, dependencies, idempotency, MySQL/MariaDB validation, and required import order in this ledger.
