# Phase 2 — Production Data Integration & Operational Hardening

## Environment model

The same `/app/` interface runs against one of two adapters:

- **Demo Data:** fictional records held in the current PHP session
- **Production Data:** persistent records read and written through prepared PDO/MySQL operations

The Admin Console environment page is `/app/admin/environment.php`.

Switching requires `platform.administer`, a CSRF token, browser confirmation, and the typed value `SWITCH`. Production activation additionally requires `config.php`, a working MySQL connection, and a fresh production administrator login. Switching from Production to Demo revokes the current production application session. No records are copied between adapters.

## Repository architecture

```text
/includes/data/repository.php          Shared data facade and scope helpers
/includes/data/mysql_repository.php    Production PDO repository
/includes/data/actions.php             Permission-checked application mutations
/includes/data/import_engine.php       Upload, mapping, validation, and commit engine
/includes/demo/                        Isolated sample repository
/storage/imports/                      Protected runtime import staging
/database/                             Fresh installer and upgrade migration
```

Application pages call the shared facade rather than branching between two separate applications.

## Production coverage

Production CRUD and normalized read models cover:

- Companies and company administration profiles
- Users, profiles, roles, permissions, and memberships
- Suppliers, contacts, categories, and company relationships
- Items/SKUs and multi-company item relationships
- Purchase orders and line items
- Inventory locations and balances
- Savings opportunities and supplier scorecards
- Discovery assignments and questionnaire responses
- Notifications, comments, approvals, sessions, access requests, security events, settings, and audit logs
- Import jobs, mappings, staged rows, errors, and receipts

## Scope and authorization

Every write action repeats authorization and company-scope validation on the server. Restricted navigation is hidden, but security does not depend on the UI. Direct POST attempts are rejected for:

- Unauthorized companies
- Out-of-scope users, suppliers, items, locations, buyers, or owners
- Disallowed role elevation
- Invalid workflow transitions
- Immutable company/security import errors
- Missing or invalid CSRF tokens

## Workflow lifecycle

Operational source records use:

`Draft → Submitted → Validated → Approved`

A reviewer may return a submitted or validated record to `Changes Requested`, after which it can be resubmitted. Submission creates a persisted workflow approval. Approving or requesting changes on the approval propagates the result back to the source record and writes audit history.

## Import engine

- CSV support without optional extensions
- XLSX support when PHP `zip` is installed
- Protected storage outside normal public browsing
- Configurable upload and row limits
- Automatic and manual column mapping
- Required-value, identifier, numeric, relationship, and company-scope validation
- Scope errors cannot be ignored or accepted
- Production commit inside a database transaction
- Rollback on any failed row
- Persisted commit receipt and SHA-256 evidence

## Installation

Fresh installations use `database/gruber_ai_procurement_single_install_v3.sql`.
Existing v2 databases use `database/gruber_ai_procurement_phase2_v3.sql` after a verified backup.

The browser installer creates or connects to the selected database, installs the schema, creates the first System Administrator, writes `config.php`, records an audit event, and creates `storage/installed.lock`.
