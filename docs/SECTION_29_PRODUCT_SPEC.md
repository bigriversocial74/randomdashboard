# Section 29 — Enterprise Strategy Portfolio, Initiative Governance & Benefits Realization

## Purpose

Section 29 closes the management loop between executive performance, strategic intervention, process change, operational execution, funding, capacity, and independently validated benefits.

It does not replace the existing strategy, budget, process, work, calendar, savings, or executive modules. It governs cross-functional initiatives above them and preserves each module as the authoritative source for its own records.

## Required operating chain

1. Section 28 supplies goals, KPI definitions, immutable snapshots, and executive performance variance.
2. Section 29 converts an approved strategic response into a governed initiative.
3. Section 25 supplies the published process and active-version step affected by the initiative.
4. Section 17 supplies governed budget-envelope availability.
5. Section 26 receives executable initiative milestones and work items.
6. Section 27 receives milestone dates, participants, and capacity demand.
7. Section 20 supplies independently validated savings-realization evidence.
8. Section 29 locks benefit records.
9. Section 28 receives later KPI evidence showing whether performance improved.

## Scope

### Portfolio governance

- Enterprise and company portfolios
- Fiscal-year funding and capacity limits
- Strategic themes and portfolio types
- Independent owner, reviewer, and approver roles
- Published and locked portfolio records

### Strategic initiatives

- Business cases, costs, expected benefits, risks, urgency, alignment, confidence, and capacity demand
- Enterprise goal and KPI connections
- Published process and active process-step bindings
- Company/entity scope
- Initiative owner, sponsor, reviewer, approver, and independent benefit validator
- Parent/child initiative support

### Stage gates

The supported forward lifecycle is:

`idea → business_case → reviewed → approved → funded → in_execution → benefits_validation → completed`

Cancellation is a separately governed terminal action.

Every normal transition requires:

- Prepared evidence
- Assigned independent review
- Assigned independent approval
- Locked approval receipt
- Replay-safe transition key

### Funding

- Funding is reserved against a visible Section 17 budget envelope.
- The budget must belong to the initiative company.
- Approved funding cannot exceed the available envelope.
- The initiative owner cannot authorize their own funding.
- Only the assigned initiative approver may authorize funding.
- Funding receipts are locked and replay-safe.

### Execution

A funded or executing initiative may create milestones that:

- Create Section 26 work
- Create Section 27 calendar events
- Preserve Section 25 process-instance and step-instance references when a matching runtime exists
- Retain reviewer and approver separation
- Use replay-safe source keys

### Process impact

Initiatives may bind to a published Section 25 process and an active-version step.

A proposed process change creates a governed change proposal only. Approval of that proposal does not alter the published process, active version, steps, controls, transitions, or runtime. A separate Section 25 version workflow remains required.

### Benefits realization

Benefit records support financial and nonfinancial outcomes. Each claim requires:

- Scoped KPI evidence, or
- An independently validated or closed Section 20 realization period
- Benefit owner and separate validator
- Baseline, expected, and actual values
- Confidence and evidence
- Independent validation
- Permanent locking after validation

Validated benefits are immutable.

### Portfolio scenarios

Scenarios compare initiatives under budget, workforce, risk, savings, supplier-resilience, or automation priorities.

Scenario results are advisory only. They cannot:

- Approve or fund initiatives
- Change initiative stages
- Pause or cancel initiatives
- Change a published process
- Validate benefits

## Security boundaries

- Company and entity scope is enforced on portfolios, initiatives, goals, KPIs, budgets, savings evidence, milestones, benefits, and process proposals.
- Sibling-company IDs are rejected before persistence.
- Preparation, review, approval, execution ownership, and benefit validation are separated.
- Terminal and locked records are immutable.
- All browser writes are POST-only and CSRF-protected.
- Exports apply spreadsheet-formula protection.
- Agent Workspace recommendations remain human-supervised.

## Data model

Section 29 introduces nine tables:

1. `enterprise_strategy_portfolios`
2. `enterprise_strategic_initiatives`
3. `enterprise_initiative_links`
4. `enterprise_initiative_stage_gates`
5. `enterprise_initiative_funding`
6. `enterprise_initiative_milestones`
7. `enterprise_initiative_benefits`
8. `enterprise_portfolio_scenarios`
9. `enterprise_process_change_proposals`

Schema version: `5.8-section29`.

## Permissions

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

## Agent Workspace

The Strategy Portfolio Agent can explain:

- Which initiatives are at risk
- Which goals or KPIs lack corrective initiatives
- Which initiatives compete for the same budget or capacity
- Which processes and steps are affected
- Which milestones are overdue
- Which benefits remain unvalidated
- Which portfolio scenario has the strongest evidence

It may recommend review, funding, pausing, or reprioritization, but it cannot execute those decisions autonomously.
