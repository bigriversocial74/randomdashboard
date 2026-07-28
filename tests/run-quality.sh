#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ -f config.php ]]; then
  echo "config.php must not be committed or packaged." >&2
  exit 1
fi

while IFS= read -r -d '' file; do php -l "$file" >/dev/null; done < <(find . -type f -name '*.php' -print0)
while IFS= read -r -d '' file; do node --check "$file" >/dev/null; done < <(find . -type f -name '*.js' -print0)

php tests/account_menu_state.php logged-out
php tests/account_menu_state.php logged-in
php tests/section1_static.php
php tests/section2_public.php
php tests/section3_shell.php
php tests/section4_reporting.php
php tests/section5_core.php
php tests/section6_imports.php
php tests/section7_workflows.php
php tests/section8_agent.php
php tests/section9_admin.php
php tests/section11_supplier_comparison.php
php tests/section12_procurement_scenarios.php
php tests/section13_mitigation_action_plans.php
php tests/section14_execution_recovery.php
php tests/section15_supplier_performance.php
php tests/section16_contract_lifecycle.php
php tests/section17_demand_requisition.php
php tests/section18_fulfillment_invoice_match.php
php tests/section19_inventory_operations.php
php tests/section20_savings_realization.php
php tests/section21_spend_strategy.php
php tests/section22_supplier_portal.php
php tests/supplier_portal_render.php
php tests/section23_accounts_payable.php
php tests/section24_business_entities.php
php tests/section24_render.php
php tests/section25_process_mapping.php
php tests/section25_render.php
php tests/sections24_25_integration_audit.php
php tests/section26_work_management.php
php tests/section26_render.php
php tests/section27_operational_calendar.php
php tests/section27_render.php
php tests/section28_executive_intelligence.php
php tests/section28_render.php
php tests/section29_strategy_portfolio.php
php tests/section29_render.php
php tests/section30_policy_adoption.php
php tests/section30_render.php

pages=(
  dashboard.php briefing.php reports.php spend-strategy.php supplier-portal.php accounts-payable.php suppliers.php sourcing.php scenarios.php mitigations.php executions.php performance.php contracts.php demand.php fulfillment.php items.php purchase-orders.php inventory.php inventory-operations.php
  scorecards.php imports.php discovery.php data-collection.php savings.php savings-realization.php approvals.php notifications.php work-management.php operational-calendar.php executive-command.php executive-kpi-governance.php strategy-portfolio.php knowledge-adoption.php
  agent.php tour.php profile.php settings.php change-password.php admin/index.php admin/users.php
  admin/roles.php admin/companies.php admin/access-requests.php admin/sessions.php admin/security.php
  admin/audit.php admin/settings.php admin/environment.php
)
for page in "${pages[@]}"; do php tests/demo_page_render.php "$page"; done

if grep -R -nE "YOUR_DB_|YOUR_DATABASE|CHANGE_ME" app includes index.php demo.php resume.php signup.php lost-password.php signin.php signout.php install.php manual-admin.php >/tmp/gruber-secret-placeholders.txt; then
  echo "Unsafe deployment placeholder detected outside example/setup files." >&2
  cat /tmp/gruber-secret-placeholders.txt >&2
  exit 1
fi

if grep -R -nE "Open Installer|\$env\['installer'\]|simulated a password-reset|reset delivery was simulated" app includes >/tmp/gruber-retired-workflows.txt; then
  echo "Retired installer or simulated password-delivery copy detected." >&2
  cat /tmp/gruber-retired-workflows.txt >&2
  exit 1
fi

for report in docs/SECTION_{1..30}_QUALITY_REPORT.md docs/QUALITY_SCORECARD.md docs/SQL_CHANGE_LEDGER.md docs/SECTIONS_24_25_INTEGRATION_AUDIT.md; do
  [[ -f "$report" ]] || { echo "Missing quality evidence: $report" >&2; exit 1; }
done

echo "All Sections 1-30 and Sections 24-25 integration audit quality gates passed."
