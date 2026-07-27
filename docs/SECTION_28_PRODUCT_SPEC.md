# Section 28 Product Specification

## Executive Command Center, Goals & Performance Intelligence

Section 28 turns the operating evidence created by Sections 24–27 into a governed executive-performance layer. It does not replace canonical procurement records or the published process maps.

## Required integration

Section 28 is deliberately integrated with the existing process architecture:

- **Section 24 — Business entities:** determines company, operating-entity, shared-service, and Enterprise View scope.
- **Section 25 — Process mapping:** supplies published process definitions, active versions, process steps, live instances, step instances, cycle times, exceptions, controls, and SLA evidence.
- **Section 26 — Work management:** supplies execution, ownership, evidence, review, approval, escalation, critical-work, and SLA results.
- **Section 27 — Operational calendar:** supplies capacity, scheduled reviews, decision deadlines, notifications, and executive operating cadence.

KPI definitions may bind to a visible published process and an optional process step. The step must belong to the active published process version. KPI snapshots preserve those process references in their evidence. Executive decisions can create Section 26 work while retaining the relevant Section 25 process-instance and step-instance identifiers. Review meetings and decision deadlines are scheduled through Section 27.

## Capabilities

### Goals

- Enterprise and operating-company goals
- Parent/child hierarchy
- Financial, operational, strategic, and risk objectives
- Numeric targets, units, direction, dates, owners, reviewers, and executive sponsors
- Confidence and evidence
- Links to KPI definitions, published processes, and process steps

### Governed KPI catalog

- Versioned calculation methodology
- Source module and deterministic metric key
- Entity and company scope
- Optional Section 25 process and process-step binding
- Target, warning, and critical thresholds
- Separate preparation, review, and approval
- Permanent locking of published versions

### KPI snapshots and forecast

- Replay-safe period snapshots
- Immutable actuals and source evidence
- Target comparison and severity state
- Period-over-period trend
- Transparent bounded forecast and confidence
- Process-instance, process-step, and exception counts in evidence

### Scorecards

- Enterprise and company scorecards
- Prepared, reviewed, published, and locked states
- On-target, warning, and critical counts
- Embedded immutable KPI results
- Executive summary and review evidence

### Executive reviews and decisions

- Weekly operating, monthly business, and quarterly strategy reviews
- Section 27 calendar placement and participant notification
- Separate preparation, review, and approval
- Published review locking
- Decision records linked to goals and KPIs
- Optional Section 26 work creation
- Process-aware work references and Section 27 decision deadlines
- Replay-safe decision receipts

### Agent Workspace

The supervised Agent can explain:

- goals and KPIs requiring executive attention
- KPI variance and forecast evidence
- the published process and step responsible for a result
- process exceptions, work items, SLA issues, and capacity constraints
- open decisions and next governed actions

The Agent cannot modify goals, KPI methodology, snapshots, scorecards, reviews, canonical records, process maps, work status, or executive decisions autonomously.

## Security and integrity requirements

- Entity and company scope on every visible object
- Sibling-company IDOR rejection
- Published-process-only KPI bindings
- Active-version process-step validation
- Three-person KPI, scorecard, and executive-review governance
- Published KPI, scorecard, and review immutability
- Historical snapshot immutability
- Replay-safe snapshots and decisions
- Cross-company goal/KPI/review linkage rejection
- Decision-owner separation from review and approval
- CSRF protection on all writes
- Protected CSV exports
- Canonical source and Section 25 process state remain authoritative

## Deferred production migration

`database/20260727_section28_executive_command_goals_performance.sql`

Import only after the Section 27 migration. Do not import `database/gruber_ai_procurement_single_install_v3.sql` into a populated production database.
