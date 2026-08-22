# Botanical Operations Console Design System

This is the approved source of truth for the Laravel Blade funeral home management system UI. It is based only on the existing implementation in `resources/css/app.css`, `resources/views/layouts/panel.blade.php`, the staff case records page, client/deceased directories, package/catalog management pages, and Payment Monitoring.

The product identity is calm, respectful, trustworthy, operational, compact but readable, botanical neutral, and professional rather than decorative.

## Source Evidence

- Shared tokens and component aliases live in `resources/css/app.css`.
- Authenticated application shell is `resources/views/layouts/panel.blade.php`.
- Strongest page patterns are `records-page`, `table-system-*`, `case-compact-*`, `directory-page`, `admin-catalog-page`, `service-management-page`, `pkg-*`, `ops-*`, and `pm-*`.

## Token Architecture

Use a three-layer system:

- Primitive: raw values already present in `:root`.
- Semantic: purpose aliases such as `--color-bg-page`, `--color-primary`, `--color-border`.
- Component: component classes such as `.ops-btn-primary`, `.table-system-card`, `.form-input`, `.status-badge`.

Do not invent a second theme. New UI work must extend the current Botanical Suite tokens.

Global semantic token roles include `--brand`, `--surface`, `--card`, `--border`, text (`--ink`), muted text (`--ink-muted`), `--success`, `--warning`, `--danger`, and `--info`.

Records-system aliases such as `--records-border`, `--records-muted`, `--records-text`, `--records-hover`, and `--records-active` are not global semantic tokens. They are scoped aliases for record-heavy screens and should not be treated as a second design system.

## Semantic Color Roles

| Role | Token / Value | Usage |
| --- | --- | --- |
| Page background | `--surface: #D2DACB`; semantic `--color-bg-page` | Main authenticated content background. May use the existing subtle operational grid pattern from `.page-content` and `.records-page`. |
| Panel | `--surface-panel: #E8EDE2` | Topbar panels, table headers, elevated shell sections, quiet grouped regions. |
| Muted panel | `--surface-muted: #E1E7D9` | Filter bars, table heads, hover surfaces, secondary panels. |
| Card | `--card: #FBFCF7`; semantic `--color-bg-surface` | Cards, forms, tables, modals, list containers. |
| Records card | `--records-card: #D3DEC9` | Dense operational record panels and Payment Monitoring pilot surfaces. |
| Records alternate | `--records-card-alt: #DCE6D6` | Alternating rows, toolbar controls, modal heads/feet. |
| Records strong | `--records-card-strong: #C7D5BE` | Active grouped areas and stronger section surfaces. |
| Primary | `--brand: #3E4A3D`; semantic `--color-primary` | Primary buttons, selected states, focus anchors, key action icons. |
| Primary hover | `--brand-hover: #2D372D`; semantic `--color-primary-hover` | Primary button hover/active surfaces. |
| Accent | `--brand-mid: #8B9A8B`; semantic `--color-accent` | Soft highlights, supporting action accents, success-adjacent accents. |
| Border | `--border: #C9C5BB`; semantic `--color-border` | Default card, table, input, modal, and divider border. |
| Strong border | `--border-strong: #B5B0A4`; semantic `--color-border-strong` | Hover borders, active filters, stronger section separation. |
| Text | `--ink: #333333`; semantic `--color-text-primary` | Body text, table text, primary labels. |
| Muted text | `--ink-muted: #5F685F`; semantic `--color-text-secondary` | Descriptions, helper text, secondary metadata. |
| Faint text | `--ink-faint: #8B9A8B`; semantic `--color-text-muted: #7A8076` | Tertiary text, inactive hints. |
| Success | `--success: #8B9A8B`; `--color-success: #6F8A6D`; `--color-success-text: #4F6F4D` | Paid, completed, settled, available, active positive status. |
| Warning | `--warning: #B87956`; semantic `--color-warning` | Partial payment, pending follow-up, scheduled risk, attention. |
| Danger | `--danger: #9E4B3F`; `--color-danger-text: #7F3A32` | Unpaid, overdue, archive/destructive action, validation error. |
| Information | `--info: #8B9A8B` | Neutral operational information. Blue must not be reused as a semantic information color unless it is formally approved and promoted to a shared token. |
| Focus ring | `--color-focus-ring: rgba(62, 74, 61, 0.18)` | Keyboard focus and focused fields. |

Dark mode exists in `app.css`; keep it token-driven. Do not hardcode dark colors in page Blade unless migrating a legacy local block to the shared tokens.

## Typography

- Interface content uses `--font-body: "DM Sans", system-ui, -apple-system, "Segoe UI", sans-serif`.
- Important headings use `--font-heading: "Syne", "DM Sans", system-ui, -apple-system, "Segoe UI", sans-serif`.
- Use Syne selectively for page titles, table-system titles, dashboard headings, and important card headings. Do not use Syne for dense table cells, filters, form controls, or long body copy.

Approved sizes and weights:

| Element | Size / Weight |
| --- | --- |
| Body | `14px`, line-height `1.6`, DM Sans |
| `h1` | `2rem`, line-height `1.15`, weight `700`, Syne |
| `h2` | `1.375rem`, line-height `1.25`, weight `700`, Syne |
| `h3` | `1.125rem`, line-height `1.3`, weight `700`, Syne |
| `h4` | `1rem`, line-height `1.35`, weight `700`, Syne |
| Page header title | `clamp(1.2rem, 1.8vw, 1.55rem)`, weight `700`, Syne |
| Page description | `13px`, weight `600`, DM Sans |
| Table header | `11.5px` to `12px`, weight `600`, uppercase only for table headers/labels |
| Table cell | `13px` to `13.5px`, DM Sans |
| Form label | `13px`; compact toolbar labels `11px` to `12px` |
| Button | `13px` to `14px`, weight `500` to `700` depending on hierarchy |
| KPI value | `18px` or `1.15rem`, weight `750` to `800`, tabular numbers |

Avoid excessive uppercase, `font-black`, and `tracking-widest`. Use uppercase only for compact labels, table heads, status microcopy, and filter labels. Prefer weight `500`, `600`, `650`, `700`; reserve heavier weights for KPI values only.

## Layout

- Use `layouts/panel.blade.php` as the authenticated application shell.
- `page-content` defines `--panel-content-inline: 20px`; mobile reduces this to `16px`.
- Standard page padding: `12px var(--panel-content-inline, 20px) 20px`.
- Table/list cards commonly use `margin: 10px var(--panel-content-inline) 12px`.
- Section/card padding should stay within `10px`, `12px`, `14px`, `16px`, or `20px`.
- Section gaps should be compact: `8px`, `10px`, `12px`, `16px`, or `24px` for major separations.
- Operational pages should favor dense scan patterns: table first, filters above table, no marketing hero sections.
- Maximum content width may use existing page wrappers such as `max-w-[1440px]` for catalog management. Do not make narrow centered app pages for record-heavy screens.
- Mobile breakpoints already used in the system include `640px`, `680px`, `860px`, `1023px`, `1100px`, `1200px`, and `1280px`. Prefer `640px`, `680px`, `1023px`, and `1280px` for new responsive rules.
- At mobile widths, filters stack, table wrappers scroll horizontally, and primary actions remain reachable without squeezing text.

## Components

### Page Headers

Use `.ops-page-header` as the migration target for new page headers.

- Background: `--records-card` or `--card`.
- Border: `--records-border` fallback to `--border`.
- Radius: `--radius-ops: 8px`.
- Padding: `12px`.
- Title: `.ops-page-title`.
- Kicker: `.ops-page-kicker`, only when it clarifies operational area.
- Description: `.ops-page-desc`, one concise operational sentence.

Avoid marketing-style hero copy. Page headers should orient the operator, not sell the product.

### Buttons

Use the shared button hierarchy:

- Primary: `.btn-primary`, `.btn-primary-custom`, `.ops-btn-primary`.
- Secondary: `.btn-secondary`, `.btn-secondary-custom`, `.ops-btn-secondary`.
- Outline/reset: `.btn-outline`, `.btn-filter-reset`, `.ops-btn-outline`.
- Danger: `.btn-danger`, `.ops-btn-danger`.

Default button dimensions:

- Min height: `40px`; small buttons `36px`; large buttons `48px`.
- Radius: general `12px`, operational compact `8px`.
- Primary background `--brand`, hover `--brand-hover`, text white.
- Secondary/outline background `--white` or `--records-card-alt`, border `--border`.
- Danger uses `--danger`, or soft danger background for low-risk actions.

Buttons should use Bootstrap Icons when helpful. Icons beside visible text are decorative and should be `aria-hidden="true"`.

### Forms

Use `.form-input`, `.form-select`, `.form-textarea`, `.input-custom`, or `.ops-control`.

- Height/min-height: `40px` to `42px`.
- Background: `--white`, `--card`, or `--records-card-alt`.
- Border: `--border` or `--records-border`.
- Radius: `12px`, or `8px` for dense operational controls.
- Focus: border `--accent` or `--brand`, focus ring `--shadow-focus` or `0 0 0 4px var(--color-focus-ring)`.
- Labels: `.form-label`, `.table-toolbar-label`, `.ops-label`.
- Help text: `.form-hint`, `.ops-help`.
- Error text: `.form-error`, `.ops-error`, color `--danger`.

Preserve `old()` behavior, input names, hidden fields, and validation mappings when changing markup.

### Filters And Toolbars

Use `.table-system-toolbar`, `.table-toolbar`, `.case-compact-*`, `.pm-toolbar-shell`, or `.ops-toolbar-shell`.

- Filters live above operational tables.
- Search should be first and wider than selects.
- Selects and date controls should align to the same height.
- Reset and Apply actions remain grouped at the end.
- Use live search classes only when the existing page already has them.
- Hidden custom date fields must remain hidden/disabled exactly as existing logic defines.

### Tables

Use `.table-system-card`, `.table-system-list`, `.table-system-wrap`, `.table-base`, and `.table-system-table`.

- Table containers use `--card`, `--border`, and no heavy shadow.
- Table heads use `--surface-muted` or `--surface-panel`.
- Header cells use uppercase small labels, `11.5px` to `12px`.
- Body cells use `13px` to `13.5px`.
- Row hover uses `--surface-muted`, `--records-hover`, or page-specific existing hover token.
- Preserve operational tables as tables. Do not replace dense record tables with decorative cards unless the existing page already has an approved card view.

### KPI Cards

Use `.ops-stat-grid`, `.ops-stat-card`, `.pm-kpi`, or existing package stat card patterns.

- Grid: four columns on desktop when space allows; stack or reduce on mobile.
- Card radius: `8px` to `10px`.
- Icon box: `32px` square.
- KPI labels: `11px` or `.65rem`, uppercase with restrained tracking.
- KPI values: `18px` or `1.15rem`, tabular numeric.
- KPI descriptions: `11px` or `.63rem`.

KPI cards should summarize operational state, not become decorative tiles.

### Status Badges

Use `.status-badge`, `x-status-badge`, or `.status-pill`.

- Radius: full pill.
- Padding: compact, roughly `4px 10px` or `.24rem .66rem`.
- Font size: `11px` to `.79rem`.
- Success: paid, completed, active, available.
- Warning: partial, pending, follow-up.
- Danger: unpaid, overdue, archived/destructive.
- Info/neutral: schedule and informational state.

Never rely on color alone; label text must remain explicit.

### Alerts

Use `.flash-success`, `.flash-error`, `.flash-info`, `.flash-warning`, or `.ops-alert`.

- Toasts use dark botanical background `#3E4A3D` with colored icon accents.
- Inline alerts use soft card backgrounds and existing borders.
- Alerts should be concise and operational.

### Empty States

Use `.table-system-empty`, `.ops-empty`, `.pm-empty`, or existing page empty rows.

- Empty states should be calm and compact.
- Minimum height around `160px` for panel empty states.
- Text should explain the state, not market the feature.
- Do not add large illustrations or decorative animation.

### Modals

Use `.ops-modal-backdrop`, `.ops-modal`, `.pm-modal`, `.svc-modal`, or existing modal shells.

- Backdrop: botanical/charcoal scrim, e.g. `rgba(35, 43, 34, .48)`.
- Modal width: default `min(100%, 28rem)`; large transaction modal may use `min(100%, 58rem)`.
- Max height: `min(86vh, 48rem)`.
- Radius: `8px` for operational modals; existing service modals may use their current shell.
- Header/footer use alternate panel surface and visible border.
- Close buttons require accessible labels.

### Sidebar And Topbar

Use `layouts/panel.blade.php` as the shell pattern.

- Sidebar contains brand, role-aware navigation, account menu, profile pill, and theme toggle.
- Topbar supports page title/description, actions, filters, reminders/alerts, and mobile sidebar toggle.
- Sidebar width token: `--sidebar-w: 252px`.
- Topbar height token: `--topbar-h: 62px`.
- Mobile sidebar opens with `#mobileSidebarToggle`, `#sidebarBackdrop`, and `#appSidebar`; do not alter selectors casually.
- Notifications use `topbar-notification-*` classes and bucket states `all`, `due`, `today`, and `upcoming`.

## Interaction States

### Hover

- Use subtle background and border changes.
- Standard transition: `0.14s` to `0.16s`.
- Primary hover uses `--brand-hover`.
- Secondary hover uses `--surface-muted`.
- Operational row hover uses `--records-hover` or `--surface-muted`.
- Avoid large transforms. If used, keep to `translateY(-1px)` or `scale(0.97)` on active button press only.

### Focus Visible

- Focus must be visible.
- Use `outline: 2px solid var(--brand)`, `outline-offset: 2px`, and/or `box-shadow: 0 0 0 4px var(--color-focus-ring)`.
- Do not remove outlines without replacing them with an equally visible focus state.

### Active

- Tabs/segments use `--records-active: #B8C9AF`, `--records-card-strong: #C7D5BE`, or a soft primary mix.
- Active route/nav states should be clear by background and border, not color alone.

### Disabled

- Disabled controls use muted surface, muted text, no hover affordance, and reduced pointer behavior.
- Do not style disabled controls as primary actions.

### Loading

- Existing loading patterns use opacity around `.62` to `.72`, small translate movement, and pointer suppression.
- Keep loading calm and non-disruptive.

### Validation Error

- Error text uses `--danger`.
- Error borders should use danger color or a soft danger border.
- Keep submitted values visible; do not clear fields visually.

### Empty And No Results

- Empty table rows should remain inside the table with correct `colspan`.
- Panel empty states should use `.ops-empty` or page-specific equivalents.
- No-results messages should be actionable when filters are active, usually by exposing Reset/Clear controls already present.

## Anti-Slop Rules

- No glassmorphism.
- No glow effects.
- Gradients are allowed only for existing table headers, page background grid, and established shell treatments.
- No oversized rounded cards. Use `8px`, `10px`, `12px`, `14px`, `16px`, `18px`, and only use `24px` where the existing detail/card pattern already does.
- Motion is allowed only for existing page, flash, toast, modal, dropdown, and navigation transitions.
- Do not add decorative gradients or animation without explicit approval.
- No random colors per page. Use the semantic tokens and approved status colors.
- No marketing-style hero sections inside authenticated operations pages.
- No generic SaaS copy such as "growth", "pipeline", "conversion", or "workspace supercharged" unless the business domain actually uses it.
- No cards replacing operational tables unnecessarily.
- No new icon family. Continue Bootstrap Icons unless a full icon migration is approved.
- No page-specific CSS forks for common components when `app.css` already provides a shared pattern.
- No excessive uppercase, `font-black`, `tracking-widest`, glow shadows, or high-contrast neon states.

## Migration Guidance

1. Keep `resources/css/app.css` as the shared source of UI tokens and components.
2. Keep `layouts/panel.blade.php` as the authenticated shell.
3. Migrate page-by-page, preserving routes, input names, Alpine/JS selectors, authorization directives, pagination, and business data mappings.
4. Prefer replacing local page CSS with shared `ops-*`, `table-system-*`, form, button, badge, alert, modal, and shell classes over creating new local systems.
5. Use Payment Monitoring, Case Records, client/deceased directories, and package/catalog management as the strongest references.
