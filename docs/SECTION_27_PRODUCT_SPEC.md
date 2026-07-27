# Section 27 — Enterprise Operational Calendar, Notifications & Workload Capacity

## Purpose

Section 27 turns the governed Section 26 work queue into a coordinated daily operating system. It provides shared and personal calendars, deadline synchronization, capacity intelligence, delegation coverage, notification preferences, digest generation, operating cadences, and expiring calendar feeds without changing canonical procurement records.

## Capabilities

- Company- and entity-scoped operational calendar
- Automatic Section 26 work-deadline synchronization
- Company, team, and private event visibility
- Personal notification queue with deduplication
- Adapter-gated email delivery settings and receipts
- Daily, weekly, morning, and end-of-day summary preferences
- Governed workload-capacity profiles and utilization states
- Temporary permission-scoped delegation and out-of-office coverage
- Idempotent daily and weekly digest generation
- Locked daily, weekly, and monthly operating cadences
- Protected CSV and RFC 5545-compatible ICS exports
- Expiring private calendar subscriptions with one-time tokens stored only as SHA-256 hashes
- Agent Workspace calendar, capacity, delegation, and notification briefing

## Security boundary

- Calendar visibility inherits Section 24 operating-entity scope.
- Work deadlines inherit the originating Section 26 entity, not a shared-service assignee’s broader reach.
- Private events are visible only to owners and explicit participants.
- Notification records are visible only to the recipient.
- Delegation does not change identity, company membership, role membership, or canonical permissions.
- Delegation is limited to explicit permission keys, one visible entity, and a bounded date window.
- No Google, Microsoft, SMTP, OAuth, calendar-provider, or bank credentials are stored.
- Calendar feed tokens are displayed once and persisted only as hashes.
- Digests, notifications, work-event synchronization, and subscriptions use replay-safe keys.
- Canonical source records and Section 26 work statuses remain authoritative.

## Production dependency

Import after Sections 11–26:

`database/20260727_section27_operational_calendar_notifications_capacity.sql`

Never import the fresh-install Version 3 schema into a populated production database.
