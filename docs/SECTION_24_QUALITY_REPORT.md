# Section 24 Quality Report — Modular Business Entity & Integration Foundation

## Initial audit

**2.4/10** — the six businesses were operationally integrated through `company_id`, memberships, shared suppliers/items, contracts, and Enterprise View, but there was no explicit organizational hierarchy, shared-service model, effective-dated entity relationship, reusable operating template, data-authority policy, entity-scoped integration binding, external-ID mapping, idempotent integration event, or conflict-governance layer.

## Final target

**10/10** after exact-head PHP 8.1/8.3, MySQL 8, MariaDB 10.11, retained workflow, rendering, permission, migration, and review-thread gates pass.

## Delivered controls

- Preserves all six canonical `companies.id` values and every existing transaction foreign key.
- Backfills a root enterprise entity, two shared-service entities, six operating entities, and one-to-one company bindings.
- Carries active company memberships into additive entity access scopes.
- Preserves supplier-company, item-company, contract, location, department, project, PO, invoice, payment, and savings relationships in place.
- Adds effective-dated ownership, service, consolidation, purchasing, payment, and reporting relationships.
- Adds reusable, versioned entity templates with preview, validation, application hashes, independent review, immutable events, and controlled rollback.
- Includes nine starting templates, led by the six-business Gruber shared-services blueprint.
- Adds module inheritance and per-domain data-authority policies.
- Adds integration connections containing external secret references only—never raw credentials or banking details.
- Adds entity bindings, external organization/company/tenant IDs, sync direction, domain authority, and effective dates.
- Adds provider-neutral external-ID mappings for companies, suppliers, items, locations, departments, projects, invoices, payments, and other domains.
- Adds integration inbox/outbox events with idempotency keys and payload checksums.
- Adds sync-run evidence, conflict ownership, independent resolution, and immutable governance history.
- Production writes fail closed before migration; Demo Mode remains functional.

## Release evidence

- `tests/section24_business_entities.php`
- `.github/workflows/section24.yml`
- `database/20260727_section24_business_entity_integration_foundation.sql`
- all primary Demo Mode pages, including `entity-system.php`
