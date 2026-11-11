# Project-Wide Instructions for Codex

## 1. Existing Application Is the Source of Truth
- The actual Laravel application is the source of truth for implementation.
- The stack is Laravel Blade, Tailwind CSS, Alpine.js, Vite, and MySQL.
- Do not replace existing Laravel pages with React or create a separate frontend application.
- Inspect the existing layouts, components, CSS, Tailwind configuration, and reusable UI patterns before editing.

## 2. Design References
- Google AI-generated screenshots, mockups, Markdown files, and prototype code are design references.
- Use them to guide layout, visual hierarchy, spacing, component arrangement, and interactions.
- Do not blindly copy their framework, file structure, CSS, font imports, or implementation.
- Adapt the intended design to the application's existing architecture and design system.

## 3. Typography Consistency — Mandatory
- **The current system's font family and typography are the source of truth.**
- Before making UI changes, inspect the existing font imports, global styles, Tailwind configuration, typography tokens, and representative screens.
- Reuse the current font family, font weights, type scale, line heights, letter spacing, and established text styles.
- Maintain consistency across page headings, section headings, body text, labels, form fields, buttons, tables, badges, modal titles, helper text, and error messages.
- Do not introduce a new font, import fonts from Google AI-generated code, install font packages, or replace the application's global font settings just to match a reference.
- Do not create arbitrary font sizes or weights when an existing typography scale already provides an appropriate option.
- If the reference uses different typography, adapt its visual hierarchy and proportions using the current system's font and typography scale.
- Only introduce a new typography token when genuinely necessary and consistent with the existing design system.
- Never modify global typography to accommodate one redesigned page.

## 4. Existing Design System
- Preserve the established Botanical Suite visual identity and existing design tokens.
- Primary colors: Forest Green `#1B4332`, Sage Green `#52B788`, Warm Stone `#FBFBF9`, and Dark Evergreen `#0E1511`.
- Reuse existing spacing, colors, borders, radii, shadows, icons, buttons, form controls, and shared components where appropriate.
- Do not introduce conflicting styles or unnecessary visual effects.

## 5. Preserve Business Logic
- Treat this as a UI/UX implementation task unless the request explicitly includes business logic changes.
- Preserve existing validation, pricing, discounts, tax calculations, permissions, database behavior, routes, payment rules, synchronization, and historical records.
- Do not remove existing functionality or make business assumptions to accommodate a design reference.
- Keep existing role-based access control and server-side validation intact.

## 6. Implementation and Verification
- Implement the requested changes in the actual application; do not stop at a plan or report.
- Inspect the relevant source files before editing and reuse the existing architecture.
- Run the application and inspect the actual screen when browser or screenshot tools are available.
- Compare the implementation against the supplied reference using the same viewport.
- Check layout, spacing, typography, colors, alignment, component sizing, scrolling, and responsive behavior.
- Correct visible discrepancies before reporting completion.
- Run relevant tests and the production build where applicable.
- Report actual verification performed. Never claim visual verification if the page was not visually inspected.

## 7. Scope Control
- Change only what is needed for the requested screen or workflow.
- Avoid unrelated refactoring, duplicate components, unnecessary dependencies, and changes to other pages.
- Follow the current architecture and established conventions rather than inventing a new system.
- Clearly report any remaining visual differences, failed tests, or verification limitations.