# SQL Change Ledger

## Fresh-install baseline

Sections 1–10 use `database/gruber_ai_procurement_single_install_v3.sql` for new installations.

## Deferred migrations for populated databases

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

## Section 21

Section 21 preserves purchasing, receiving, invoice, contract, supplier, scorecard, inventory, and savings records as canonical evidence. It adds governed spend snapshots and classifications, category strategies and actions, procurement planning periods and targets, immutable strategy events, and the `strategy.*` permission family.

The migration is repeat-safe through `CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE`, and an idempotent `schema_migrations` record. The Section 21 workflow imports the complete Section 11–21 sequence and repeats Section 21 on MySQL 8.0 and MariaDB 10.11.

Production strategy writes remain blocked until the Section 21 migration is present. Import the deferred migrations in the order shown during the final deployment window. Keep `config.php` outside repository commits and deployment packages.
