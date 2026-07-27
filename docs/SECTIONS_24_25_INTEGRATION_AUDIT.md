# Sections 24–25 Integration Audit

## Scope

Combined audit of Section 24 modular business entities and Section 25 visual process mapping, including company isolation, shared-service routing, process assignment, live execution, canonical-record linkage, template governance, integrations, exports, Demo Mode, and cumulative database compatibility.

## Initial score

- Section 24 multi-corporation governance: **7.1/10**
- Section 25 process mapping and orchestration: **6.8/10**
- Cross-feature integration: **5.9/10**

## Material findings

### P0 — none

No destructive migration, canonical company-ID replacement, or transaction-relationship loss was found.

### P1 — repaired

1. Supplemental Section 24 collections were not consistently filtered to the current user's permitted entity scope.
2. Section 25 process definitions, instances, exceptions, events, and analytics were not consistently filtered through Section 24 entity assignments.
3. Live process step instances were assigned to the initiating company instead of the entity represented by each swimlane.
4. A process could be started against a canonical record belonging to a different company entity.
5. Multiple active instances could be created for the same process, entity, and canonical record.
6. Step advancement did not enforce the permission declared by the visual process step.

### P2 — repaired

1. Demo Mode used entity codes that differed from the production migration, leaving visual swimlanes without reliable entity bindings.
2. Enterprise template application allowed insufficient preparation/review/approval separation.
3. Scoped readiness could treat hidden sibling companies as missing bindings.
4. Active exception reviewers were not validated before assignment.
5. The cumulative quality gate did not render the entity or process workspaces or execute an adversarial cross-feature identity-switching audit.

## Repair controls

- Entity repositories derive an explicit visible-entity set from permitted company memberships, direct shared-service relationships, parent context, and data-authority relationships.
- Integration connections, bindings, mappings, events, conflicts, and entity events are filtered through that visible set.
- Process visibility is derived from active entity assignments and visible live instances.
- Versions, lanes, steps, transitions, controls, integrations, instances, step instances, exceptions, events, analytics, and exports inherit process/entity scope.
- Live process startup verifies a published assigned process, a permitted operating entity, canonical-record company alignment, required evidence, and active-instance uniqueness.
- Requesting and receiving lanes follow the initiating operating entity; shared procurement and shared finance lanes retain their Section 24 shared-service entities; external supplier lanes remain external.
- Step advancement enforces the step's declared application permission and visible assigned entity.
- Enterprise template creation and publication require Enterprise View and three distinct active governance participants.
- Published process versions remain immutable.
- Canonical operational statuses remain authoritative; the process engine remains supplemental.

## Validation requirements

- PHP 8.1 and PHP 8.3 cumulative quality
- Adversarial enterprise/company/contributor/procurement identity tests
- Entity-system and process-map page rendering
- MySQL 8 cumulative and repeat-safe Sections 11–25 migrations
- MariaDB 10.11 cumulative and repeat-safe Sections 11–25 migrations
- Six one-to-one canonical company bindings
- Correct production shared-service lane bindings
- No unresolved review threads
- Exact-head retained Sections 11–25 workflows

## Release decision

Final certification remains pending until the repair PR's exact head passes every focused, repository-wide, database, and retained workflow gate.
