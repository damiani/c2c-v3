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

- A tenant is the top-level distribution and isolation boundary. Typical tenants are large organizations, such as regional MLS or association partners, that make the app available to their members under a special arrangement; the default `C2C` tenant contains retail and unaffiliated users.
- A team is a brokerage or brokerage-style group inside exactly one tenant. Team admins can manage team members, see team transactions, and aggregate reporting for transaction volume, commission values, and operational activity. Individual users may or may not belong to a team.
- Use a single MySQL database with `tenant_id` on tenant-owned aggregate roots and independently accessed tenant-owned records.
- Deeply contained child records may inherit tenant through their parent when they are never queried, authorized, reported, audited, queued, or indexed independently.
- Tenant-owned models must use policies, scoped queries, tenant-aware factories, and tests that prove cross-tenant isolation.
- Background jobs must carry tenant context explicitly.
- Admin/global operations must be intentionally marked and covered by tests.
- Tenant-specific branding, labels, templates, workflows, integrations, sender identities, and notification preferences must never leak across tenants.
- Team-specific settings, templates, reports, and permissions must remain scoped to the team's tenant.

## Dynamic Transaction Fields

- Do not model customizable transaction facts as fixed business columns. The v2 property/deal schema is kept as a reference inventory in `docs/reference/v2-property-fields`, but v3 should use dynamic field definitions, template fields, and typed transaction field values.
- Field definitions use stable keys/IDs that do not change when labels change. AI extraction mappings, form fill mappings, calculations, date triggers, and workflow rules must reference stable field identifiers instead of display labels.
- Field labels, units, formats, visibility, required state, and option labels resolve by precedence: user display preference, team/brokerage override, tenant override, template default, system default.
- User-level field customization is personal display preference by default. Shared workflow behavior belongs to tenant/team templates and permissions.
- Store canonical values separately from display rendering. Money stores amount and currency; dates store date or ISO datetime values; unit-aware quantities store canonical units and render through resolved unit preferences.
- Transaction field values should be tenant-scoped directly because they will be searched, reported, extracted into, audited, recalculated, and used by queued reminders/triggers across transactions.
- Transaction templates are versioned. A transaction pins to the template version used at creation so future template edits do not silently change historical transactions.
- Conditional display, required-state rules, calculated fields, and date triggers use constrained JSON rule/expression structures, never executable code.
- Calculated field outputs are persisted with source metadata, formula version, dependency hash, and audit events so historical math can be inspected.

## Audit

- Audit logs are append-only.
- Do not update or delete audit events.
- Every audit event should identify tenant, actor, subject, action, timestamp, source, and relevant metadata.
- Audit coverage starts in Phase 1 and expands with every feature phase.
- E-signature, AI extraction, document review, compliance, and commission workflows require especially detailed audit trails.

## Documents

- Store binary documents outside the application database.
- Application database stores metadata, ownership, versioning, review state, folder assignment, extraction state, signing state, and audit references.
- Application code reads the configured document storage disk from `documents.storage.disk`. Local development defaults to the private local `documents` disk; deployed environments should set `DOCUMENT_STORAGE_DISK=s3` or another S3-compatible disk.
- Document object paths must be tenant-scoped and live under the configured `DOCUMENT_STORAGE_PATH_PREFIX` so documents, signed packages, previews, and generated artifacts can share one bucket without path collisions.
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
