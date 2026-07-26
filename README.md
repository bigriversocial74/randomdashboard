# Gruber Procurement Intelligence Platform

Production-style PHP procurement platform for the six Gruber companies, with a database-free Demo Data environment and a persistent MySQL Production Data environment.

## Primary routes

```text
/                     Preserved public landing page
/resume.php           Preserved public résumé
/pitch/               Preserved sticky-scroll pitch
/app-prototype/       Preserved original Agent Workspace prototype
/app/                 Operational procurement application
/demo.php             Intentional Demo Mode account selector
/install.php          Disabled installer / manual setup notice
/manual-admin.php     Disabled setup endpoint
/manual-admin-example.php
                      Copy temporarily to a private filename for first-admin setup
/app/admin/           Permission-controlled Admin Console
/app/admin/environment.php
                      Demo Data / Production Data environment control
```

## Phase 2 capabilities

- Shared repository layer for Demo Data and Production Data
- Session-isolated fictional demo dataset and seven demo roles
- Persistent MySQL CRUD for companies, users, roles, suppliers, items, purchase orders, inventory, savings, scorecards, discovery, imports, approvals, comments, notifications, sessions, settings, and audit events
- Strict company scope and server-side permission enforcement
- Workflow progression: Draft → Submitted → Validated or Changes Requested → Approved
- Protected CSV/XLSX staging, mapping, row validation, transaction commit, rollback, and import receipts
- Admin-controlled security, session, environment, and platform policies
- Explicit environment switching with confirmation, fresh authentication, production-session revocation, and no cross-environment copying
- Import-safe fresh installation and v2-to-Phase-2 migration SQL

## Agent Workspace release

The production Agent Workspace now uses the same full-width chat-canvas direction as the demo dashboard. The fixed chat composer includes a **+** quick-prompt menu for leadership issues, inventory transfers, supplier attention, executive briefs, savings opportunities, data-quality review, and résumé review. Selected prompts and typed questions render supervised responses and evidence labels directly in the chat canvas.

The deployment never contains a live `config.php`. Only `config-example.php` and `config-manual-example.php` are included, so extracting the ZIP cannot overwrite hosting credentials.


## Savings realization and guided-tour release

The Savings Opportunity Pipeline now manages value from identification through business-case review, negotiation, approval, implementation, and accounting-validated realization. Opportunity records include baseline cost, expected annual savings, implementation cost, realized savings, confidence, risk, supplier, owner, target date, operational status, accounting review, and next step. The dashboard and Agent Workspace use the same scoped savings records.

Demo Mode also includes a nine-step guided presentation tour spanning the Executive Dashboard, suppliers, purchase orders, inventory, savings, imports, approvals, Daily Briefing, and Agent Workspace. The tour highlights the live page area being discussed and provides previous/next navigation without writing to production data.

No additional SQL migration is required for this release because the Version 3 schema already contains the savings-realization fields used by the application.

## Requirements

- PHP 8.1+
- MySQL 8.0+ for Production Data
- PHP extensions: PDO, `pdo_mysql`, JSON, OpenSSL, session, and Fileinfo
- `mbstring` recommended
- `zip` required only for XLSX imports; CSV remains available without it

## Local Demo Data review

```bash
cd gruber-ai-procurement-system-v1
php -S 127.0.0.1:8080 -t .
```

Open `http://127.0.0.1:8080/app/`, choose **Enter Demo Mode**, and select a role.

## Production installation

The browser SQL installer is intentionally disabled in this deployment. For a database that has already received the Version 3 schema:

1. Copy `config-manual-example.php` (or `config-example.php`) to `config.php`.
2. Enter the real MySQL credentials and support email.
3. Copy `manual-admin-example.php` to a temporary, difficult-to-guess filename.
4. Open that temporary file once to create the first System Administrator.
5. Sign in at `/app/login.php`, then delete the temporary setup file immediately.

For a new empty database, import this corrected schema first:

```bash
mysql -u USER -p DATABASE_NAME < database/gruber_ai_procurement_single_install_v3.sql
```

For an existing v2 installation only, back up the database and apply:

```bash
mysql -u USER -p DATABASE_NAME < database/gruber_ai_procurement_phase2_v3.sql
```

## Environment safety

The Admin Console environment toggle changes the active data source only. It never copies Demo records into MySQL or production records into the PHP session. Production activation requires a working database and production administrator authentication. Production-to-Demo switching revokes the current production application session first.

## Documentation

- `docs/PHASE_2_PRODUCTION_INTEGRATION.md`
- `docs/DEMO_ADMIN_CONSOLE.md`
- `docs/TEST_REPORT.md`
- `database/README.md`
- Original PRD, page map, architecture, agent specification, pitch storyboard, workbook, and launch plan remain preserved under `/docs`.

## Repository

`bigriversocial74/randomdashboard`

Never commit `config.php`, credentials, password hashes, reset tokens, runtime imports, installer locks, logs, or production database exports.


## Quality protocol

The cumulative release is divided into ten scored sections. Run `tests/run-quality.sh` before opening or merging a section PR. The gate validates PHP, JavaScript, account states, section-specific rules, every primary Demo Mode workspace, manual-deployment safety, and the quality evidence under `/docs`.

See `docs/QUALITY_SCORECARD.md` and `docs/SQL_CHANGE_LEDGER.md`.
