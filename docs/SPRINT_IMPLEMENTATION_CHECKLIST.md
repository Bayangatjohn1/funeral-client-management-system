# Funeral System Sprint Checklist

## Sprint 1 - Foundation Layer
- [x] Laravel project structure is present.
- [x] Authentication (login/logout) is implemented.
- [x] Role middleware aliases exist (`owner`, `admin`, `staff`).
- [x] Session table migration exists.
- [x] Basic role dashboards exist (`/owner`, `/admin`, `/staff`).
- [x] Login now regenerates session after authentication.
- [x] Logout now properly destroys session for all active users.
- [x] Duplicate admin route block removed and consolidated.
- [x] Duplicate sessions-table definition removed from base users migration for fresh setup consistency.
- [ ] Add feature tests for role-based access and login redirects.

## Sprint 2 - Core Structure Layer
- [x] Branch module exists (admin CRUD).
- [x] Add package management CRUD (admin only) using persistent package table.
- [x] Enforce admin-only package pricing changes.

## Sprint 3 - Operational Transaction Layer
- [x] Client module exists.
- [x] Deceased module exists.
- [x] Case module exists.
- [x] Intake flow (overlay form) exists.
- [x] Ensure case creation strictly uses package table records (not hard-coded options).
- [x] Add reusable validation rules for person-name fields and contact formats.
- [x] Deceased photo upload enabled (optional, validated, replace/remove support).

## Sprint 4 - Billing and Payment Layer
- [x] Payment module exists.
- [x] Payment status fields exist in case/payment flow.
- [x] Cash-only/full-payment behavior is implemented in intake logic.
- [x] Enforce DB-level full-payment consistency (`payments.amount == cases.total_amount`) via validation + transaction safeguards.
- [x] Remove any paths that allow partial values.

## Sprint 5 - Monitoring and Reporting Layer
- [x] Owner dashboard branch filter with real data.
- [x] Branch sales totals from paid cases.
- [x] Date-range filters for reports.
- [x] Printable/exportable report output.

## Final Sprint - Deployment and Hardening
- [ ] Production `.env` and database config review.
- [ ] HTTPS and trusted proxy settings.
- [ ] Backup/restore procedure.
- [ ] Basic audit logging (created_by, updated_by for sensitive records).
