# Add/Edit Validation Audit

**Date:** 30 October 2026  
**Scope:** Branch Management, User Management, Package Management  
**Status:** Inspection and recommendations only. No fixes approved or implemented.

## Scope and evidence

This report records the validation audit completed against the current Laravel working tree. Blade inputs, HTML attributes, page JavaScript, shared form behavior, controller rules, custom closures, models, migrations, and existing validation tests were inspected. Current database column types, nullability, indexes, and foreign keys were confirmed through read-only schema queries.

No application code, database records, or functionality was changed for the audit. Tests were inspected but **not executed**. Browser error flows were not reproduced because the browser was at login. Findings described as source-based risks need runtime verification before implementation decisions.

**FE** = frontend, **BE** = backend, **DB** = database. “Same” means shared source rules, not a browser-tested result. No dedicated Form Request or separate Alpine validation implementation was found for these forms; controller validation is authoritative.

### Main findings

1. Standalone Branch forms can retain stale custom validity after a name is corrected; the index modal has a different validity lifecycle.
2. Package freebie catalog errors have a wildcard MessageBag rendering path that attempts to print arrays as strings.
3. Package excess-distance rates, extra-day rates, and promo values lack the backend precision/maximum rules applied to Base Price.
4. Several Package backend errors appear only in the summary, not beside their fields.
5. Enabled promo data can be cleared without a validation error when type/value is missing or value is zero.
6. Current `users` schema has first and last names but no middle-name or suffix columns. The latter fields and rules are conditional in source.

## Source reference index

Paths are relative to **C:/xampp/htdocs/funeral-system/**.

| ID | Actual source file and relevant methods |
|---|---|
| B1 | `app/Http/Controllers/Admin/BranchController.php`: `store`, `update`, `nextBranchCode`, `branchValidationRules`, `branchValidationMessages`, `validBranchAddressRule`, `trimBranchInput`, `hasDisableBlockers` |
| B2 | `resources/views/admin/branches/create.blade.php`: standalone Add markup and validation script |
| B3 | `resources/views/admin/branches/edit.blade.php`: Edit markup and validation script |
| B4 | `resources/views/admin/branches/index.blade.php`: Add modal, `bindBranchNameValidation`, `bindBranchFormValidation`, Edit loader |
| U1 | `app/Http/Controllers/Admin/UserController.php`: `store`, `updateLocked`, `userValidationRules`, `userValidationMessages`, `validNamePartRule`, `uniqueNamePartsRule`, `validEmailAddressRule`, `validPhilippineMobileRule`, assignment/scope checks |
| U2 | `resources/views/admin/users/_create_form.blade.php`: Add form and validation script; included by `create.blade.php` |
| U3 | `resources/views/admin/users/edit.blade.php`: Edit form and validation script |
| U4 | `app/Models/User.php`, `app/Support/NormalizesName.php`: name normalization and construction |
| P1 | `app/Http/Controllers/Admin/PackageController.php`: `packageValidationRules`, `packageValidationMessages`, `requiredIncludedCasketRule`, `atLeastOneIncludedServiceRule`, `cleanIncludedServices`, `cleanFreebies`, `assertCatalogItemsAvailable`, `resolvePromoPayload`, `trimPackageInput` |
| P2 | `resources/views/admin/packages/_form.blade.php`: shared Add/Edit markup, `syncServices`, `validateRequiredCasket`, `syncPromo` |
| P3 | `resources/js/service-form-feedback.js`: native inline error rendering and name correction |
| P4 | `app/Models/Package.php`, `app/Models/PackageInclusion.php`, `app/Models/PackageFreebie.php`: casts, service normalization and relationships |
| L1 | `vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php`: default message templates; no application `lang` directory found |
| L2 | `vendor/laravel/framework/src/Illuminate/Support/MessageBag.php`: `get`, `getMessagesForWildcardKey` |

## A. Branch Management — Add/Edit Validation

| Field | Required/Optional | Validation Rules | Add Behavior | Edit Behavior | Backend Enforcement | Source File |
|---|---|---|---|---|---|---|
| Branch Code: branch identifier | Generated | Readonly input without submitted name; generated as BR plus next numeric sequence | Server assigns code | Preserved | Controller ignores submitted code; DB unique, non-null varchar(10) | B1–B3 |
| `branch_name`: display name | Required | FE required; JS Unicode letters/marks, spaces, apostrophe, period, &, hyphen; starts with letter/mark. BE required, string, max 255, regex. Whitespace normalized. No uniqueness rule | Modal/standalone scripts differ | Same backend rules | `branchValidationRules`; DB non-null varchar(255), not unique | B1–B4 |
| `address`: location | Required in application | FE required; JS requires a Unicode letter/mark. BE required, string, max 255, custom place-name check | Required | Required even if existing DB value is null | DB nullable varchar(255); application stricter | B1–B4 |
| `is_active`: operating status | Optional request field | BE boolean; Add hidden 0 and checkbox 1 | Select initial status | Hidden existing status; status changed through Details | BR001 must remain active; active branch disable blocked by unfinished cases or completed cases with balance; DB non-null default true | B1–B4 |
| `return_to`: navigation | Optional | Field/destination validation not found | Used directly by redirect | Same | `redirect()->to($returnTo)` | B1–B3 |
| `form_context`: modal origin | Technical/optional | Field validation not found | Present in Add modal | Not a business field | Not validated branch data | B4 |

### 1. Existing validations correctly represented in source

- Required name/address, character restrictions and backend length limits.
- Server-generated code protected by DB uniqueness.
- BR001 and unfinished/unpaid-case disable restrictions.
- Index modal updates custom validity on input and blur.

### 2. Frontend-only rules

No exclusively frontend business restriction confirmed. Readonly code is also protected by controller behavior.

### 3. Missing or insufficient rules

- Name/address have no matching FE maxlength 255.
- Code generation reads current maximum; no concurrency retry observed. DB prevents duplicates but no friendly conflict handler found.
- `return_to` destination restriction not found.
- `trimBranchInput` converts name/address to strings before validation, weakening validation of original request types.
- Branch-name uniqueness is absent; whether required **needs confirmation**.

### 4. Add/Edit and entry-point inconsistencies

- Status editable on Add but hidden on Edit is intentional.
- Standalone Add/Edit call `sync()` initially. Subsequent input listeners clear displayed errors but do not rerun `sync()` or clear custom validity. An initial blank/invalid name may stay invalid after correction. **Source-based risk; not browser-reproduced.**
- Index modal reruns validity checks on input/blur, unlike standalone scripts.

### 5. Duplicate or conflicting rules

- Name logic repeated in controller, standalone Add, standalone Edit and index modal.
- “Letters only” message conflicts with allowed punctuation.
- DB nullable address versus required application address is a layer difference, not automatically a defect.

### 6. Message/display issues

- Default string/max/boolean messages remain technical.
- Edit has no dedicated inline `is_active` error output; index shows first global error.
- Stale custom validity can make the visible correction and browser message disagree.

## B. User Management — Add/Edit Validation

### Regular Staff and Branch Admin forms

| Field | Required/Optional | Validation Rules | Add Behavior | Edit Behavior | Backend Enforcement | Source File |
|---|---|---|---|---|---|---|
| `first_name`: given name | Required with split-name schema | FE required; JS requires a letter; letters/marks, spaces, period, apostrophe, hyphen; no digits. BE required, string, max 100, custom name rule | Required in current schema | Same | DB nullable varchar(255); no cross-user uniqueness | U1–U4 |
| `middle_name` | Optional, schema-dependent | FE name check; BE nullable string max 100 plus custom name rule only if column exists | Not rendered with current schema | Same | Current DB column **not found** | U1–U3 |
| `last_name`: surname | Required with split-name schema | First-name rules plus case-insensitive exact duplicate-name-parts check | Required | Same | `uniqueNamePartsRule`; error assigned to Last Name; DB nullable varchar(255) | U1–U4 |
| `suffix` | Optional, schema-dependent | Select; BE nullable in Jr., Sr., II, III, IV, V if column exists | Not rendered with current schema | Same | Current DB column **not found**; duplicate-parts check includes submitted suffix | U1–U3 |
| `name`: legacy full name | Required only without split-name schema | Fallback FE required; BE required string max 255 and custom name rule | Current split fields build name; legacy payload can be parsed | Same | DB non-null varchar(255); no person-name uniqueness | U1, U4 |
| `email`: login | Required | FE email input/required; BE custom RFC then DNS check, unique users email. Explicit string/max rule not found | Must be unique | Ignores current user's ID for uniqueness | DB non-null varchar(255), unique, case-insensitive collation | U1–U3 |
| `password`: temporary password | Required Add | FE required/minlength 6; JS length >=6; BE required string min 6 | Generated or manually entered | No ordinary Edit field; nonempty submitted password rejected | Hashed; confirmation/composition/max rule not found | U1–U3 |
| `role`: permission category | Required; workflow-controlled | Aliases normalized; BE in admin/staff plus actor/target checks | Staff or Branch Admin through scoped workflow | Ordinary Edit cannot change privileges | Controller scope and role guards; separate Change Role action | U1–U3 |
| `admin_scope`: internal scope | Derived | Not in shared validation array; explicit guards reject legacy/unauthorized system scope | Derived from role | Existing privileged scope protected | `normalizeRequestedRole`, `ensureRoleAssignmentAllowed`, update comparison | U1 |
| `branch_id`: assignment | Required except System Admin | Add custom chooser/hidden field; Edit select. BE required or nullable, exists; active/scope checks; explicit integer rule not found | System Admin chooses active branch; Branch Admin Staff forced to own branch | Current inactive assignment can remain; new assignment must be active | DB nullable unsigned FK; controller checks | U1–U3 |
| `contact_number`: mobile | Optional | FE and BE accept 09, 639, +639 followed by nine digits after stripping spaces/parentheses/hyphens. BE nullable string max 50 | Same | Same | DB nullable varchar(50); no unique rule | U1–U3 |
| `address`: contact address | Optional | FE text; Add attempted blank check. BE nullable string max 255, `notBlankRule` | Optional | Optional | Blank normalization can make blank rule ineffective; DB nullable varchar(255) | U1–U3 |
| `is_active` | Not editable in these forms | No Add/Edit field rule | Controller sets true | Preserved | Separate status workflow | U1 |
| `current_password`: authorize System Admin email change | Conditional | Edit input not unconditionally required; BE required/current_password:web if System Admin email changes | Not regular Add | Authenticated actor's current password required on change | `updateLocked` | U1, U3 |
| `return_to`: navigation | Optional | Not ordinary field validation | Checked by `safeReturnTo` | Same | Safe-return helper | U1–U3 |

### Dedicated System Admin Add form

This form reuses User Add and therefore belongs in this audit; separate role-change/password-reset forms are not audited as ordinary Edit.

| Field | Required/Optional | Validation Rules | Add Behavior | Edit Behavior | Backend Enforcement | Source File |
|---|---|---|---|---|---|---|
| `password` | Required | FE minlength 12; privileged validation required string min 12; shared validation later min 6 | Effective BE minimum 12; shared JS still checks 6 | No ordinary Edit | Two validation passes | U1, U2 |
| `current_password` | Required | FE required; BE required/current_password:web | Must match acting System Admin | Conditional email-change rule above | Privileged endpoint | U1, U2 |
| `privileged_for_user_id`: linked regular account | Optional | Nullable integer; exists active Staff/Admin with null/branch scope; unique link | Eligible options; transaction rechecks active account | Not ordinary Edit | DB nullable unique self-FK, restrict delete | U1, U2 |
| `reason`: justification | Required | FE required min 10 max 500; BE required string min 10 max 500 | Required | Not ordinary Edit | Privileged endpoint | U1, U2 |
| `confirmed`: access confirmation | Required acceptance | FE required checkbox; BE accepted | Must accept | Not ordinary Edit | Privileged endpoint | U1, U2 |

### 1. Existing validations correctly represented in source

- Email uniqueness handles Add and current-user Edit correctly; RFC/DNS checks exist.
- Philippine mobile validation on both layers.
- Backend permission, branch-scope and active-branch enforcement.
- Current inactive branch retention is an explicit Edit exception.
- Password reset/role changes separated from ordinary Edit.
- Model normalizes name case/spacing; normalization is not validation.

### 2. Frontend-only rules

JS requires at least one letter in a populated name. BE name regex allows punctuation without a letter requirement; punctuation-only `---` can satisfy BE while FE rejects it. No other exclusive FE business restriction confirmed.

### 3. Missing or insufficient rules

- Email has no explicit length limit matching DB 255.
- Names/contact/address lack matching FE maximum lengths.
- Regular password max/confirmation/composition rules not found; stronger policy **needs confirmation**.
- Branch ID has no explicit integer rule, although existence/scope/FK checks exist.
- Middle name/suffix absent in current schema.
- Person-name uniqueness not found; requirement **needs confirmation**.

### 4. Add/Edit differences

- Required Add password versus prohibited ordinary Edit password is intentional.
- New inactive assignment blocked versus retaining current inactive assignment is intentional.
- Create name-format message uses ñ/Ñ; Edit uses “enye.”
- System Admin password HTML/BE minimum 12 versus shared JS message/check 6.
- Add whitespace-address check runs after normalization; Edit lacks equivalent explicit check.

### 5. Duplicate/conflicting rules

- Email custom closure runs RFC then RFC+DNS; email.email/rfc/dns message-map entries are not how that closure emits its errors.
- Privileged password checked at 12 then again at 6.
- Name logic repeated across Add JS, Edit JS, BE closure and model normalization.
- Conditional schema checks determine middle/suffix availability.

### 6. Message/display issues

- Duplicate-name-parts failure attached to Last Name regardless of conflicting pair.
- DNS message cannot distinguish invalid domain from lookup failure.
- Privileged default messages expose internal names.
- Ordinary Edit password rejection uses HTTP 422 abort, not normal inline error.
- Privileged reason/current-password/confirmation Add errors lack individual inline output; summary is available.

## C. Package Management — Add/Edit Validation

Both forms use shared markup and controller rules.

| Field | Required/Optional | Validation Rules | Add Behavior | Edit Behavior | Backend Enforcement | Source File |
|---|---|---|---|---|---|---|
| `name`: title | Required | FE required, case/space correction; BE required string max 150, contains letter, allowed name characters; no unique rule | Same | Same | Custom closures; DB non-null varchar(255) | P1–P3 |
| `short_description` | Optional | FE maxlength 500; BE nullable string max 500 | Same | Same | DB nullable text | P1, P2 |
| `price`: base price | Required | FE number min 0 max 9999999999.99 step .01; BE required numeric same min/max, decimal:0,2 | Same | Same | DB non-null decimal(12,2) | P1, P2 |
| `included_services` | Required array | Required/array, at least one cleaned inclusion; no general FE at-least-one check found | Recognized enabled or retained custom inclusion must remain | Same | `atLeastOneIncludedServiceRule`; unsupported types ignored | P1, P2 |
| `included_services.*.enabled` | Optional per row | FE 0/1 switches; BE nullable boolean | Disabled row fields disabled by JS | Same | Disabled rows ignored during cleaning | P1, P2 |
| `included_services.*.description` | Optional | BE nullable string max 255 | No current visible editor found | Same | Standard cleaning does not use it as service name | P1 |
| Body Retrieval `included_kilometers` | Required when enabled | FE required number min 0; BE required_if, nullable integer 0–999999 | Disabled when off | Same | Specific and wildcard rules | P1, P2 |
| Hearse `included_kilometers` | Required when enabled | Same distance rules | Same | Same | Same | P1, P2 |
| `price_per_excess_kilometer` | Optional | FE min 0 step .01; BE nullable numeric min 0; no max/decimal rule | Blank means no configured charge | Same | Rounded to 2 decimals; DB nullable decimal(12,2) | P1, P2 |
| Embalming `included_days` | Required when enabled | FE required min 1 max 365; specific BE min 1/max 365; overlapping wildcard min 0/max 365 | Same | Same | Effective zero behavior needs focused test | P1, P2 |
| Home Viewing `included_days` | Required when enabled | Same days rules | Same | Same | Same overlap | P1, P2 |
| `price_per_extended_day` | Optional | FE min 0 step .01; BE nullable numeric min 0; no max/decimal rule | Blank means no configured charge | Same | Rounded to 2 decimals; DB nullable decimal(12,2) | P1, P2 |
| Hearse `hearse_catalog_id` | Required when Hearse enabled | FE required select; BE required_if, nullable integer, exists active/available | Eligible choice | Must resolve invalid saved dependency | Ordinary rules and transactional availability recheck; nullable FK | P1, P2 |
| Casket `casket_catalog_id` | Required when Casket enabled, legacy exception | FE custom check, no native required; BE wildcard integer/exists, custom required/active/available rule | Reject missing/invalid selection | Same; invalid saved item cannot remain on save | Parent/child closure plus transaction; nullable FK | P1, P2 |
| `casket_type` | Optional/derived | Hidden; BE nullable string max 150, allowed characters | Catalog display name snapshot | Same | Legacy text compatibility exception | P1, P2 |
| `custom_inclusions` | Optional array | Nullable array; no max row count found | No Add editor; preserved hidden values | Existing hidden values submitted | Cleaning converts nonblank rows | P1, P2 |
| `custom_inclusions.*.description` | Optional; blank ignored | Nullable string max 255, letter/allowed characters when populated | Nonblank becomes inclusion | Same | DB saved name non-null varchar(255) | P1, P2 |
| `freebies` | Optional array | Nullable array | Unselected fields disabled | Same | Deduplicated by lowercase name + unit | P1, P2 |
| `freebies.*.freebie_catalog_id` | Optional generally | Nullable integer, exists active/available | Hidden selected ID | Resolve invalid saved dependencies | Transactional recheck; current DB has no FK for this column | P1, P2 |
| `freebies.*.freebie_name` | Optional request field | Nullable string max 255, letter/allowed characters when populated | Catalog can supply missing name | Legacy/custom editable where present | Nameless rows discarded; DB saved name required | P1, P2 |
| `freebies.*.quantity` | Optional request field | FE number min 1; BE nullable integer 1–999 | Missing/blank becomes at least 1 | Same | DB unsigned integer default 1 | P1, P2 |
| `freebies.*.unit` | Optional request field | FE text; BE nullable string max 40 | Missing/blank becomes item | Same | DB non-null varchar(40), default item | P1, P2 |
| `promo_is_active` | Optional request field | FE hidden 0/switch 1; BE boolean | Controls visible fields | Same | Resolver may clear promo payload | P1, P2 |
| `promo_label` | Optional | BE nullable string max 120, allowed characters | Same | Same | DB nullable varchar(120) | P1, P2 |
| `promo_value_type` | Optional | FE select; BE nullable in AMOUNT/PERCENT | No conditional required rule | Same | Missing type clears promo | P1, P2 |
| `promo_value` | Optional | FE min 0 step .01; BE nullable numeric min 0 | Enabled promo persists only if value >0 and type supplied | Same | Otherwise cleared; no percent ceiling, max amount, decimal rule found | P1, P2 |
| `promo_starts_at` | Optional | FE date; BE nullable date | Same | Same | Asia/Manila parsing; nullable datetime | P1, P2 |
| `promo_ends_at` | Optional | FE date; BE nullable date after_or_equal start | Same | Same | End of day; nullable datetime | P1, P2 |
| Package `is_active` | Not Add/Edit field | No Add/Edit rule | Set active | Preserved | Separate status workflow | P1 |
| `return_to` | Optional technical field | No field rule in package validation | Navigation | Same | Not package data; redirect restriction needs separate review | P1, P2 |
| Legacy `coffin_type`, `inclusions`, `legacy_freebies` | Compatibility inputs | Converted to structured rows in `trimPackageInput` | Legacy compatibility | Same | Applicable structured rules after conversion; legacy Casket exception | P1 |

### 1. Existing validations correctly represented in source

- Base Price FE/BE bounds and precision match DB capacity.
- Description max exists in both layers.
- Enabled service limits required; rates optional.
- Catalog existence/availability enforced in backend and rechecked transactionally.
- Shared Add/Edit rules; model casts do not substitute for validation.

### 2. Frontend-only rules

- Rate and promo two-decimal step checks; BE accepts greater precision. Rates are rounded later.
- Days FE minimum 1 versus overlapping BE min 1/min 0: effective zero behavior **needs focused testing**.

### 3. Missing or insufficient rules

- Rate/promo maximums and precision missing in BE despite decimal(12,2) DB columns.
- Enabled incomplete/zero promo silently becomes no promo.
- Percentage ceiling and amount-discount relationship to base price not found; desired business policy **needs confirmation**.
- FE maximums absent for distances, quantity, unit, name, promo label.
- Explicit nested-row array/allowed-key rules and global row-count bounds not found.
- Legacy Casket text exception remains; whether new requests should use it needs confirmation.
- Package-name uniqueness not found; requirement needs confirmation.
- String coercion of name/description/promo label happens before validation.

### 4. Add/Edit differences

Same rule set. Edit can retain legacy/custom rows and invalid saved dependencies not ordinarily encountered on Add, causing validation when changing another field.

### 5. Duplicate/conflicting rules

- Casket required closure attached to both parent and child.
- Specific/wildcard distance, day and Hearse ID rules overlap; day minimums differ.
- Transactional repeated availability check is useful race protection.
- Promo fields hidden but not disabled when off; retained invalid hidden values may still fail validation.

### 6. Message/display issues

- Service limit/rate, promo, and freebie quantity/unit/name errors mostly summary-only.
- `$errors->get('freebies.*.freebie_catalog_id')` returns arrays per matching key, but loop prints each as a scalar. L2 confirms wildcard shape. **Source-confirmed rendering risk; not browser-reproduced.**
- Generic exists errors do not describe archived/unavailable state or identify catalog name.

## Database constraints confirmed by read-only inspection

| Area | Current constraints |
|---|---|
| Branches | Code unique/non-null varchar(10); name non-null varchar(255); address nullable varchar(255); active non-null default true; branch_type enum main/branch |
| Users | Email unique/non-null varchar(255); name non-null varchar(255); first/last nullable varchar(255); middle/suffix absent; contact nullable varchar(50); address nullable varchar(255); branch nullable FK; privileged link nullable unique self-FK |
| Packages | Name non-null varchar(255), not unique; price non-null decimal(12,2); description nullable text; promo label nullable varchar(120); promo value nullable decimal(12,2); promo dates nullable; status non-null |
| Package inclusions | Package FK; Casket/Hearse nullable FKs; inclusion name non-null varchar(255); distance unsigned int; days unsigned smallint; rates nullable decimal(12,2) |
| Package freebies | Package FK; catalog ID nullable **without current FK**; name non-null varchar(255); quantity unsigned int default 1; unit non-null varchar(40) default item |

Catalog availability, permitted text characters, 365-day bounds, and branch assignment eligibility are application rules, not established by these DB types. Business-rule CHECK constraints were **not confirmed**.

Migration sources: `database/migrations/2026_02_12_085423_create_branches_table.php`; `0001_01_01_000000_create_users_table.php`; `2026_02_12_095453_add_role_to_users_table.php`; `2026_02_24_090000_add_centralized_and_partial_payment_fields.php`; `2026_04_27_100004_phase1_add_user_name_split_and_admin_type.php`; `2026_10_11_000001_add_account_security_to_users.php`; `2026_02_21_090000_create_packages_table.php`; `2026_02_25_221000_add_promo_fields_to_packages_table.php`; `2026_04_27_100005_phase1_create_package_normalized_tables.php`; `2026_07_19_000001_add_structured_package_setup_fields.php`; `2026_10_29_000001_add_catalog_availability_and_package_hearse.php`.

## Validation Message Review

Messages below are exact source strings or explicitly identified default templates. `{field}`, `{name}`, and similar placeholders in recommendations are proposed contextual substitutions, not claims about current wording. Recommendations apply only to existing rules; new business thresholds require confirmation.

### Branch messages

| Form | Field / Rule | Current Message | Issue | Recommended Message | Reason |
|---|---|---|---|---|---|
| Add/Edit | Name required | `Branch name is required.` | Clear; standalone stale-validity risk | Keep wording | Identifies field |
| Add/Edit | Name regex | `Branch name must contain letters only.`; standalone Add custom validity adds `(no numbers).` | Allowed punctuation not explained; variants differ | `Start Branch Name with a letter. Use letters, spaces, apostrophes, periods, & or hyphens.` | Matches regex |
| Add/Edit | Address required | `Address is required.` | Context could be clearer | `Branch Address is required.` | Specific label |
| Add/Edit | Address custom check | `Address must include a valid place name.` | Actual test only requires a letter | `Enter an address containing a place name, not numbers only.` | Actionable correction |
| Edit | Protected main branch | `Main branch (BR001) must remain active.` | Correct; no dedicated status inline output | Keep; display in status context | Actual business rule |
| Edit | Disable blockers | `This branch cannot be disabled while it has unfinished cases or completed cases with a remaining balance.` | No branch name | `Cannot disable "{branch}". Complete its unfinished cases and settle remaining balances first.` | Target and resolution |
| Add/Edit | Name/address max | Default string max template | No matching FE limit | `{Field} must be 255 characters or fewer.` | Actual limit |
| Add/Edit | String | Default string template | Technical | `Enter {Field} as text.` | Plain language |
| Add/Edit | Status boolean | Default boolean template | Internal boolean wording | `Choose Active or Inactive for the branch status.` | Actual choices |
| Edit loader | Fetch failure | `Unable to load content.` | System error, not validation; no next action | `Could not load Edit Branch. Close this window and try again.` | Appropriate recovery |
| Status check | Fetch failure | `Unable to check branch status`; `Please try again. No branch status was changed.` | Appropriate system feedback | Keep | Does not invent cause |

### User messages

| Form | Field / Rule | Current Message | Issue | Recommended Message | Reason |
|---|---|---|---|---|---|
| Add/Edit | Names required | `First name is required.` / `Last name is required.` / `Name is required.` | Clear; FE label derived dynamically | Keep consistent labels | Field identified |
| Add/Edit | Digits in name | `{Field} must not contain numbers.` | Clear | Keep | Actual rule |
| Add/Edit | Name characters | BE `{Field} may only contain letters, spaces, hyphen, apostrophe, period, ñ, Ñ, and accented letters.` FE begins `Name fields`; Edit uses `enye` | Long, nonspecific FE label, inconsistent variants | `{Field} can contain letters, spaces, apostrophes, periods and hyphens.` | Shorter; Unicode letters included |
| Add/Edit | FE name contains letter | Uses name-character message | Punctuation-only cause not explained; BE parity missing | `{Field} must contain at least one letter.` | Existing FE rule only; BE fix not authorized |
| Add/Edit | Duplicate parts | `Name parts must not be exact duplicates.` | Attached to Last Name even when different pair conflicts | `{First field} and {second field} must not be identical.` | Identify actual pair |
| Add/Edit | Name length | Default string max template | FE limits absent | `{Field} must be 100 characters or fewer.`; legacy Name 255 | Actual conditional bounds |
| Add/Edit | Email required | `Email address is required.` | Clear | Keep | Specific |
| Add/Edit | Email RFC | FE `Enter a valid email address.`; BE `Please enter a valid email address.` | Minor inconsistency | `Enter a valid email address, such as name@example.com.` | Example helps |
| Add/Edit | Email DNS | `The email domain appears to be invalid.` | Invalid domain versus lookup failure unclear | `We could not validate this email domain. Check the address and try again.` | Avoid overstated certainty |
| Add/Edit | Email unique | `This email address is already taken.` | Correct, action can be clearer | `This email address is already in use. Enter a different email address.` | Explains correction |
| Add | Password required | `Password is required.` | UI says Temporary password | `Temporary Password is required.` | Matches label |
| Regular Add | Password min | `Password must be at least 6 characters.` | Correct | `Temporary Password must contain at least 6 characters.` | Consistent label |
| System Admin Add | Password min | Default 12-character BE; shared JS says 6 | Conflicting threshold | `Temporary Password must contain at least 12 characters.` | Effective privileged rule |
| Edit | Submitted password | `Use the separate password reset action.` | HTTP 422 abort, not inline validation | `Passwords cannot be changed in Edit User. Use Reset Password.` | Names action |
| Add/Edit | Role required/in | `Role is required.` / `Select a valid role.` | Allowed choices not explained | `Choose Staff or Branch Admin.` for regular creation | Actual choices |
| Add/Edit | Owner assignment | `Unauthorized to create owner accounts.` | Creation wording reused by guard | `Owner access cannot be assigned through this form.` | Applies to both |
| Add/Edit | Legacy scope | `Legacy global administrator scopes cannot be assigned.` | Internal terminology | `Choose Staff or Branch Admin. Create System Admin access through the dedicated action.` | Clear path |
| Ordinary Add | System scope attempt | `Create a separate personal System Admin account using the dedicated action.` | Appropriate | Keep | Correct workflow |
| Edit | Privilege change | `Use Change Role to change account privileges. System Admin requires a separate account.` | Appropriate | Keep | Correct workflow |
| Add/Edit | System access permission | `Only a System Admin can assign System Admin access.` | Accurate authorization feedback | Keep | Cause known |
| Edit | Protected system role | `System Admin access cannot be downgraded here.` / `System Admin access cannot be reassigned here.` | Here is vague | `System Admin access cannot be changed in Edit User.` | Names context |
| Add/Edit | Branch required | `Branch is required for staff and branch admin accounts.`; role-specific variants; FE `Branch is required for this account.` | Multiple variants | `Choose a branch for this account.` | Simple action |
| Add/Edit | Branch exists | `Select a valid branch.` | Existence failure unexplained | `The selected branch no longer exists. Choose another branch.` | Actual rule |
| Add/Edit | Active branch | `Select an active branch.` | Clear | `Choose an active branch for this account.` | Context |
| Add/Edit | Branch Admin restrictions | `Branch admins can only create staff accounts.` / `Branch admins can only manage staff accounts.` / `Branch admins cannot change staff accounts into admin accounts.` | Accurate | Keep | Known restrictions |
| Edit | Branch Admin to Staff | `Branch Admin accounts cannot be changed into Staff accounts.` | Guard wording can conflict with separate Change Role workflow | `Use Change Role to change this account's role.` | Distinguish ordinary Edit restriction |
| Add/Edit | Unknown role | `Unauthorized user role.` | Generic | `This role cannot be assigned through this form.` | Field context |
| Add | Actor permission | `You are not allowed to create user accounts.` | Appropriate authorization message | Keep | Not format validation |
| Add/Edit | Mobile format | `Enter a valid Philippine mobile number.` | No format example | `Enter a Philippine mobile number, such as 09171234567 or +639171234567.` | Accepted formats |
| Add/Edit | Contact max | Default string max template | FE max absent | `Contact Number must be 50 characters or fewer.` | Actual bound |
| Add/Edit | Address blank | `Address must not be blank.` | Optional field; normalized blank often null; questionable reachability | Investigate; do not label blank optional address as an error | Avoid contradiction |
| Add/Edit | Address max | Default string max template | No FE max | `Address must be 255 characters or fewer.` | Actual bound |
| Conditional suffix | In-list | `Select a valid suffix.` | Field absent in current schema | `Choose Jr., Sr., II, III, IV or V, or leave Suffix blank.` | Confirmed values |
| System Add/conditional Edit | Current password | Default `The password is incorrect.`; default required template | Actor identity unclear; Add summary-only | `Enter your current password.` / `Your current password is incorrect.` | Distinguish from new password |
| System Add | Linked account exists | Default `The selected privileged for user id is invalid.` | Technical; several eligibility causes | `Choose an active Staff or Branch Admin account eligible for linking, or leave this blank.` | Constrained existence |
| System Add | Linked account unique | Default `The privileged for user id has already been taken.` | Technical | `This account is already linked to a System Admin account. Choose another account or leave this blank.` | Actual conflict |
| System Add | Reason required/min/max | Default templates for reason | Summary-only | `Enter a reason for System Admin access, using 10–500 characters.` | Actual rules |
| System Add | Confirmation accepted | `The confirmed field must be accepted.` | Technical; summary-only | `Confirm that this person requires System Admin access.` | Checkbox purpose |

### Package messages

| Form | Field / Rule | Current Message | Issue | Recommended Message | Reason |
|---|---|---|---|---|---|
| Add/Edit | Name required | `Package name is required.` | Clear | Keep | Specific |
| Add/Edit | Name letter | `Package name must include letters.` | Clear, can be precise | `Package Name must contain at least one letter.` | Actual rule |
| Add/Edit | Name characters | `Package name has unnecessary special characters.` | Unnecessary is subjective | `Start Package Name with a letter or number. Use letters, numbers, spaces and these symbols: . , ' ( ) / & -` | Actual regex |
| Add/Edit | Name max | Default string max | No FE max | `Package Name must be 150 characters or fewer.` | Actual bound |
| Add/Edit | Description max | Default string max | Inline exists | `Package Description must be 500 characters or fewer.` | Matches label |
| Add/Edit | Price required | `Price is required.` | Full field label missing | `Base Package Price is required.` | Context |
| Add/Edit | Price numeric | `Price must be a valid amount.` | Could specify correction | `Enter Base Package Price as a number.` | Numeric rule |
| Add/Edit | Price min | `Price cannot be negative.` | Correct | `Base Package Price cannot be negative.` | Context |
| Add/Edit | Price max | Default `The price field must not be greater than 9999999999.99.` | Hard-to-read amount | `Base Package Price cannot exceed ₱9,999,999,999.99.` | Confirmed bound |
| Add/Edit | Price precision | Default `The price field must have 0-2 decimal places.` | Technical wording | `Base Package Price can have up to two decimal places.` | Actual precision |
| Add/Edit | At least one inclusion | `At least one included service is required.` | Clear; may accompany Casket error | `Include at least one service before saving.` | Direct action |
| Add/Edit | Distance required | `Enter the included distance for this service.` | Summary lacks service name | `Enter the included distance for {Service}.` | Identify service |
| Add/Edit | Days required | `Enter the included days for this service.` | Same ambiguity | `Enter the included days for {Service}.` | Identify service |
| Add/Edit | Distance integer/min/max | Default templates with nested path | Technical, mostly summary-only | `{Service} Included Distance must be a whole number from 0 to 999,999 km.` | Actual bounds |
| Add/Edit | Days integer/min/max | Default templates with nested path | Min 1/min 0 overlap | `{Service} Included Days must be a whole number from 1 to 365.` only after effective minimum confirmed | Do not assume unresolved enforcement |
| Add/Edit | Rates numeric/min | Default templates with nested path | Readable label absent | `{Service} Extra Kilometer Rate must be a non-negative number.` / Extra Day Rate equivalent | Existing rules only |
| Add/Edit | Casket missing | FE `Please select a casket before saving.`; BE `Select an available casket before saving.` | Variants; potential duplicate parent/child error | `Choose an available included casket before saving.` | Consistent |
| Add/Edit | Casket exists | `Please select a valid casket.` | Deleted selection unexplained | `The selected casket no longer exists. Choose another casket.` | Exists failure |
| Add/Edit | Casket eligibility | `This casket is no longer available. Choose another casket.` | Item/state not identified | `Included casket "{name}" is {unavailable/archived}. Choose an available casket.` | Actual catalog context |
| Add/Edit | Hearse required/exists | Default required-if/exists | Internal nested field name | `Choose an available included hearse.` | Current requirement |
| Add/Edit | Transactional Hearse | `Choose an available included hearse.` | Item unnamed | `Included hearse "{name}" is no longer available. Choose another hearse.` | Item identified |
| Add/Edit | Casket text | `Casket type has unnecessary special characters.` | Hidden/derived field; vague | `The casket description contains unsupported characters. Choose the casket again.` | Correction path |
| Add/Edit | Custom inclusion letter/characters | `Custom inclusion must include valid description text.` / `Custom inclusion has unnecessary special characters.` | Vague; hidden retained rows can fail | `Custom Inclusion must contain at least one letter.` / explain permitted punctuation | Existing rules |
| Add/Edit | Freebie letter/characters | `Freebie must include valid description text.` / `Freebie has unnecessary special characters.` | Row not identified | `Freebie Name must contain at least one letter.` / identify unsupported characters and item | Specific |
| Add/Edit | Freebie name max | `Freebie must include valid description text.` | Incorrect explanation of length failure | `Freebie Name must be 255 characters or fewer.` | Actual rule |
| Add/Edit | Freebie exists/eligible | Default selected-value error | Nested path, array-rendering risk | `Freebie "{name}" is unavailable or archived. Replace or remove it.` when cause known | Specific cause |
| Add/Edit | Transactional Freebie | `A selected freebie is no longer available. Remove or replace it.` | No item name | `Freebie "{name}" is no longer available. Replace or remove it.` | Identify item |
| Add/Edit | Quantity min | `Freebie quantity must be at least 1.` | Accurate, row unnamed | `Quantity for "{name}" must be at least 1.` | Item context |
| Add/Edit | Quantity integer/max | Default templates | Summary-only | `Quantity for "{name}" must be a whole number from 1 to 999.` | Confirmed bounds |
| Add/Edit | Unit max | Default string max | No FE max/inline | `Unit for "{name}" must be 40 characters or fewer.` | Confirmed bound |
| Add/Edit | Promo label characters | `Promo label has unnecessary special characters.` | Vague | `Use letters, numbers, spaces and supported punctuation in Promo Label.` | Existing rule |
| Add/Edit | Promo label max | Default string max | Summary-only | `Promo Label must be 120 characters or fewer.` | Actual bound |
| Add/Edit | Promo type | `The selected promo value type is invalid.` | Technical | `Choose Amount or Percentage for Promo Discount Type.` | Actual choices |
| Add/Edit | Promo numeric/min | `Promo value must be a valid amount.` / `Promo value cannot be negative.` | Amount also refers to percentage | `Promo Value must be a number.` / `Promo Value cannot be negative.` | Both types |
| Add/Edit | Promo dates | Default date templates | Summary-only, technical labels | `Enter a valid Promo Start Date.` / End Date equivalent | Field identity |
| Add/Edit | Promo end ordering | `Promo end date must be on or after the start date.` | Correct | Keep | Resolution clear |
| Add/Edit | Incomplete enabled promo | No error; promo cleared | Silent outcome | Needs confirmation: explain not configured, or approve conditional required rules | Do not invent requirement |

### Default message families and remaining technical errors

These cover uncustomized rules across the field tables. `:attribute`, `:min`, and `:max` are literal Laravel template placeholders. Rendered labels may contain nested paths. No per-field custom attribute map was found.

| Rule | Exact current template | Affected fields | Recommendation |
|---|---|---|---|
| required | `The :attribute field is required.` | Uncustomized privileged fields | Use visible field name and correction |
| required_if | `The :attribute field is required when :other is :value.` | Hearse selection where not overridden | Name service and selection requirement |
| string | `The :attribute field must be a string.` | Text fields | Say text, not string |
| array | `The :attribute field must be an array.` | Package groups | Treat malformed section as payload/form error; do not imply ordinary text correction |
| integer | `The :attribute field must be an integer.` | Limits, IDs, quantities, linked account | Whole number for numeric fields; choose eligible item for IDs |
| numeric | `The :attribute field must be a number.` | Rates and uncustomized numbers | Use readable field/service label |
| boolean | `The :attribute field must be true or false.` | Status/service/promo switches | Identify switch or permitted state |
| max string | `The :attribute field must not be greater than :max characters.` | Names, descriptions, address, units | State actual field and character limit |
| max numeric | `The :attribute field must not be greater than :max.` | Limits, quantity, price | Include unit/currency |
| min string | `The :attribute field must be at least :min characters.` | Password/reason | Visible form label |
| min numeric | `The :attribute field must be at least :min.` | Limits/rates/quantity | Item/service and unit |
| exists/in | `The selected :attribute is invalid.` | Catalog/type/link selections | State known cause; do not guess unavailable when actual failure unknown |
| date | `The :attribute field must be a valid date.` | Promo dates | Start/End field label |
| unique | `The :attribute has already been taken.` | Linked account; email overridden | Identify actual conflict |
| accepted | `The :attribute field must be accepted.` | Privileged confirmation | Describe confirmation |
| current_password | `The password is incorrect.` | Authenticated actor password | Your current password |
| decimal | `The :attribute field must have :decimal decimal places.` | Base Price | Up to two decimal places |
| Native HTML | Browser-generated `validationMessage` | Required/type/min/max/step | Exact text varies by browser/language; **needs browser confirmation**. P3 renders this inline for Package |

### Error-location review

| Form | Current location | Concern |
|---|---|---|
| Branch modal Add | Inline name/address; index also first global error | Duplicate presentation possible; status/internal errors global |
| Branch Edit | Inline name/address; no dedicated status inline | Status business failure needs appropriate visible context |
| User Add/Edit | Inline common fields and all-error summary | Same error can repeat; privileged reason/confirmation/current-password Add summary-only |
| Package Add/Edit | All-error summary; explicit inline name/price/description/Casket/Hearse/general services/freebies | Limit/rate/promo/freebie quantity/unit/name errors mostly summary-only |
| Package native HTML | P3 creates inline error from browser text and sets aria-invalid/describedby | Browser text not customized; server association finds first nearby input and needs verification in compound wrappers |
| Package freebie catalog | Wildcard MessageBag loop plus summary | Nested array printed as scalar can prevent error form rendering |
| Unexpected loading errors | Modal-level text | Must remain distinct from field/business validation |

## Existing validation tests inspected

All paths relative to repository root. These are coverage observations, **not a fresh passing-test claim**.

| Source | Relevant tests/coverage |
|---|---|
| `tests/Feature/AdminScopeAuthorizationTest.php` | `test_create_user_rejects_invalid_email_format`, invalid-domain/duplicate-email tests, `test_update_user_allows_current_email_but_rejects_duplicate_email`, role/branch scoping, ordinary System Admin rejection |
| `tests/Feature/AccountSecurityWorkflowTest.php` | Ordinary Add/Edit privilege restrictions, separate System Admin creation/link uniqueness, role-preserving Edit |
| `tests/Feature/SystemAdminUserEditTest.php` | `test_system_admin_can_edit_a_user_and_keep_their_inactive_branch_selection` confirms choices; does not by itself exercise saving every inactive-assignment case |
| `tests/Feature/SystemRegressionGuardsTest.php` | `test_admin_cannot_deactivate_main_branch`, `test_branch_disable_explains_unfinished_and_unpaid_cases_and_blocks_both_paths` |
| `tests/Feature/SystemNegativeScenariosTest.php` | Missing user branch assignment rejected |
| `tests/Feature/NameGenerationConsistencyTest.php` | Name construction, lowercase capitalization, mixed case/acronyms, surname particles and apostrophes |
| `tests/Feature/AdminPackageStructuredWorkflowTest.php` | Structured Add/Edit; enabled Casket required; invalid text; `test_enabled_service_limits_are_required_but_rates_are_optional_and_details_show_days_and_nights` |
| `tests/Feature/CatalogAmountValidationTest.php` | `test_oversized_and_overprecise_amounts_are_rejected_on_create_and_update`: includes Package price |
| `tests/Feature/CatalogDependencyEligibilityTest.php` | Dependency eligibility, restore preserving latest selections, active required dependencies |
| `tests/Feature/PackageFormPresentationTest.php` | Named freebie controls and service labels |

### Coverage not found or not confirmed

- Standalone Branch correction clearing stale custom validity.
- Punctuation-only names rejected directly by user backend.
- Zero-day result under specific/wildcard rule overlap.
- Malformed nested Package row structures and oversized arrays.
- Wildcard Freebie error page rendering.
- Excess-rate/promo overflow and overprecision.
- Complete inline error locations after invalid Add/Edit submissions.
- Exact native browser messages in both themes/form entry points.

## Review decisions required before fixes

1. Confirm whether Branch/Package names must be unique. No such rule currently exists.
2. Confirm promo limits: percentage ceiling, discount versus base price, and enabled-incomplete promo behavior.
3. Confirm password policy beyond current minimums; no extra policy is assumed.
4. Confirm whether legacy Casket text/custom inclusion compatibility must remain for new payloads.
5. Confirm whether middle name/suffix are intended fields; current schema does not contain them.
6. Reproduce source-based frontend/rendering risks, then approve targeted fixes separately.

**Report-only delivery. No application fixes or database changes were made.**
