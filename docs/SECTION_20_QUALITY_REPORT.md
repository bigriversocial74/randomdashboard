# Section 20 Quality Report — Savings Realization, Finance Validation & Procurement Value Governance

## Result

- Initial audit: **2.8/10**
- Final implementation target: **10/10**
- Canonical record preserved: `savings_opportunities`
- Deferred migration: `database/20260727_section20_savings_realization_finance_governance.sql`
- Production SQL import: deferred to the final deployment window

## Delivered controls

### Versioned savings baselines

- Historical spend, historical price, contract price, budget, market-index, and other baseline methods
- Baseline period, volume, unit cost, total cost, supplier, contract, methodology, assumptions, owner, reviewer, and evidence
- Independent workflow approval
- Locked approved baseline
- Prior approved baseline supersession without destroying history

### Periodized realization

- Monthly or quarterly fiscal periods
- Planned and actual hard savings
- Planned and actual cost avoidance
- Supplier credits and recoveries
- Working-capital benefits
- Implementation and operating costs
- Leakage and signed adjustments
- Gross and net realized value
- Finance validation and period close

### Transaction evidence

- Supplier comparison
- Supplier contract and amendment
- Purchase request
- Purchase order and line
- Receipt
- Supplier invoice
- Three-way match
- Invoice exception
- Supplier credit
- Inventory transfer
- Replenishment avoidance
- Quality recovery
- Corrective action
- Other traceable source evidence

### Independent finance validation

- Evidence-completeness score
- Finance reviewer
- Validated benefit-category snapshots
- Changes-requested and validated decisions
- Procurement-owner and finance-reviewer separation
- Approval required before validation
- Only validated or closed periods roll into canonical realized savings

### Leakage governance

- Contract-price erosion
- Off-contract purchasing
- Missed supplier credits
- Invoice overpayment
- Volume shortfall
- Implementation delay
- Supplier noncompliance
- Emergency purchasing
- Missed internal transfers
- Inventory carrying costs
- Benefit expiration
- Recovery reconciliation and immutable evidence

### Reporting and governance

- Gross pipeline
- Weighted forecast
- Finance-validated net value
- Hard savings
- Cost avoidance
- Recoveries
- Working capital
- Implementation and operating cost
- Leakage and benefits at risk
- Realization percentage
- Forecast accuracy
- Protected CSV export
- Agent Workspace handoff
- Immutable governance events
- Demo and Production persistence
- Production writes blocked before migration

## Automated gates

The Section 20 workflow requires:

- PHP 8.1 cumulative Sections 1–20 quality
- PHP 8.3 cumulative Sections 1–20 quality
- Focused baseline, period, evidence, validation, leakage, canonical-rollup, event, export, rendering, handler, and integration tests
- MySQL 8 cumulative migration import
- MariaDB 10.11 cumulative migration import
- Repeat import of the Section 20 migration on both engines
- Retained Sections 11–19 workflows
- Repository-wide authentication integrations
- All primary Demo Mode pages render successfully
- No live `config.php`

## Production deployment notes

Import this migration only after Sections 11 through 19:

`database/20260727_section20_savings_realization_finance_governance.sql`

Do not reimport `database/gruber_ai_procurement_single_install_v3.sql` into a populated database.
