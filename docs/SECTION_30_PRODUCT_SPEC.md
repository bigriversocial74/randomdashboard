# Section 30 Product Specification

## Enterprise Policy, SOP, Knowledge & Change Adoption

### Purpose

Section 30 turns approved strategic and process changes into governed operating knowledge and measurable adoption. It does not replace Section 25 process maps, Section 29 initiatives, Section 26 work, Section 27 calendars, Section 28 performance intelligence, or Section 20 benefits. It connects them through controlled policy versions, training, competency evidence, waivers, snapshots, and evidence manifests.

## Required operating chain

1. Section 29 supplies the approved or executing initiative.
2. Section 25 supplies the active published process, version, step, and runtime evidence.
3. Section 30 produces an immutable policy, SOP, procedure, checklist, or work instruction.
4. Approved impact assessments identify affected companies, entities, roles, systems, and required actions.
5. Published versions launch role-targeted training campaigns.
6. Training assignments create Section 26 work and Section 27 deadlines while preserving Section 25 runtime references.
7. Learners attest against the exact published content hash and complete a competency check.
8. An independently assigned validator reviews competency evidence.
9. Time-limited waivers require separate request, review, approval, expiration, and compensating controls.
10. Immutable adoption snapshots and evidence manifests support Section 28 executive reporting, audits, and Section 29 benefit validation.

## Functional scope

- Enterprise and company policy libraries
- Policy, SOP, procedure, work-instruction, and checklist document types
- Draft, submit, review, publish, supersede, and retire lifecycle
- Immutable published versions and SHA-256 content hashes
- Approved Section 29 initiative linkage
- Active published Section 25 process, version, and step linkage
- Extensible links to initiatives, processes, steps, controls, roles, systems, suppliers, and contracts
- Company, entity, role, system, and process change-impact assessments
- Independent impact review and approval
- Self-paced, instructor-led, and blended training campaigns
- Required score, due date, renewal period, owner, reviewer, and approver
- Replay-safe learner assignments
- Section 26 work-item creation
- Section 27 calendar deadline creation
- Section 25 process-instance and step-instance preservation
- Learner acknowledgement, quiz score, and competency evidence
- Independent competency validation and immutable attestations
- Remediation status for failed competency checks
- Time-limited waivers and compensating controls
- Adoption and pass-rate snapshots
- Protected adoption CSV export
- Hash-locked JSON evidence manifests
- Agent Workspace policy, training, waiver, and adoption analysis

## Governance boundaries

- Published content cannot be edited.
- Supersession closes the prior effective period without changing prior content or hash.
- Policy preparation, review, and publication approval must be performed by different assigned users.
- Impact preparation, review, and approval must remain separated.
- Training owner, reviewer, and approver must remain separated.
- Learner and competency validator must be different users.
- Waiver requester, reviewer, and approver must remain separated.
- Training cannot launch without an approved locked impact assessment for the target entity.
- Policy source bindings cannot point to sibling-company initiatives or unsupported process versions.
- Section 30 never directly edits a Section 25 published process or a Section 29 initiative.
- Agent recommendations remain advisory and cannot publish, assign, attest, validate, waive, snapshot, or alter evidence.

## Data model

Section 30 adds ten tables:

- `enterprise_policy_documents`
- `enterprise_policy_versions`
- `enterprise_policy_links`
- `enterprise_change_impacts`
- `enterprise_training_campaigns`
- `enterprise_training_assignments`
- `enterprise_training_attestations`
- `enterprise_policy_waivers`
- `enterprise_adoption_snapshots`
- `enterprise_policy_evidence_manifests`

Schema version: `5.9-section30`.

## Deployment

Import after Sections 11–29:

`database/20260727_section30_policy_sop_knowledge_adoption.sql`

Do not import `database/gruber_ai_procurement_single_install_v3.sql` into a populated production database.
