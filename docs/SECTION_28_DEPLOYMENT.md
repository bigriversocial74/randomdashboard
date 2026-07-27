# Section 28 Deployment

## Required order

Deploy the application code from the validated merge commit, then import the populated-database migrations in section order. Section 28 depends on Sections 24–27 and must be imported after:

1. `database/20260727_section24_business_entity_integration_foundation.sql`
2. `database/20260727_section25_process_mapping_orchestration_control_intelligence.sql`
3. `database/20260727_section26_enterprise_work_management_command_center.sql`
4. `database/20260727_section27_operational_calendar_notifications_capacity.sql`
5. `database/20260727_section28_executive_command_goals_performance.sql`

All earlier deferred Section 11–23 migrations must already be present.

Do not import `database/gruber_ai_procurement_single_install_v3.sql` into a populated production database.

## Pre-deployment

- Confirm a restorable database backup.
- Preserve production `config.php` and storage.
- Confirm Sections 24–27 schema migrations are recorded.
- Confirm three active internal users exist for preparation, independent review, and approval.

## Verification

1. Open `/app/executive-command.php`.
2. Confirm goals and KPI definitions are filtered by active company/entity scope.
3. Confirm process-bound KPI definitions resolve to published Section 25 processes and active-version steps.
4. Generate KPI snapshots twice and confirm the second run reuses the existing period snapshot.
5. Prepare, review, and publish a scorecard using three separate users.
6. Schedule an executive review and confirm its Section 27 calendar event and notifications.
7. Record a decision with work creation enabled.
8. Confirm the resulting Section 26 work item preserves process-instance and step-instance references when an active matching process exists.
9. Confirm the decision deadline appears in Section 27.
10. Confirm a sibling-company user cannot resolve the goal, KPI, scorecard, review, decision, work, or calendar records.
11. Confirm Agent Workspace explains the KPI using process, work, calendar, capacity, and canonical evidence without changing any records.

Production deployment remains unconfirmed until these checks are completed against the deployed environment.
