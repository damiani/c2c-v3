# Contract2Close v3 Backlog

## Phase 0 - Setup (8)

- Create and maintain planning artifacts under `docs/planning` (1).
- Scaffold Laravel using the official Livewire starter kit (2).
- Configure MySQL defaults and local environment baseline (1).
- Install Laravel Boost and add C2C-specific AI guidelines (2).
- Establish test, formatting, static-analysis, and CI expectations (2).

## Phase 1 - Foundation (21)

- Define single-database tenant strategy, tenant vs. team boundaries, and direct `tenant_id` scoping rules for tenant-owned aggregate roots and independently accessed records (5).
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

## Phase 4 - Core Transaction Management MVP (102)

- Build dynamic transaction field and template foundation: stable field definitions, versioned transaction templates, scoped labels/units/formats, typed values, default residential sale template seed, and tenant-safe resolution rules (34).
- Build transaction create/edit workflows: template selection, generated fields, typed custom values, validation feedback, and edit-after-create behavior (34).
- Build transaction workspace and progress tracking: transaction list/detail surfaces, lifecycle status, milestones/checklist progress, key dates, activity feed surface, filters, and pinned-rail/detail-page continuity (34).

## Phase 5 - Transaction Field Customization Administration (34)

- Build tenant/team/user customization UI and services for field labels, units, display formats, required/visible state, select option labels, and custom field definitions (13).
- Build template composition workflows for suppressing default fields, adding custom fields, and versioning bespoke residential sale and later transaction-type templates (13).
- Add tenant/team/user override precedence, value-preservation, permissions, and regression tests (8).

## Phase 6 - Transaction Rules, Calculations, And Date Triggers (34)

- Build conditional display and required-state rule evaluation using constrained JSON structures and stable field identifiers (8).
- Build calculated fields for sums, percentages, multiplication, dependency hashes, persisted outputs, and formula version metadata (8).
- Build configurable date triggers for reminders, late status, and transaction progress dates (8).
- Add calculation, dependency, trigger, audit, and tenant isolation tests (10).

## Phase 7 - Custom E-Signature Core (55)

- Build PDF ingestion and browser preview pipeline (5).
- Import AcroForm fields and convert them into editable in-app fill/sign fields (8).
- Add field designer for adding, deleting, moving, assigning, and editing fields on fillable or non-fillable PDFs (13).
- Add signer assignment, ad-hoc signers, routing order, status tracking, reminders, and field-correction support (8).
- Add typed/drawn signatures, initials, dates, signer consent, final package generation, tamper hashes, and audit certificate (13).
- Auto-store final signed package in the transaction document folder and integrate status with document color system (3).
- Add tests for routing, field placement, audit, consent, final package, reminders, and failure recovery (5).

## Phase 8 - AI Document Read And Auto-Fill (34)

- Define extraction provider interface and run provider bakeoff using representative real estate documents (5).
- Build document classification pipeline for purchase agreements, lease agreements, listing agreements, disclosures, and lender docs (5).
- Build field extraction, confidence scoring, field mapping, and folder routing (8).
- Build confirmation/correction UI with one-click confirm for clean documents (5).
- Log all extraction values, confidence scores, confirmations, corrections, and timestamps in audit trail (3).
- Add extraction fixture tests and regression cases for low-confidence fields and conflicting values (8).

## Phase 9 - Single-Upload Document Review And Forms Library (55)

- Build one-upload document storage and reviewer assignment (5).
- Build private reviewer notes, annotations, and approval statuses per reviewer (8).
- Build document status panel with reviewer color, timestamp, and status summary (5).
- Build folder/transaction/back-office/commission color rollups (5).
- Enforce no cross-reviewer edits and commission gate hooks (5).
- Build searchable forms library with user-uploaded and MLS-preloaded source badges (5).
- Add frequency ranking and contextual tabs by transaction section (5).
- Integrate pull-fill-sign inline workflow with e-signature core (8).
- Add upload, reviewer privacy, status rollup, search, ranking, access, fill/sign, and audit tests (9).

## Phase 10 - Teams And Contacts (21)

- Build team/brokerage shared accounts within a single tenant, role assignment, reporting visibility, and per-transaction overrides (5).
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
