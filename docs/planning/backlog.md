# Contract2Close v3 Backlog

## Phase 0 - Setup (8)

- Create and maintain planning artifacts under `docs/planning` (1).
- Scaffold Laravel using the official Livewire starter kit (2).
- Configure MySQL defaults and local environment baseline (1).
- Install Laravel Boost and add C2C-specific AI guidelines (2).
- Establish test, formatting, static-analysis, and CI expectations (2).

## Phase 1 - Foundation (21)

- Define single-database tenant strategy with required `tenant_id` scoping (5).
- Create core model architecture for tenants, users, roles, transactions, documents, contacts, milestones, audit logs, forms, listings, leases, document reviews, document extractions, lease notifications, and property distributions (8).
- Establish append-only audit event model and conventions (3).
- Configure queues, cache, object storage, and document persistence assumptions for Laravel Cloud (3).
- Add baseline feature, policy, model, and tenancy tests (2).

## Phase 2 - Identity, Tenancy, Branding, Localization (34)

- Customize starter-kit auth while preserving Fortify-backed login, registration, password reset, email verification, and 2FA paths (5).
- Add tenant membership, role definitions, role permissions, and per-tenant authorization policies (8).
- Add tenant branding settings for logo, colors, sender identity, and visible integrations (5).
- Add English/Spanish localization framework and locale-aware formatting defaults (5).
- Add SSO-ready identity/linking model for future Google, Microsoft, and MLS association login (5).
- Add cross-tenant leakage, role, localization, and auth regression tests (6).

## Phase 3 - Dashboard And Navigation (42)

- Build Flux-based app shell: sidebar, top action area, notification badge, and responsive mobile layout (8).
- Build collapsible pinned transaction rail with active/recent transactions, quick filtering, status chips, and empty states (8).
- Build action-queue-first dashboards for Agent, Coordinator/Assistant, Broker/Admin, and Back-Office roles (13).
- Build global search shell across transactions, contacts, documents, and forms (5).
- Build tenant-branded partner tab framework visible on dashboard and relevant sections (3).
- Run UI acceptance for desktop/mobile, keyboard navigation, loading/empty/error states, and feature-finding usability (5).

## Phase 4 - Custom E-Signature Core (55)

- Build PDF ingestion and browser preview pipeline (5).
- Import AcroForm fields and convert them into editable in-app fill/sign fields (8).
- Add field designer for adding, deleting, moving, assigning, and editing fields on fillable or non-fillable PDFs (13).
- Add signer assignment, ad-hoc signers, routing order, status tracking, reminders, and field-correction support (8).
- Add typed/drawn signatures, initials, dates, signer consent, final package generation, tamper hashes, and audit certificate (13).
- Auto-store final signed package in the transaction document folder and integrate status with document color system (3).
- Add tests for routing, field placement, audit, consent, final package, reminders, and failure recovery (5).

## Phase 5 - AI Document Read And Auto-Fill (34)

- Define extraction provider interface and run provider bakeoff using representative real estate documents (5).
- Build document classification pipeline for purchase agreements, lease agreements, listing agreements, disclosures, and lender docs (5).
- Build field extraction, confidence scoring, field mapping, and folder routing (8).
- Build confirmation/correction UI with one-click confirm for clean documents (5).
- Log all extraction values, confidence scores, confirmations, corrections, and timestamps in audit trail (3).
- Add extraction fixture tests and regression cases for low-confidence fields and conflicting values (8).

## Phase 6 - Core Transaction Engine (34)

- Implement transaction CRUD for residential sale, purchase, rental, commercial sale, commercial lease, and custom (8).
- Implement lifecycle states and transitions by transaction type (5).
- Implement property and lease core fields, including renewal lead time and automatic alert date calculation (8).
- Implement transaction workspace tabs, activity feed, search, filters, and audit hooks (8).
- Add lifecycle, validation, policy, and audit tests (5).

## Phase 7 - Single-Upload Document Review (34)

- Build one-upload document storage and reviewer assignment (5).
- Build private reviewer notes, annotations, and approval statuses per reviewer (8).
- Build document status panel with reviewer color, timestamp, and status summary (5).
- Build folder/transaction/back-office/commission color rollups (5).
- Enforce no cross-reviewer edits and commission gate hooks (5).
- Add upload, reviewer privacy, status rollup, and audit tests (6).

## Phase 8 - Templates, Fields, Rules (34)

- Build template builder for all transaction types (8).
- Build tenant-scoped field label editor and custom field types (8).
- Build milestones, checklists, timing offsets, duplication, versioning, and permissions (13).
- Add field isolation, template versioning, and workflow rule tests (5).

## Phase 9 - Forms Library (21)

- Build searchable forms library with user-uploaded and MLS-preloaded source badges (5).
- Add frequency ranking and contextual tabs by transaction section (5).
- Integrate pull-fill-sign inline workflow with e-signature core (8).
- Add search, ranking, access, and fill/sign integration tests (3).

## Phase 10 - Teams And Contacts (21)

- Build team/brokerage shared accounts, role assignment, and per-transaction overrides (5).
- Build CRM-capable contact records and dropdown autofill for repeat contacts (5).
- Build section-based access, update notifications, contact document associations, and team nudges (8).
- Add contact, permission, and notification tests (3).

## Later Phases

- Phase 11 Rental & Lease Management + Renewal Notifications (21).
- Phase 12 PDF Forms & Compliance (34).
- Phase 13 Commission Engine & Financial Dashboards (34).
- Phase 14 Notifications, Milestones & Calendar Integration (21).
- Phase 15 Property Details, Listing Sheet & WhatsApp/META Distribution (34).
- Phase 16 Workflow Automation (21).
- Phase 17 MLS Feed Integration, parallel workstream (34).
- Phase 18 Vertical Integrations (21).
- Phase 19 Migration, Hardening & Launch Readiness (34).
