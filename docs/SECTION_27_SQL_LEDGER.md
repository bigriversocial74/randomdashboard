# Section 27 SQL Ledger

Deferred migration:

`database/20260727_section27_operational_calendar_notifications_capacity.sql`

- Dependencies: corrected Version 3 schema and Sections 11 through 26.
- Purpose: creates the operational calendar, notification preferences and queue, capacity profiles, delegations, digest runs, private calendar subscriptions, and governed operating cadences.
- Canonical boundary: Section 27 schedules and summarizes existing work; it does not replace Section 26 work records or canonical procurement statuses.
- Credential boundary: stores no Google, Microsoft, SMTP, OAuth, or calendar-provider credentials. Subscription tokens are persisted only as SHA-256 hashes.
- Idempotency: uses `CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE`, unique source/dedupe/idempotency keys, and a repeat-safe `schema_migrations` record.
- Compatibility gate: the Section 27 workflow imports this migration twice on MySQL 8.0 and MariaDB 10.11.
- Required import order: Version 3 fresh schema only for new installations; for populated installations import Sections 11 → 12 → 13 → 14 → 15 → 16 → 17 → 18 → 19 → 20 → 21 → 22 → 23 → 24 → 25 → 26 → 27.
- Existing installation behavior before import: Demo Mode remains fully available; Production Data calendar, capacity, delegation, notification, digest, cadence, and subscription writes remain blocked until all Section 27 tables exist.
- Deployment warning: never import `database/gruber_ai_procurement_single_install_v3.sql` into a populated production database.
