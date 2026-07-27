# SQL Change Ledger Continuation — Sections 24 and 25

This document continues `docs/SQL_CHANGE_LEDGER.md` without rewriting the preserved Section 11–23 migration history.

## Section 24 — Modular Business Entity, Organizational Hierarchy & Integration Foundation

Deferred migration:

`database/20260727_section24_business_entity_integration_foundation.sql`

- Dependencies: corrected Version 3 schema and deferred Sections 11–23 migrations.
- Purpose: preserves all six canonical `companies.id` values and transaction relationships while adding business entities, effective-dated entity relationships, company bindings, shared-service module profiles, data authorities, access scopes, reusable entity templates, integration connections, entity bindings, external-ID mappings, synchronization runs and events, conflicts, and immutable entity history.
- Security boundary: integration credentials remain external secret references; no raw passwords, tokens, banking credentials, routing numbers, or private keys are stored in ordinary integration records.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE`, deterministic company backfill, and schema version `5.3-section24`; validated through repeat imports on MySQL 8 and MariaDB 10.11.
- Existing installation behavior before import: all canonical company and procurement records remain readable; Production entity-template, relationship, authority, access, integration, mapping, event, conflict, and export writes fail closed until all Section 24 tables exist.

## Section 25 — Enterprise Process Mapping, Workflow Orchestration & Control Intelligence

Deferred migration:

`database/20260727_section25_process_mapping_orchestration_control_intelligence.sql`

- Dependencies: corrected Version 3 schema and deferred Sections 11–24 migrations, especially the Section 24 business-entity and integration foundation.
- Purpose: adds visual process definitions, immutable versions, cross-entity swimlanes, positioned steps, normal and exception transitions, controls, integration events, entity assignments, live process references, step instances, independent exceptions, and immutable process history without replacing canonical procurement statuses.
- Reference seed: one complete Procure-to-Pay reference map with five Section 24-aligned swimlanes, 14 steps, 14 transitions, 12 controls, and seven integration-event mappings. The application catalog supplies nine reusable starting templates for governed instantiation.
- Governance boundary: published process versions are locked; process preparation, review, and approval are separated; process exceptions require separate ownership and review; integration execution remains adapter-gated; canonical operational modules remain authoritative.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE`, deterministic reference codes, nullable system attribution for fresh-schema compatibility, and schema version `5.4-section25`; the Section 25 workflow imports the migration twice on MySQL 8 and MariaDB 10.11.
- Existing installation behavior before import: canonical transactions and Section 24 entities remain readable and Demo Mode remains fully visual; Production process-definition, layout, instance, transition, exception, export, and event writes fail closed until all twelve Section 25 tables exist.

## Current strict deployment order

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
14. `database/20260727_section24_business_entity_integration_foundation.sql`
15. `database/20260727_section25_process_mapping_orchestration_control_intelligence.sql`

Never import `database/gruber_ai_procurement_single_install_v3.sql` into an already populated production database. Keep the live `config.php` outside repository commits and deployment packages. Production deployment and migration import remain unconfirmed until explicitly verified by the user.
