# Contract2Close Project Guidelines

## Product Constraints

- C2C Core is a multi-tenant real estate transaction management platform for sales, purchases, residential rentals, commercial sales, and commercial leases.
- C2C Build is out of scope unless a task explicitly says otherwise.
- P1 Critical beta scope is foundation, tenancy/auth, dashboard/navigation, custom e-signature, and AI document read/auto-fill.

## Tenancy

- A tenant is the top-level MLS/association/default C2C isolation and branding boundary.
- A team is a brokerage inside exactly one tenant; users may be unaffiliated or belong to teams within their tenant memberships.
- Tenant-owned aggregate roots and independently accessed tenant-owned records must be scoped by `tenant_id`.
- Deeply contained child records may inherit tenant through their parent only when they are never queried, authorized, reported, audited, queued, or indexed independently.
- Do not write tenant-owned queries without policy coverage or explicit tenant scoping.
- Background jobs must carry tenant context explicitly.
- Cross-tenant leakage is a critical failure and must be covered by tests whenever tenant-owned data is added.

## Dynamic Transaction Fields

- Do not add fixed database columns for customizable transaction facts such as property details, deal terms, due dates, amounts, percentages, checklist answers, or tenant/team-specific labels.
- Use stable field definitions, versioned templates, scoped overrides, typed transaction field values, and audit events for customizable built-in and custom fields.
- Labels, units, formats, option labels, visibility, and required state must be configurable at tenant, team, and user-display levels with user preference, team override, tenant override, template default, and system default precedence.
- Store canonical values separately from display rendering for money, dates, units, booleans, selects, and custom data types.
- Conditional display, required-state rules, calculated fields, and date triggers must use constrained rule/expression data structures, never executable code or label-based references.

## Audit

- Audit records are append-only.
- Do not update or delete audit records.
- Audit important auth, tenancy, transaction, document, AI extraction, e-signature, review, compliance, commission, notification, and integration actions.

## Documents And E-Signature

- Documents are uploaded once and stored in object storage, never as database blobs.
- Reviewers must interact with the canonical document record from role-scoped views.
- No reviewer may edit another reviewer's annotations, notes, or approval status.
- E-signature is a first-party subsystem for beta. Do not introduce provider lock-in without an explicit decision-log update.
- Beta signing scope includes PDF preview, AcroForm import, add/delete/edit fields, signer assignment, routing, typed/drawn signatures, initials, dates, consent, final package, tamper hash, and audit certificate.

## AI Extraction

- AI document extraction must go through a provider abstraction.
- Store every extracted field, extracted value, confidence score, confirmation/correction, and timestamp.
- High-confidence values may auto-apply; low-confidence values must be prefilled for review.

## UI And UX

- Use Flux UI Pro as the baseline component system.
- Keep the app light-first, polished, uncluttered, and balanced in information density.
- Authenticated app navigation uses a sidebar, contextual record tabs, and a collapsible pinned transaction rail.
- Primary dashboard content is an action queue, not generic reporting cards.
- Every Livewire UI must include appropriate empty, loading, validation, error, and permission-denied states.

## Testing

- Every behavior change needs automated tests.
- Prefer Pest tests and Laravel feature tests for workflows.
- Add policy tests for authorization, tenant isolation tests for tenant-owned models, Livewire component tests for interactive UI, and job tests for queued behavior.
- Do not mark a phase complete until its tests and relevant regression tests pass.
