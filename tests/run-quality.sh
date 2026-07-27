#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ -f config.php ]]; then echo "config.php must not be committed or packaged." >&2; exit 1; fi
while IFS= read -r -d '' file; do php -l "$file" >/dev/null; done < <(find . -type f -name '*.php' -print0)
while IFS= read -r -d '' file; do node --check "$file" >/dev/null; done < <(find . -type f -name '*.js' -print0)
php tests/account_menu_state.php logged-out
php tests/account_menu_state.php logged-in
for test in section1_static section2_public section3_shell section4_reporting section5_core section6_imports section7_workflows section8_agent section9_admin section11_supplier_comparison section12_procurement_scenarios section13_mitigation_action_plans section14_execution_recovery section15_supplier_performance section16_contract_lifecycle section17_demand_requisition section18_fulfillment_invoice_match section19_inventory_operations section20_savings_realization section21_spend_strategy; do php "tests/${test}.php"; done
pages=(dashboard.php briefing.php reports.php spend-strategy.php suppliers.php sourcing.php scenarios.php mitigations.php executions.php performance.php contracts.php demand.php fulfillment.php items.php purchase-orders.php inventory.php inventory-operations.php scorecards.php imports.php discovery.php data-collection.php savings.php savings-realization.php approvals.php notifications.php agent.php tour.php profile.php settings.php change-password.php admin/index.php admin/users.php admin/roles.php admin/companies.php admin/access-requests.php admin/sessions.php admin/security.php admin/audit.php admin/settings.php admin/environment.php)
for page in "${pages[@]}"; do php tests/demo_page_render.php "$page"; done
if grep -R -nE "YOUR_DB_|YOUR_DATABASE|CHANGE_ME" app includes index.php demo.php resume.php signup.php lost-password.php signin.php signout.php install.php manual-admin.php >/tmp/gruber-secret-placeholders.txt; then echo "Unsafe deployment placeholder detected outside example/setup files." >&2; cat /tmp/gruber-secret-placeholders.txt >&2; exit 1; fi
if grep -R -nE "Open Installer|\$env\['installer'\]|simulated a password-reset|reset delivery was simulated" app includes >/tmp/gruber-retired-workflows.txt; then echo "Retired installer or simulated password-delivery copy detected." >&2; cat /tmp/gruber-retired-workflows.txt >&2; exit 1; fi
for report in docs/SECTION_{1..21}_QUALITY_REPORT.md docs/QUALITY_SCORECARD.md docs/SQL_CHANGE_LEDGER.md; do [[ -f "$report" ]] || { echo "Missing quality evidence: $report" >&2; exit 1; }; done
echo "All Sections 1-21 quality gates passed."
