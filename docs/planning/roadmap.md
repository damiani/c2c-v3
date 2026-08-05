# Contract2Close v3 Roadmap

## Summary

Contract2Close v3 is a complete Laravel rebuild of C2C Core for real estate transaction management across sales, purchases, residential rentals, commercial sales, and commercial leases. The first deployable beta is the P1 Critical scope: foundation, tenancy/auth, dashboard/navigation, custom e-signature, and AI document read/auto-fill.

The app will use PHP 8.4.1+, Laravel, the official Livewire starter kit, Flux UI Pro, Tailwind, MySQL, Laravel Boost, robust automated tests, S3-compatible document storage, and Laravel Cloud-oriented deployment patterns.

Relative effort uses story points: `1` tiny, `3` small, `5` medium, `8` large, `13` very large, `21+` epic.

## Product Principles

- Upload once: every party reviews the same uploaded document from their own role-scoped view.
- One platform: transactions, dynamic fields, documents, signatures, AI field entry, contacts, commissions, rentals, listings, and integrations live together.
- Simple enough for anyone: a new user should find any primary feature within 30 seconds without training.
- White-label from day one: tenant branding, settings, communications, templates, field labels/units/formats, and integration visibility are never hardcoded.
- Audit everything: auth, tenancy, field edits, document actions, AI extraction, signing, review, compliance, commission, notification, and integration events are append-only.

## UX Direction

- Light-first, polished, modern interface inspired by premium financial/admin tools and Laravel Cloud/Forge.
- Balanced professional density: at-a-glance status without dashboard clutter.
- Flux UI Pro is the baseline component system; create custom C2C primitives for transaction workspaces, PDF filling/signing, document review, status rollups, and action queues.
- Persistent global sidebar for primary navigation.
- Collapsible pinned transaction rail for active/recent transactions with status chips and quick filtering.
- Contextual tabs inside transaction records: Overview, Documents, Forms, Signatures, Contacts, Milestones, Commission, Activity/Audit.
- Dashboard is action-queue first: deadlines, pending signatures, document reviews, renewals, missing approvals, and commission blockers.

## Phase Roadmap

| Phase | Priority | Milestone | LOE |
|---|---:|---|---:|
| 0 | Setup | Save planning docs, scaffold Laravel Livewire starter kit, install Boost, configure MySQL, establish test/CI/style baseline | 8 |
| 1 | P1 Critical | Laravel foundation, tenant scope, audit/events, object storage, queues, CI/CD, test baseline | 21 |
| 2 | P1 Critical | Starter-kit auth customization, roles, Fortify 2FA/email verification, SSO-ready identity, tenancy, branding, English/Spanish localization | 34 |
| 3 | P1 Critical | Flux design system, app shell, sidebar, pinned transaction rail, role dashboards, action queue, global search shell, notification badge, mobile UX gate | 42 |
| 4 | P1 Critical | Custom e-sign core: PDF viewer, AcroForm import, add/delete fields, signer assignment, routing, signatures/initials/dates, audit certificate, final package | 55 |
| 5 | P1 Critical | AI extraction bakeoff, document classification, field extraction interface, confidence review UI, auto-fill audit log, folder routing | 34 |
| 5A | P1 Critical | First beta stabilization for P1 Critical workflows, coverage review, UX pass, regression suite | 13 |
| 6 | P1 | Core transaction engine: transaction types, lifecycles, property/lease fields, activity feed, filters | 34 |
| 7 | P1 | Single-upload document platform, reviewer layers, status panel, folder rollups, commission gate hooks | 34 |
| 8 | P1 | Dynamic field platform: templates, custom fields, scoped labels/units/formats, dependencies, calculations, date triggers, checklist/versioning permissions | 34 |
| 9 | P1 | Unified forms library, PDF filling, source badges, contextual tabs, frequency ranking, sign flow integration | 21 |
| 10 | P1 | Teams/brokerages within tenants, contact CRM, per-transaction permissions, section access, team reporting, team nudges | 21 |
| 10A | P1 | Core platform stabilization, coverage review, beta expansion | 13 |
| 11 | P2 | Rental/lease management, renewal scheduler, escalation alerts, rental portfolio dashboard | 21 |
| 12 | P2 | Compliance rules, back-office checklist, rejection/resolution flow, approval gates | 34 |
| 13 | P2 | Commission engine, agent net view, brokerage financial dashboards, lease commission models | 34 |
| 14 | P2 | Notification engine, email/SMS/in-app, calendar sync, digest, localized notifications | 21 |
| 15 | P2 | Property details, listing sheets, share links, WhatsApp/META distribution and delivery tracking | 34 |
| 16 | P3 | Workflow automation triggers/actions and conditional logic | 21 |
| 17 | P3 Parallel | MLS/form feed compatibility, mappings, inbound draft data, outbound approval gate | 34 |
| 18 | P3 | Vertical integration framework and partner tab placements | 21 |
| 19 | P3 | v2 migration, load/security/mobile QA, UAT, launch runbook, rollback | 34 |

## Release Gates

- P1 Critical beta is releasable only after Phases 1-5A pass automated tests, tenant isolation checks, core mobile QA, and e-sign/AI/document audit acceptance.
- P1 core beta expansion is releasable only after Phases 6-10A pass transaction, document review, forms, teams, contacts, and regression acceptance.
- P2 release requires lease renewal, compliance, commission, notification, calendar, and property distribution workflows to pass end-to-end tests.
- P3 launch requires migration validation, load testing, security review, mobile QA, UAT with 2-3 brokerages, rollback plan, and full sign-off.
