# User account security

Ordinary Edit User changes profile and branch assignment while preserving the role. A System Admin can promote or demote another operational account between Staff and Branch Admin through Change Role. The request requires the actor's current password, an explicit confirmation, a reason, an active branch, and the expected current role. Branch Admins cannot invoke this action.

System Admin access uses a separate personal account. It can optionally be linked uniquely to an active operational account for reference. The dedicated creation action requires System Admin access, current-password verification, a reason and confirmation. Existing operational accounts keep their identity and operational history. The privileged account must replace its temporary password and enroll an authenticator before accessing application pages.

System Admin, Branch Admin and Staff login requires a password and an authenticator code or unused recovery code. Authenticator secrets are encrypted; recovery codes are hashed and encrypted, shown once, and consumed transactionally. Accepted authenticator time steps cannot be reused. Security submissions are rate limited. Password replacement after an administrative reset still requires the existing MFA factor.

Role changes, deactivation, password resets and MFA changes write security audit records inside their database transactions. Audit failure rolls back the security change. Access changes increment a security version, rotate the remember token, and revoke database sessions. Session fingerprints also check role, branch, active status, email and password on subsequent requests. Concurrent MFA requests recheck the account under a row lock.

Users cannot change their own role or deactivate themselves through user management. System Admin deletion is blocked. The active System Admin safeguard locks administrator records before checking the remaining administrators. A linked operational account cannot be deleted through the profile endpoint.

## Rollout and recovery

The additive account-security migration has been applied locally. Existing sessions must sign in again. Existing System Admins, Branch Admins and Staff enroll MFA on their next login; their password is retained unless a password reset requires replacement. Newly created privileged accounts enroll MFA, save recovery codes, then replace the temporary password on a separate page. Store the eight recovery codes securely outside the application. No administrative MFA bypass or reset endpoint is provided; loss of both authenticator and all recovery codes requires a separately reviewed recovery procedure.

## Administrative password recovery

Edit User exposes a separate reset dialog for operational accounts. Branch Admins reset only their own branch's Staff; System Admins reset Staff and Branch Admins. System Admin and Owner targets are excluded from this administrative reset action; they use the designated recovery process, including configured email recovery where available.

The dialog generates a temporary password with browser cryptographic randomness and provides Show, Copy and Regenerate controls. Credentials are never placed in browser storage or restored from validation errors. The administrator must supply their own current password and a reason after independently verifying identity. Reset changes the password, sets the mandatory-change flag, revokes sessions/remember tokens, invalidates pending email reset links and writes the reason to the security audit transaction. Audit failure rolls the operation back.

After signing in with the temporary password, an enrolled user must verify MFA before reaching the dedicated personal-password page. A new enrollment displays recovery codes before that page. Protected navigation and mutations remain blocked until password replacement succeeds. Replacement requires 12 or more characters, letters and numbers, matching confirmation, and a different password from the current temporary one; submissions are rate limited. Current password is not requested again on this page because login and MFA already verified the session. MFA remains enabled. No Owner privileges or administrative MFA reset are added.

The focused reset/MFA/login/session suite passes 54 tests. Frontend build and Blade compilation pass; authenticated desktop/mobile checks in both themes remain unverified because no authenticated browser session is available.

Keep the application's encryption key stable and backed up securely. Losing it makes encrypted MFA secrets unreadable. Use HTTPS and secure session cookies in production.

## Validation

Feature tests cover server authorization, promotion and demotion, forbidden System Admin conversion, stale forms, inactive branches, audit rollback, session revocation, MFA enrollment, replay prevention, recovery-code consumption, login throttling, separate privileged-account creation, form rendering and password-reset MFA requirements. The frontend build and Blade compilation were checked. Authenticated visual checks in both themes remain outstanding because the browser session was logged out.

Broader dashboard and intake regression tests have failures outside the focused security workflow. Dependency audit also reported existing league/commonmark advisories; resolve those before a production release. These findings do not establish that the full application passes regression or security review.

## Account creation flow

System Admins choose Staff, Branch Admin or System Admin from the Add User modal. The destination form has a fixed role. Branch Admins go directly to the Staff form with their branch assigned. Optional name/contact fields and the optional existing-account link are labelled explicitly. Additional Owner accounts are excluded from this flow.

## Inactivity timeout

System Admin, Branch Admin and Owner sessions expire after 15 minutes without activity. Staff sessions expire after 30 minutes. The server enforces expiry before serving authenticated application requests, including mutations and keep-alive requests. Missing timeout state, including an older session or a remembered login without a live session, requires signing in again. Normal foreground navigation updates activity; JSON/AJAX requests and background status polling do not.

The shared warning appears one minute before expiry on panel, authenticated guest/MFA and modal-frame pages. Real keyboard/touch/pointer/wheel interaction can extend a still-valid session, at most once per 30 seconds; once the warning is open, choose Stay signed in explicitly. Tabs share deadlines through browser storage and non-extending server polling. Session-specific identifiers prevent a stale tab from extending a different sign-in. Offline failures keep the warning visible; the server still denies expired requests when connectivity returns.

This change does not introduce automatic saving. Existing intake draft/recovery behavior remains; other unsaved form changes can be lost when redirected to login. Expiry invalidates the current session and clears remembered authentication. Active sessions on other devices are not deleted. Browser visual checks remain outstanding for the warning modal across themes and viewport sizes.

