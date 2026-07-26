# Demo Mode and Admin Console

## Purpose

The production-style `/app/` interface can now be reviewed before a `config.php` file or MySQL database exists. Demo Mode is an intentional, visibly labeled environment. Its records are fictional, remain isolated in the PHP session, and are never written to the production database.

## Entry points

- `/app/` — environment gate when production is not configured
- `/demo.php` — Demo Mode account and role selector
- `/install.php` — production MySQL installer
- `/app/login.php` — production account sign-in after installation
- `/app/admin/` — Admin Console for authorized accounts
- `/app/agent.php` — supervised Agent Workspace

## Demo accounts

Select an account from `/demo.php`; passwords are not required in the isolated review environment.

| Role | Demo email | Scope |
|---|---|---|
| System Administrator | `admin@demo.gruber.test` | All six companies and all administration |
| Executive | `executive@demo.gruber.test` | Enterprise reporting and approved records |
| Company Administrator | `companyadmin@demo.gruber.test` | Assigned companies and company administration |
| Procurement Manager | `procurement@demo.gruber.test` | Procurement workflows across all six companies |
| Data Contributor | `contributor@demo.gruber.test` | Assigned company data creation and submission |
| Reviewer | `reviewer@demo.gruber.test` | Review, validation, change requests, and approvals |
| Read Only | `readonly@demo.gruber.test` | View-only access |

## Six-company dataset

The fictional dataset covers:

1. Gruber Communications
2. Gruber Power Services
3. Gruber Technical Services
4. Gruber Motor Company
5. EV Preserve
6. Gruber Commercial Properties

It includes users, roles, memberships, discovery, suppliers, contacts, supplier-company relationships, contracts, items, SKUs, purchase orders, lines, open commitments, inventory locations, snapshots, aging, savings, scorecards, exceptions, notifications, approvals, comments, audit events, import jobs, import errors, receipts, sessions, access requests, and security events.

## Persistence and resets

Demo changes are stored only in the active PHP session. Use **Demo controls** in the application status bar to:

- Reset Demo Data while retaining the active account and scope
- Restore the complete default dataset
- Clear the Demo Session and return to the account selector

The protected `/storage/demo/` directory contains no public record export. It is denied by `.htaccess` and an `index.php` guard.

## Permission behavior

Navigation and actions are permission-aware. Restricted pages are not shown, and direct route access returns an access-denied screen. System roles are protected from deletion. Company-scoped users can switch only among assigned companies.

## Admin Console coverage

The Admin Console provides:

- Dashboard metrics and security activity
- User creation, editing, activation, suspension, archiving, restoration, password reset simulation, role assignment, company assignment, primary company selection, notes, and session revocation
- Role creation, editing, cloning, archival, system-role protection, and permission matrix review
- Company profiles, ownership, memberships, completion, module enablement, retention, and company settings
- Session review and revocation
- Access-request review and assignment
- Password, login, lockout, reset, and session policy controls
- Immutable-style audit viewer with filters and before/after details
- Platform settings and environment diagnostics

## Production installation

1. Confirm PHP 8.1+ and MySQL 8.0+.
2. Ensure the web server can write `config.php` during installation, or copy `config-example.php` manually.
3. Open `/install.php`.
4. Enter a MySQL account allowed to create the target database and tables.
5. Create the first System Administrator with a password of at least 12 characters with mixed case, a number, and a symbol.

The installer uses:

`/database/gruber_ai_procurement_single_install_v3.sql`

The SQL is import-safe for repeated schema initialization and includes the preserved operational schema plus the Phase 2 production repository, multi-company relationships, workflow persistence, import staging, environment controls, administration, audit, and security tables. Existing v2 installations can use `/database/gruber_ai_procurement_phase2_v3.sql` after a verified backup.

## Security notes

- Demo Mode never authenticates against or writes to MySQL.
- Production passwords use PHP `password_hash()` and `password_verify()`.
- Sessions use HTTP-only, SameSite=Lax cookies and regenerate on login.
- Login attempts are throttled using the active Admin Console security policy.
- Production session records can be revoked and use configurable idle expiration.
- Environment switching is audited and revokes the production application session before entering Demo Data.
- CSRF tokens protect all mutable form actions.
- Demo audit events are append-only in the session interface.
- Secrets belong in `config.php`, which is excluded by `.gitignore`.
