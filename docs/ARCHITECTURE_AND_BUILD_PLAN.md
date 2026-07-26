# Architecture and Build Plan

## Recommended implementation

### Application
- PHP 8.3+
- Laravel 12-compatible architecture
- MySQL 8.0+
- Blade server-rendered UI for the first release
- Tailwind CSS or a locally compiled design system
- Alpine.js for lightweight interactivity
- Chart.js for dashboards
- Queue workers for imports, report generation and AI briefings
- Object storage or protected local storage for contracts and import files

### Why this approach
- Fits the organization's existing PHP deployment familiarity
- Faster initial delivery than a separate SPA and API stack
- Clear authentication, authorization, queues, validation and migrations
- Can expose an API later for mobile, suppliers, other systems or agent integrations

## Application layers

1. **Presentation layer**
   Dashboards, grids, forms, filters, import mapping and briefing review.

2. **Workflow layer**
   Requests, approvals, purchase orders, receiving, transfers, counts, savings and reviews.

3. **Domain services**
   Pricing comparisons, inventory availability, supplier scoring, project-material variance and savings calculations.

4. **Integration layer**
   CSV/XLSX imports first; ERP/accounting/e-commerce/service integrations later.

5. **AI intelligence layer**
   Read-only snapshots, deterministic calculations, prompt templates, findings and human review.

6. **Audit/security layer**
   Role-based access, company scope, approvals, immutable audit history and data retention.

## First release epics

### Epic 1: Foundation
- Authentication
- Roles and company scope
- App shell
- Audit logging
- Companies, locations and departments

### Epic 2: Master data
- Supplier master
- Item master
- Categories and units
- Duplicate-review workflow

### Epic 3: Imports and data quality
- File upload
- Column mapping
- Validation
- Error review
- Import audit

### Epic 4: Purchasing visibility
- PO list and details
- Open/past-due views
- Supplier acknowledgement
- Assignments and comments

### Epic 5: Inventory visibility
- Inventory snapshot
- Location view
- Aging
- Strategic legacy
- Transfer recommendation workflow

### Epic 6: Supplier performance
- Scorecards
- Contract dates and documents
- Quality events
- Corrective actions

### Epic 7: Savings
- Opportunity pipeline
- Financial baseline
- Implementation tracking
- Accounting validation

### Epic 8: AI briefing room
- Weekly briefing
- Findings queue
- Evidence drawer
- Review and publication

## Suggested 90-day delivery

### Sprint 0: 1 week
- Repository, environments, CI, coding standards and data access

### Sprints 1-2: 4 weeks
- Foundation, companies, users, roles, suppliers and items

### Sprints 3-4: 4 weeks
- Imports, purchase orders, inventory, dashboard and exceptions

### Sprint 5: 2 weeks
- Supplier scorecards and savings pipeline

### Sprint 6: 2 weeks
- AI briefing room, QA, training and pilot launch

## MVP acceptance criteria

- Users can only see companies authorized for their role.
- Supplier and item records have normalized identifiers and audit history.
- PO and inventory files can be uploaded, mapped, validated and imported.
- Dashboard totals reconcile to imported source files.
- Open and past-due POs can be filtered, assigned and actioned.
- Inventory is classified by age without misclassifying approved strategic legacy stock.
- Supplier scorecards retain period-specific measures and action plans.
- Savings cannot be marked validated without an Accounting reviewer.
- AI findings show evidence, confidence, reviewer and status.
- No AI action can directly create an approved PO, post inventory or validate savings.
