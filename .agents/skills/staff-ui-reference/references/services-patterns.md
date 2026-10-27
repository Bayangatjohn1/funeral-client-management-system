# Branch Admin Services

Tabs, search/status/sort/reset and pagination fetch server-rendered hub content and replace only `[data-service-hub]`, preserving the shell and Grid/Table preference. Reinitialize the replaced hub once; modal listeners have one owner. Sorting supports Name A–Z/Name Z–A/Newest/Oldest across the complete query before pagination. Render all saved package inclusions/freebies, never slice to three; empty saved content stays honestly empty. Do not fabricate catalog data.

Keep `.service-hub-tabs` itself transparent, in addition to its nav wrapper. Modal iframe detail pages explicitly use Plus Jakarta Sans for body/headings and staff light/dark tokens; audit iframe typography and section surfaces independently from the parent overlay.

Use the native Services dialog with 12px card border, 20px header, 44px close button, full viewport scrim, focus containment/return and Escape support inside the iframe. Hide Back to Service Management links only in modal detail requests. Server-paginated hub has `data-service-server-navigation`: the shared JS initializer must skip it so page-local filter/modal behavior has one owner; tabs navigate once through their actual href, without simultaneous client activation/history rewriting.

Catalog View controls use `type="button"` with existing modal URL hooks, avoiding whole-page navigation. Initialize the page-local modal script after its markup is parsed. Branch Admin cards omit relative updated-at text (such as 1 week ago); keep eye action aligned to the right. Verify the parent Services URL remains unchanged and iframe details load within the overlay.

View actions in Grid and Table are 44px eye-only links, with catalog-specific accessible labels (View Package/Casket/Add-on/Freebie) and left-side tooltips on hover/focus. Keep original href/modal hooks. Ensure the row action wrapper is always visible, rather than only on row hover.

Use `partials/services_visual_styles.blade.php` with staff foundation tokens. Preserve Packages/Caskets/Add-ons/Freebies tabs, counts, Grid/Table toggle, current-section search, status options, reset, catalog view modals and pagination. Branch Admin remains read-only; never copy System Admin write permissions.

Keep transparent page/nav/view-switch surroundings with independent card surfaces. Filters use a sticky 16px inset and shared is-stuck emphasis; wrappers own 44px sage borders and inner controls stay transparent. Cards use 16px gaps/padding and three/two/one columns. Tables retain all columns and scroll horizontally on narrow screens; pagination stays outside on transparent background. Actions must remain visible. Verify all tabs, search/reset, status, Grid/Table and detail modal in both themes and mobile; record missing checks honestly.
