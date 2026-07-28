# Section 29 SQL Change Ledger

## Migration

`database/20260727_section29_strategy_portfolio_initiative_benefits.sql`

Apply after Sections 11–28.

Schema version: `5.8-section29`

The migration is additive and repeat-safe for MySQL 8.0 and MariaDB 10.11.

## New tables

### `enterprise_strategy_portfolios`

Governed enterprise and company fiscal portfolios, including funding limits, capacity limits, owners, reviewers, approvers, publication, and immutable locks.

### `enterprise_strategic_initiatives`

Initiative business cases and lifecycle state, including value, risk, urgency, alignment, cost, benefit, capacity, dates, goal/KPI links, published-process links, budget links, savings links, and governance assignments.

### `enterprise_initiative_links`

Replay-safe many-to-many references from initiatives to goals, KPIs, processes, process steps, budgets, savings opportunities, and other governed evidence.

### `enterprise_initiative_stage_gates`

Prepared, reviewed, approved, and locked stage transitions with independent role assignments and idempotency keys.

### `enterprise_initiative_funding`

Locked funding receipts against Section 17 budget envelopes, with approved amounts, forecast cost, currency, independent governance, and replay protection.

### `enterprise_initiative_milestones`

Initiative execution milestones with Section 26 work IDs, Section 27 calendar event IDs, Section 25 process-runtime references, owners, dates, evidence, and source keys.

### `enterprise_initiative_benefits`

Financial and nonfinancial benefit claims with baseline, expected, actual, financial value, KPI/snapshot references, Section 20 realization references, confidence, independent validation, and immutable locking.

### `enterprise_portfolio_scenarios`

Advisory scenario inputs and results, including budget and capacity constraints, priority weights, selected cost/benefit/capacity, independent review, replay protection, and locks.

### `enterprise_process_change_proposals`

Governed process-impact proposals tied to the exact published Section 25 process version and optional step. These records do not alter process definitions.

## New permissions

- `portfolio_intelligence.view`
- `portfolio_intelligence.create`
- `portfolio_intelligence.edit`
- `portfolio_intelligence.submit`
- `portfolio_intelligence.review`
- `portfolio_intelligence.approve`
- `portfolio_intelligence.fund`
- `portfolio_intelligence.validate_benefits`
- `portfolio_intelligence.export`
- `portfolio_intelligence.administer`

## Data integrity rules

Application and workflow tests enforce:

- Visible company/entity scope
- Forward-only stage transitions
- Distinct preparation, review, approval, ownership, and validation roles
- Nonnegative cost, funding, benefit, and capacity values
- Start dates not later than target dates
- Confidence percentages between 0 and 100
- Unique replay/idempotency keys
- Locked approved gates and validated benefits
- Section 17 budget-company matching
- Section 20 realization-company matching
- Active-version Section 25 process-step matching
- No direct update to published process definitions

## Deployment order

For a populated installation with deferred Sections 24–29 migrations, import in this order:

1. `database/20260727_section24_business_entity_integration_foundation.sql`
2. `database/20260727_section25_process_mapping_orchestration_control_intelligence.sql`
3. `database/20260727_section26_enterprise_work_management_command_center.sql`
4. `database/20260727_section27_operational_calendar_notifications_capacity.sql`
5. `database/20260727_section28_executive_command_goals_performance.sql`
6. `database/20260727_section29_strategy_portfolio_initiative_benefits.sql`

Do not import the fresh-install SQL into a populated production database.
