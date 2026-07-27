# Section 17 Quality Report — Demand Intake, Purchase Requisitions & Budget Governance

## Initial audit: 3.1/10

The Version 3 schema already included purchase requests, request lines, project/work-order allocation, approval actions, and request-to-purchase-order foreign keys. The operational application did not expose a governed demand-intake workspace, budget envelopes, demand forecasts, sourcing assessments, immutable request history, or controlled request-to-PO conversion.

## Final score: 10/10

### 1. Canonical demand records — 1.0/1.0
- Preserves `purchase_requests` and `purchase_request_lines` as canonical records.
- Supports company, location, department, project/work-order, requester, purpose, date, urgency, justification, and line-level specification evidence.
- Maintains permanent request-to-purchase-order traceability.

### 2. Demand intelligence — 1.0/1.0
- Evaluates available inventory and internal avoidance value.
- Evaluates open purchase-order coverage.
- Detects duplicate and consolidation signals.
- Measures contract-covered and off-contract exposure.
- Evaluates supplier performance, risk, historical price variance, and required-date risk.

### 3. Budget governance — 1.0/1.0
- Supports company, department, project, and category budget envelopes.
- Tracks requested, approved, committed, actual, remaining, and utilization values.
- Identifies over-budget, no-budget, and unplanned demand.

### 4. Demand forecasting — 1.0/1.0
- Stores forecast quantity, value, confidence, source evidence, owner, period, category, and company.
- Surfaces forecast cash requirements beside current requests.

### 5. Workflow and approvals — 1.0/1.0
- Enforces draft, submission, approval, rejection, conversion, and cancellation boundaries.
- Routes material, urgent, over-budget, off-contract, and timing-risk requests to human approval.
- Prevents PO conversion before effective approval.

### 6. Conversion controls — 1.0/1.0
- Converts approved demand into canonical purchase orders and line items.
- Preserves supplier, company, request, project, purpose, dates, amounts, and source lines.
- Records immutable conversion evidence.

### 7. Persistence and scope — 1.0/1.0
- Provides session-isolated fictional Demo Mode records.
- Provides Production Data persistence with company-scope enforcement.
- Blocks Production writes until the Section 17 migration is imported.

### 8. Evidence, notifications, and export — 1.0/1.0
- Creates immutable request events and audit records.
- Notifies assigned reviewers for governed approvals.
- Provides protected CSV export and supervised Agent Workspace analysis.

### 9. Database compatibility — 1.0/1.0
- Uses an incremental, import-safe migration.
- Validates cumulative and repeated imports on MySQL 8.0 and MariaDB 10.11.
- Does not reimport or mutate the fresh-install Version 3 schema.

### 10. Regression protection — 1.0/1.0
- Runs cumulative Sections 1–17 quality gates on PHP 8.1 and PHP 8.3.
- Retains Sections 11–16 workflows.
- Renders all primary Demo Mode pages.
- Rejects committed `config.php` and unsafe setup placeholders.

## Deployment status

Code merge does not confirm production deployment. Import the deferred migrations in strict Section 11 through Section 17 order during the controlled deployment window, then perform production user acceptance and request-to-PO verification.
