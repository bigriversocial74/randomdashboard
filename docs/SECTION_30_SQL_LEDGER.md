# Section 30 SQL Ledger

## Deferred migration

`database/20260727_section30_policy_sop_knowledge_adoption.sql`

## Dependencies

- Corrected Version 3 fresh-install schema for new installations only
- Deferred migrations for Sections 11 through 29
- Section 25 published process and runtime tables
- Section 26 work-management tables
- Section 27 calendar and notification tables
- Section 28 executive intelligence tables
- Section 29 strategy portfolio and initiative tables

## Purpose

The migration creates ten governed tables for policy documents, immutable versions, source links, change impacts, training campaigns, learner assignments, competency attestations, waivers, adoption snapshots, and evidence manifests. It also adds thirteen `knowledge_adoption.*` permissions and assigns them to existing roles using least-privilege defaults.

## Idempotency

- Uses `CREATE TABLE IF NOT EXISTS`
- Uses `INSERT IGNORE` for permissions, role permissions, and the schema migration record
- Uses unique policy-version, source-link, campaign, assignment, attestation, snapshot, and manifest keys
- The Section 30 CI workflow imports the migration twice on both MySQL 8.0 and MariaDB 10.11

## Compatibility gate

Merge requires successful cumulative imports on:

- MySQL 8.0
- MariaDB 10.11

It also requires the complete PHP 8.1 and PHP 8.3 Sections 1–30 quality suite.

## Required import order

1. Existing corrected production schema and Sections 11–29 migrations, as applicable
2. `database/20260727_section30_policy_sop_knowledge_adoption.sql`

## Existing installation behavior before import

Demo Mode remains available. Production Data reads return no Section 30 records, and Section 30 writes intentionally fail with an import instruction until all ten tables exist.

## Deployment restriction

Never import `database/gruber_ai_procurement_single_install_v3.sql` into a populated production database. Preserve the live `config.php` and complete a database backup before applying the deferred migration.
