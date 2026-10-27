# Staff CSS source map

Inspected on 2026-10-07 from the full 356-line `resources/css/staff-reference.css` in this working tree. SHA256 at inspection: `464AAF77B9DF2B01635B241B28AF334FBC33F15E820854A0DF8218854BFC7AF0`. This is a source audit, not a rendered-browser audit. Line positions and values can change; inspect the current source before application.

## Source and cascade

- `resources/css/app.css` imports `staff-reference.css`, then `staff-intake-reference.css`, then `branch-admin-reference.css`, before its other rules and Tailwind directives. Import order alone does not determine a winner: account for specificity, `!important`, theme conditions, media queries, and later Blade styles.
- Main scope: `html[data-theme] body.panel-shell-body.staff-ui`. Dashboard rules mostly require the descendant `.staff-dashboard-v2`. The source does not provide a complete standalone visual system for every staff page.
- `resources/views/layouts/panel.blade.php` loads Plus Jakarta Sans and JetBrains Mono and assigns the role scope. Preserve role checks; extract/adapt presentation selectors rather than assigning the staff role class to every role.
- Read the whole file, including the final "Muted dashboard controls" block. Earlier declarations are sometimes superseded. Check a matching rendered staff component's computed styles before resolving conflicts with other loaded sources.
- Keep one maintained source of styling. This map intentionally does not duplicate the full stylesheet; the application CSS remains the authoritative source. Do not paste all staff dashboard rules onto target pages.

## Foundation tokens

| Token | Light/base | Dark override |
| --- | --- | --- |
| `--staff-canvas` | `#DCE8E0` | `#07130E` |
| `--staff-card` | `#F4F8F5` | `#112218` |
| `--staff-soft` | `#D0E2D6` | `#14261D` |
| `--staff-hover` | `#C2DAC9` | `#1B3527` |
| `--staff-border` | `#AEC7B7` | `#29523D` |
| `--staff-ink` | `#081D13` | `#F8FAFC` |
| `--staff-muted` | `#1B3E2E` | `#94A3B8` |
| `--staff-action` | `#1B4332` | `#34D399` |
| `--staff-accent` | Not defined by this file's base scope | `#34D399` |
| `--staff-layout-gap` | `1.5rem`; `1rem` at widths <=767px | Same responsive rule |

Both `--font-body` and `--font-heading` use `'Plus Jakarta Sans', 'Segoe UI', sans-serif`. Code/money/time selectors use `'JetBrains Mono', Consolas, monospace`, with tabular numbers where declared. Map these to shared semantic tokens; do not use the dark action token as every filled button background. Several controls intentionally retain dark green or use separate muted selection surfaces.

## Component source specifications

These are declarations and within-file overrides; surrounding app/Blade CSS must still be checked. Apply a specification only to an equivalent target component, preserving its structure, data, and controls.

| Source selector family | Source specification |
| --- | --- |
| `.app-shell`, `.main-area`, `.page-content` | Staff canvas background |
| `.staff-title` | `clamp(1.5rem, 2.4vw, 2rem)`, line-height `1.2`, heading font |
| `.staff-card`, light action/attention/outstanding cards | Card surface, 1px staff border, `.85rem` radius, `0 2px 5px rgb(8 29 19 / 4%)` shadow |
| `.staff-summary-card` | Card surface, 1px border, `.85rem` radius, `1.4rem` padding; numeric value `clamp(1.4rem, 2vw, 2rem)` |
| `.staff-card-head` | `1rem 1.25rem` padding, bottom border; heading `1.1rem/700`. Attention/outstanding headers finally use card surface |
| `.staff-action-card` | `1.25rem` padding, `1rem` internal gap; title `1rem/700`, description `.8125rem/400`, 48px icon container |
| `.staff-action-card.is-dark` | `#1B4332`, white copy/icons, hover `#123325`; separate from muted arrows/tabs |
| `.staff-tabs`, `.staff-tab-btn` | Tabs `.25rem` gap/padding, `.6rem` container radius; buttons `.5rem` radius, `.55rem .75rem` padding, weight 600; later minimum height is 40px |
| `.staff-list`, `.ref-activity-list` | `1.25rem` padding, `.75rem` gap; applicable schedule/attention/outstanding lists max-height 340px with vertical scrolling |
| `.ref-activity-row` | `1rem` padding/gap, 1px border, `.75rem` radius, light `#EAF2ED`, dark `#13271D`; `.8125rem/1.45` |
| `.ref-field-label`, `.ref-meta` | Labels `.65rem`, uppercase, 700, `.035em` tracking; metadata `.7rem/1.5` |
| `.ref-schedule-card`, `.ref-attention-card`, `.ref-balance-card` | `1rem` padding, `.65rem` radius, soft surface, 1px border; attention border `#D8B949` |
| `.ref-code`, `.ref-package` | Card surface, 1px border, `.3rem` radius, `.25rem .5rem` padding, `.75rem/600`; code uses mono |
| `.ref-empty`, `.ref-empty-icon` | Min-height 160px, `2rem 1rem` padding, centered; 48px icon container, `.75rem` radius |
| `.staff-table` (dashboard only) | Width `calc(100% - 2rem)`, `1rem` margin, separate borders, `.6rem` row spacing; headers `.7rem` uppercase, cells `.85rem .75rem`, soft surface, border, `.6rem` outer row corners |
| `.staff-nav-icon` | 34px square, `.5rem` radius, soft surface/action color; SVG 18px; active wrapper `#285A44`/white |
| `.profile-avatar`, `.profile-pill` | Avatar 36px circular, `#1B4332`/white, 1px border; pill transparent, borderless, `.5rem` radius/padding; footer top border |
| Explicit `:focus-visible` rules | 2px action-color outline with 3px offset on listed navigation, dashboard links/cards/tabs and theme control; audit other controls separately |

## Later overrides that must not be missed

- Final `.staff-action-arrow` and `.staff-tab-btn.is-active`: light background `#D0E2D6`, text `#1B4332`, 1px `#AEC7B7` border; dark background `#1B3527`, text `#B5D6C4`, border `#41634F`; no shadow. These replace earlier dark-green/white selection declarations. Arrow container is 40px; this is not a universal action-button size.
- `.staff-tab-btn` is initially min-height 44px but later set to 40px. Do not document 44px as its final source value. Choose accessible sizing deliberately and verify any deviation rather than silently reproducing an outdated rule.
- `.staff-tabs` ends with card surface in base/light and `#07130E` in dark, replacing the earlier soft surface.
- Dashboard table headers are finally transparent within this file, replacing the earlier soft header declaration. Do not apply this dashboard table treatment to Case Records/payment tables without inspecting those counterparts.
- `.staff-branch-chip` ends borderless/transparent with zero padding and `.875rem/600`, replacing earlier chip surface/border declarations.
- Dark primary copy uses ink; specified labels/links/code/summary money use mint accent; specified metadata/inactive tabs/time use muted slate. Warning attention/count/balance amounts finally use `#FBBF24`; attention count surface/border are `#35200B`/`#78350F`. Preserve warning semantics.
- Dark context/code/package/icon surfaces use `#06251B` with `#065F46` border. `.ref-collect` stays `#1B4332` with light text. Do not flatten these distinctions into a single theme color.
- `.staff-summary-card:last-child` has warning colors in dark mode: this is a staff positional assumption. Apply warning styling by the target metric's meaning, not by blindly coloring its last card.
- Generic `.nav-link__icon` has an important soft background; its active background declaration is non-important. The `.staff-nav-icon` wrapper has separate active rules. Preserve the actual markup and inspect computed styles before deciding which icon treatment wins.

## Responsive rules and scope traps

| Condition | Source behavior |
| --- | --- |
| Desktop base | Summary 4 columns, action grid 3, main grid 2:1, subgrid 2; dashboard section gutters `1.5rem`, header padding `1.25rem 1.5rem 0` |
| <=1179px | Summary 2 columns, main grid 1 column; payment activity rows change from 6 to 3 columns |
| 700–1100px | Header becomes one column; header controls align right |
| >=1200px | Schedule header uses title/controls columns, schedule tabs nowrap with `.55rem` horizontal padding and `.75rem` text |
| <=767px | Body layout-gap token becomes `1rem` |
| <=699px | Action/subgrids 1 column, case/payment activity rows 2; gutters `.75rem`, header `.75rem`; controls/list rows wrap; dashboard table min-width 650px |
| <=399px | Case/payment activity rows 1 column |
| >=1025px / <=1024px | Activity headings shown / hidden; desktop row field labels visually hidden but retained for accessibility; collapsed sidebar identity copy hidden at >=1025px |

Do not change target card/column counts or page composition to match staff. Adopt spacing and adaptive principles for the target's existing structure. Preserve the staff reference's content too. The source's <=699px dashboard `gap: 1rem` rule is followed by a later base `gap: var(--staff-layout-gap)`; the <=767px token override supplies the narrow value. Source order matters.

The panel's sidebar setup uses a 1024px threshold while this CSS has 1025px rules: verify 1024px and 1025px rather than assuming the boundary is seamless. For other responsive transitions, check both sides of applicable breakpoints. Do not generalize desktop label-hiding to target tables unless equivalent accessible headings remain.

## Areas requiring additional sources

This file does not define the full filter toolbar, pagination, form validation, modal scrim/focus behavior, sidebar dimensions, notification behavior, or all loading/disabled states. Use the main skill's source map to inspect staff page/partial CSS, `app.css`, `staff-intake-reference.css`, and the relevant JS. Do not invent missing specifications from dashboard rules or claim this file alone covers the whole system.

For each adopted component, record the staff selector, relevant source/override, target selector/component, and browser comparison result in the adoption matrix. Refresh this reference when the CSS changes; a hash difference signals reinspection, not an application error. Keep browser verification and content-preservation gates from the main skill.
