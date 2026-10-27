# Cross-page typography audit — October 13, 2026

Audit only; no application code changed. Staff UI Reference is the foundation. Print/PDF/contract paper, guest/auth and security recovery codes are separate typography contexts.

## Findings

- `app.css` root still declares DM Sans body and Syne headings. `panel.blade.php` loads those alongside Plus Jakarta Sans and JetBrains Mono. Staff, Branch Admin and System Admin body rules override the family; the shared sidebar also overrides it. This is not a universal panel content foundation.
- Shared `.topbar-title` and `.panel-page-header__copy h1` explicitly prepend Syne, bypassing the role heading token unless another component rule wins. Some inspected rendered System Admin pages already override this; do not assume every page renders Syne.
- Owner body has no role font foundation in panel markup. Owner dashboard and sales view use inherited font tokens, making the legacy root family a source-confirmed inconsistency risk. Owner Analytics explicitly includes the Reports visual reference and adopts Plus Jakarta Sans. Rendered Owner verification remains pending.
- Reports/Analytics uses Plus Jakarta Sans but the rendered title and tabs use weight 800, KPI amounts use 900, and section heading uses 19.52px. System Admin dashboard headings use 700; generic page headings observed at 24px/700 in the same browser sizing.
- Actual Branch details modal title is Plus Jakarta Sans 20px/760; User Management has a control at 660; Backup & Recovery has a control at 850. Shared loaded Plus Jakarta weights are 400/500/600/700/800. Nonstandard declared weights and weights beyond the supplied families make rendering less predictable.
- Numeric styles differ: staff uses JetBrains Mono; Reports amounts use ui-monospace/SFMono/Consolas; security codes use monospace intentionally. Do not indiscriminately replace every monospace declaration.
- Multiple layered inline and role overrides own sizes and weights. A broad important/universal selector would conceal conflicts and affect icons, charts and document output; correct component ownership instead.

## Adoption matrix

| Role/scope | Pages/components | Evidence | Next step |
|---|---|---|---|
| Shared panel | Shell, sidebar, page heading, controls, modal frame | Source inspected | Consolidate panel body/heading family tokens without changing guest/print styles |
| Staff | Dashboard, intake, case detail and remaining-page reference layers | Current sources and prior task browser evidence | Baseline for semantic typography; verify fresh matching viewports before adoption |
| Branch Admin | Dashboard, records and common pages | Role reference/source inspected | Review local 800/900 overrides by component; rendered role QA pending |
| System Admin | Dashboard, cases, payments, branches, users, service management, Reports/Analytics, audit, recovery, retention | Actual family/size/weight sampled under System Admin; Branch details modal sampled | Standardize controls/headings; preserve directory and data scopes |
| Reports/Analytics | Shared visual-reference, analytics/reference components | Source + actual light/dark typography | Normalize title/tab/label/KPI weights and numeric family |
| Owner | Dashboard, Analytics, sales per branch | Source inspected | Apply universal panel family; verify Owner login and page-specific cascades |
| Print/security | Print/PDF, contract paper, recovery codes | Source identified | Preserve intentional separate typography |

## Recommended standard

- Plus Jakarta Sans for panel text and headings, across all roles; body 400, labels/actions 600, headings/important values 700.
- Shared responsive page-title scale (24–32px), section headings 18px, component headings 16px, body/controls 14px, help text 12–13px. Choose by semantic component, not a global h-tag override; keep dense-table requirements.
- One numeric convention derived from Staff: JetBrains Mono for deliberate financial/code presentation, otherwise Plus Jakarta Sans with tabular numbers. Avoid arbitrary 900 financial labels and inconsistent platform-dependent monospace stacks.
- Preserve Bootstrap icon font, chart data, routes, permissions, table columns, validation, form bindings, numeric/date formatting, and print layout.
- Work in batches: panel foundation, shared headings/controls/modals, Reports and System Admin, Branch Admin, Owner, then Staff regression. Read actual staff CSS/reference and compare both themes at 1440/390px for each changed batch.

## Verification limits

This is a typography audit, not proof that every UI interaction or role screen was tested. System Admin navigation pages and a branch overview were sampled. System Admin dashboard and Reports typography checked in both themes. Other role screens require their authenticated sessions; no authorization bypass or account changes made. A navigation observation for Branch Management initially returned the preceding page and was discarded; Branch Management was separately revisited and its actual title/modal measured. No build/test run is needed for this documentation-only audit.
