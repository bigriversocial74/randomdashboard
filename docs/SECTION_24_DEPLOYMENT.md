# Section 24 Deployment Addendum

Deferred migration: `database/20260727_section24_business_entity_integration_foundation.sql`.

Import after Sections 11 through 23. Do not reimport `database/gruber_ai_procurement_single_install_v3.sql` into a populated database. The migration preserves all existing company IDs and transaction relationships, backfills six operating entity bindings, creates Gruber Enterprise and shared Procurement/Finance entities, carries active company memberships into entity access scopes, and adds integration connections, bindings, external ID mappings, idempotent events, conflicts, and immutable governance history. Production import and deployment remain unconfirmed until explicitly verified.
