# Production Database

## Fresh installation

Use:

`gruber_ai_procurement_single_install_v3.sql`

This is the current MySQL 8.0+ import-safe fresh-install schema. It contains the preserved v2 operational foundation plus Phase 2 production integration, including:

- Six-company enterprise structure
- Users, profiles, roles, permissions, memberships, sessions, access requests, resets, and security events
- Company administration profiles and settings
- Supplier, item/SKU, purchase-order, inventory, savings, scorecard, and discovery records
- Multi-company item and supplier relationships
- Workflow approvals, comments, notifications, data-quality exceptions, and audit logs
- Protected import jobs, mappings, staging rows, validation errors, transaction receipts, and checksums
- System settings, environment policy, health data, reporting views, and schema migration tracking

The SQL does not issue `CREATE DATABASE`, `DROP DATABASE`, or `USE`. Select the intended database before importing.

```bash
mysql -u USER -p DATABASE_NAME < gruber_ai_procurement_single_install_v3.sql
```

## Existing v2 upgrade

Back up the database and apply:

`gruber_ai_procurement_phase2_v3.sql`

```bash
mysql -u USER -p DATABASE_NAME < gruber_ai_procurement_phase2_v3.sql
```

The migration uses existence checks for added columns, tables, and foreign keys, then records schema version `3.0.0-phase2`.

## Preserved files

- `gruber_ai_procurement_single_install_v2.sql` — prior installer baseline
- `gruber_ai_procurement_schema.sql` — original v1 foundation

Never commit `config.php`, production credentials, password hashes, reset tokens, runtime imports, or database exports.
