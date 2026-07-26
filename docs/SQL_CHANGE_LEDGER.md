# SQL Change Ledger

## Quality catch-up pass

No incremental SQL migration was added for Sections 1–10.

The application continues to rely on the existing corrected Version 3 schema imported manually from:

`database/gruber_ai_procurement_single_install_v3.sql`

The quality pass changed PHP, JavaScript, documentation, tests, configuration examples, and CI only. No additional SQL import is required when these changes are deployed over an existing Version 3 database.

## Deployment rule

- Never import the fresh-install schema into a populated production database.
- Keep `config.php` outside deployment packages and repository commits.
- If a future section introduces SQL, record the migration filename, dependencies, idempotency, MySQL/MariaDB validation, and required import order in this ledger.
