# Contract2Close v3 Architecture Notes

## Baseline Stack

- Laravel app scaffolded from the official Livewire starter kit.
- PHP 8.4.1 or newer.
- Livewire, Blade, Tailwind, and Flux UI Pro for frontend.
- MySQL as the relational database.
- Redis-compatible cache and queues.
- S3-compatible object storage for documents, signed packages, previews, and generated artifacts.
- Laravel Boost for package-aware guidelines, code standards, documentation lookup, and project-specific AI instructions.
- Pest for tests and Laravel Pint for formatting.

## Auth And Identity

- Preserve starter-kit auth paths: registration, login, password reset, email verification, and Fortify-backed two-factor authentication.
- Use the built-in Livewire starter kit path first, not WorkOS AuthKit.
- Keep identity extensible for future Google, Microsoft, and MLS association SSO.
- Model tenant membership separately from users so one person can belong to multiple tenants if needed.
- Every authenticated request must resolve current tenant context before accessing tenant-owned data.

## Tenancy

- Use a single MySQL database with `tenant_id` on tenant-owned records.
- Tenant-owned models must use policies, scoped queries, tenant-aware factories, and tests that prove cross-tenant isolation.
- Background jobs must carry tenant context explicitly.
- Admin/global operations must be intentionally marked and covered by tests.
- Tenant-specific branding, labels, templates, workflows, integrations, sender identities, and notification preferences must never leak across tenants.

## Audit

- Audit logs are append-only.
- Do not update or delete audit events.
- Every audit event should identify tenant, actor, subject, action, timestamp, source, and relevant metadata.
- Audit coverage starts in Phase 1 and expands with every feature phase.
- E-signature, AI extraction, document review, compliance, and commission workflows require especially detailed audit trails.

## Documents

- Store binary documents outside the application database.
- Application database stores metadata, ownership, versioning, review state, folder assignment, extraction state, signing state, and audit references.
- The filesystem is treated as ephemeral for deployment; persistent documents belong in object storage.
- A document is uploaded once. Reviewers, signers, agents, and back-office users interact with the same canonical document record.
- Versioning must preserve prior uploaded/generated files and associated audit events.

## Custom E-Signature

- Build a first-party signing core for beta instead of relying on a provider-specific workflow.
- Required beta scope: PDF preview, AcroForm import, add/delete/edit fields, non-fillable PDF field placement, signer assignment, routing order, typed/drawn signatures, initials, dates, consent, reminders, status tracking, final signed package, audit certificate, and tamper hash.
- Reusable signing templates, conditional fields, and bulk send are out of beta scope.
- Legal scope starts with US ESIGN/UETA assumptions; production signing requires attorney review.

## AI Extraction

- Build provider abstraction before selecting a production provider.
- Bakeoff should test representative purchase agreements, lease agreements, listing agreements, disclosures, and lender documents.
- Extraction flow: classify document type, extract values, map to transaction fields, route to folder, show confirmation summary.
- Store field name, extracted value, confidence score, agent confirmation/correction, and timestamp.
- High-confidence fields may auto-apply; low-confidence fields must be prefilled and flagged for review.

## Localization

- No hardcoded user-facing strings.
- Beta supports English and Spanish.
- Store user-level locale preference independent of tenant.
- Notifications, renewal alerts, and property cards must render in recipient language.
- Date, currency, and area units follow locale defaults.

## UI System

- Flux UI Pro is the baseline.
- App shell uses persistent sidebar, top action/search area, notification badge, and tenant/user controls.
- Transaction rail is collapsible and shows active/recent transactions, statuses, and quick filters.
- Transaction workspace uses a summary header and contextual tabs.
- Use progressive disclosure for dense workflows.
- Every UI workflow needs empty, loading, success, warning, error, and permission-denied states.
- Mobile must support primary workflows, especially dashboard actions, transaction switching, document review, and signing.

## Testing Standards

- Every feature phase includes tests before it is considered complete.
- Required coverage types: unit tests for domain logic, feature tests for workflows, policy tests for authorization, Livewire component tests, job/queue tests, tenant isolation tests, and regression tests for fixed bugs.
- CI should run tests, formatting checks, migration checks, and frontend build.
- Factories and seeders should support realistic multi-tenant examples.
- Critical workflows require end-to-end style coverage: auth, tenancy, dashboard role views, e-signature, AI extraction, single-upload review, compliance gate, commission calculation, and notifications.

## Laravel Cloud Assumptions

- Persistent files use object storage, not local disk.
- Queue workers run separately from web requests.
- Environment variables and secrets are managed per environment.
- Build and deploy must not rely on mutable local filesystem state.
- MySQL, cache/queue, object storage, and scheduled commands must be environment-configurable from the beginning.
