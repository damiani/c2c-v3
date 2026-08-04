# Contract2Close v3 Decision Log

## Finalized Defaults

| Decision | Default | Rationale |
|---|---|---|
| First release target | P1 Critical beta | Validates foundation, tenancy, dashboard, e-signature, and AI extraction before broad platform scope. |
| Framework | Laravel | User-selected backend and best fit for Laravel Cloud. |
| Starter scaffold | Laravel Livewire starter kit | Provides Livewire, Tailwind, Flux UI, Fortify-backed auth, and customizable app code. |
| Frontend | Livewire + Flux UI Pro | User preference and first-party Laravel ecosystem fit. |
| Database | MySQL | Good Laravel Cloud/Laravel default; no current spec need for Postgres-specific features. |
| Tenancy model | Single database with `tenant_id` scoping | Fastest operational path for beta and compatible with Laravel Cloud; must be heavily tested. |
| UI direction | Light-first premium admin UI | Matches user preference for modern, clean, high-end, uncluttered product experience. |
| Navigation | Sidebar + contextual tabs + pinned transaction rail | Supports fast transaction switching and clear global navigation. |
| Dashboard priority | Action queue first | Helps users see what needs attention immediately. |
| E-signature | Custom first-party core | User wants seamless customizability and no provider lock-in. |
| E-sign legal scope | US ESIGN/UETA for beta | Keeps first signing scope bounded; requires attorney review before production use. |
| AI extraction | Provider abstraction and bakeoff | Avoids premature vendor lock-in and allows evidence-based selection. |
| Initial languages | English and Spanish | Externalization starts immediately and beta validates real multilingual behavior. |
| Pilot scale | 2-3 brokerages | Matches spec UAT target and exercises multi-tenant behavior without overbuilding support workflows. |
| Testing posture | Robust tests during every phase | User explicitly requested strong coverage and sustainable development velocity. |
| AI development support | Laravel Boost | Provides Laravel/package-aware guidelines and docs for architectural and code-standard decisions. |

## Decision Gates

| Gate | Needed By | Owner | Notes |
|---|---|---|---|
| Flux Pro license setup | Phase 0/3 | User | Add credentials when installing Flux Pro package. |
| Initial dashboard wireframes | Before Phase 3 build | User/Design | Engineering should not invent final dashboard UX without approval. |
| E-sign legal review | Before production signing | User/Legal | Validate consent, attribution, retention, certificate, and tamper-evidence requirements. |
| AI extraction provider | Before Phase 5 production hardening | User/Engineering | Compare sample real estate docs across candidate providers. |
| AI confidence thresholds | Phase 5 design | User/Engineering | Define field-level auto-apply vs. review thresholds. |
| Reviewer permission matrix | Before Phase 7 | User | Define reviewers by transaction type and what each can view/approve. |
| Initial partner tabs | Before Phase 2/3 tenant UI completion | User | Needed for dashboard and tenant integration visibility. |
| Jurisdictional retention rules | Before Phase 7/12 | User/Legal | Documents and leases may differ by state and transaction type. |
| MLS association SSO partners | Before Phase 17 and form preload work | User | Needed for launch associations and mapping scope. |
| META WhatsApp tenant credentials | Before Phase 15 | User/Tenants | Each tenant needs verified META Business credentials. |
| WhatsApp message templates | During Phase 14 | User/Engineering | Must be submitted early enough for META approval before Phase 15. |
| Vertical integration partner list | Before Phase 18 | User | Determines initial named tabs and section placement. |
| Native mobile strategy | After Phase 13 pilot feedback | User | Responsive web is the default until then. |

## Revisit Triggers

- Reconsider MySQL only if MLS/property workflows need advanced geospatial querying, high-volume analytics, or database-native features MySQL cannot comfortably support.
- Reconsider Flux Pro only if PDF/editor/document-review interactions require UI primitives that are better served by a specialized app framework.
- Reconsider single-database tenancy if enterprise isolation, regulatory obligations, or large-tenant performance require isolated databases.
- Reconsider custom e-sign scope if legal review finds certification, identity, or retention requirements that materially exceed beta assumptions.
