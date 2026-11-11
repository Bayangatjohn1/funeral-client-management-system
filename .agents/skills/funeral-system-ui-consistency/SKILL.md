---
name: funeral-system-ui-consistency
description: Standardize existing funeral-system Laravel pages against approved Service Management UI/UX patterns, apply applicable design patterns to other modules, improve layout consistency, and keep the project's UI decisions current after user revisions. Use for existing-page consistency work; this does not authorize new features or a system-wide redesign.
---

# Funeral system UI consistency

Use the existing Botanical Suite implementation in this Laravel Blade/Tailwind/Alpine application. Preserve its typography, semantic tokens, backend behavior, and page-specific workflows. Service Management supplies comparable catalog, toolbar, form, and detail patterns; the user's latest explicit choices take precedence. Staff worklists provide additional case-specific references, not a universal replacement for catalog behavior.

Read [approved UI patterns](references/approved-ui-patterns.md) for source ownership and evidence. For case pages or a revision to a choice made in this project, also read [current UI decisions](references/current-ui-decisions.md). Evidence levels are part of the contract: an implemented or user-approved choice is not automatically a visually verified standard.

For search-bar adoption or revisions, read the [complete record-search component](references/record-search.md). Apply its UI and interaction contract together unless the user explicitly limits scope; copying width alone is insufficient. Update that reference when the search component changes.

For Filter/Sort adoption, read [Filter and Sort states](references/filter-sort-states.md). Compare actual Service Management hover, open, selected-option and keyboard states as well as the default frame. Keep applied-filter counts distinct from open state and preserve each page's Apply behavior. Use this reference for each subsequently authorized page, updating it when the user revises the pattern.

For loading-state work across authenticated pages, read [shared skeleton loading](references/skeleton-loading.md). Use real pending states; do not impose loading delays or replace ready server-rendered content.

For Branch/User Management, Audit Logs, or Reports/Analytics filter adoption, read [directory and audit adoption](references/worklist-adoption.md). It records shared owners, preserved role differences, manual Apply, detached footers, and the filter-only Reports scope.

## Work on an existing page

1. Inspect the target's live working-tree view, routes/controllers, role checks, components, CSS imports/inline overrides, and JavaScript. Read applicable repository instructions and git status. Preserve unrelated work. Follow `staff-ui-reference` for role-specific source comparisons when applicable; do not attach a staff role class to another role.
2. Compare corresponding existing functionality against the approved reference. Verify the effective cascade; a filename, declared token, or prior conversation is not proof of the rendered result.
3. Write a compact applicability checklist: **Applicable**, **Already consistent**, **Not applicable**, or **Existing functionality missing**. Distinguish a regression in existing functionality from a feature that never existed. Missing functionality is a finding, not authorization to invent it.
4. Apply only relevant presentation/interaction improvements within the user's scope. Reuse existing components when their data assumptions and permissions match. Keep content, labels, amounts/dates, field names, query parameters, available options, validation, actions, branch scope, and return URLs unless the user explicitly requests a change.
5. Do not introduce filters, sort choices, grid/table switches, status values, buttons, backend behavior, database structures, permissions, or business rules merely to match Service Management. Explicit feature requests remain separate authorized work and must retain their authorization checks.
6. Verify affected interactions on each shared consumer. Compare light/dark, desktop/intermediate/narrow widths, long names, empty results, selected filters, keyboard focus, dropdown open/close, pagination and print where applicable. Tables keep every original column in a horizontal scroller. Mark unavailable checks as unverified, not passed. Use isolated test data for writes.
7. Run relevant checks for the change. Blade changes require view compilation/cache clearing; asset changes require the existing build. Choose behavioral tests for changed permissions/workflows. Do not add tests that merely mirror CSS strings. A build is not visual QA.
8. Report applied patterns, not-applicable items, changed files, actual verification, and remaining issues. Documentation-only work does not authorize redesigning other pages.

## Keep this skill current during UI work

This is a living reference. Whenever a UI/UX change or a user correction revises an established pattern, update its documentation in the same task before reporting completion:

- Inspect the resulting implementation, then replace the relevant **current** decision with the latest requested outcome. Do not accumulate contradictory rules or treat an abandoned intermediate version as another valid standard.
- Record scope (page/component/roles), exact source owners, actual tokens/measurements, user intent, implementation state, verification evidence, and outstanding checks. Update only affected entries; avoid copying a whole chat transcript.
- Keep a short supersession note when it prevents a known regression. Current instructions must be readable without reconstructing the edit history.
- Separate **Implemented and verified**, **Implemented but not yet fully verified**, and **Planned/not implemented**. Promote an item to an authoritative reusable reference only after its relevant verification passes. A user-approved visual preference can be recorded as the desired direction while its implementation remains unverified.
- If the latest request only proposes an alternative, do not rewrite the adopted decision as though it was implemented. Questions are not automatically design approvals.
- Synchronize overlapping references in `staff-ui-reference`, especially `references/case-records-patterns.md`. Make the current decision ledger the single owner for revised case choices; retain role and workflow safeguards from the older reference.
- Never let a skill update grant permission to change another page, widen access, or run a production mutation. Scheduler findings are operational context, not UI adoption instructions.

## Validate skill maintenance

Validate frontmatter and reference links. Re-read edited guidance for scope leaks, obsolete choices, and unsupported verification claims. Dry-run the applicability checklist against an existing page without editing that page unless separately authorized. Keep the outcome in [validation notes](references/validation-notes.md).

Invoke explicitly with `$funeral-system-ui-consistency`, or use it for an applicable UI consistency request.

