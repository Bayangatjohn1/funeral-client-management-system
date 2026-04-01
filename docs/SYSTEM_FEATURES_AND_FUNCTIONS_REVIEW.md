# Funeral System Features and Functions Review

This document maps the requested system requirements to the current implementation in the Laravel application.

## Features of the System

1. Web-Based and Cloud-Hosted System
   Status: Partially met in code, deployment-dependent.
   Notes: The application is web-based and browser-accessible through Laravel routes and Blade views. The database layer already supports centralized remote database connections through environment-based configuration in `config/database.php`, including MySQL, MariaDB, PostgreSQL, SQL Server, and `DB_URL`-based deployments. Whether the system is actually cloud-hosted depends on the production hosting and database infrastructure, not source code alone.

2. Main Branch-Centered Encoding
   Status: Met.
   Notes: Staff intake flow is centered on `BR001`, with cross-branch reporting routed through main-branch-authorized staff and tagged separately using `entry_source`.

3. Branch Management
   Status: Met.
   Notes: Admin users can create, edit, activate, and monitor branches from the branch management module.

4. Client Information Management
   Status: Met.
   Notes: Client records are created, updated, validated, and linked to cases.

5. Deceased Information Management
   Status: Met.
   Notes: The system stores deceased records, operational details, and optional photos.

6. Service Package Management
   Status: Met.
   Notes: Admin users can manage package names, coffin types, pricing, inclusions, freebies, and promo pricing.

7. Billing and Payment Recording
   Status: Met.
   Notes: Billing is derived from the selected package, discounts are resolved automatically, and the system supports unpaid, partial, and fully paid cases.

8. Role-Based Access Control
   Status: Met.
   Notes: Owner, admin, and staff routes are protected with role-specific middleware and branch-scope checks.

9. Reporting and Monitoring
   Status: Met.
   Notes: Admin and owner dashboards provide case and sales monitoring, including branch-based views and exports.

10. Data Security and Ethical Handling
    Status: Met, strengthened.
    Notes: Sensitive records are protected by authentication, role checks, active-account enforcement, and branch scoping. The application now also sends baseline security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, and `Permissions-Policy`) and applies active-user protection to the profile area as well.

## Functions of the System

1. The system records client information before selecting a funeral service package.
   Status: Met.

2. The system records deceased information and links it to the corresponding client record.
   Status: Met.

3. The system assigns each case to a specific branch for proper monitoring and reporting.
   Status: Met.

4. The system allows staff to select and record the appropriate coffin-based service package.
   Status: Met.

5. The system automatically generates billing based on the selected service package.
   Status: Met.

6. The system records full or partial payments made by the client.
   Status: Met.

7. The system calculates and tracks the remaining balance if the client makes a partial payment.
   Status: Met.

8. The system updates the payment status as Unpaid, Partially Paid, or Fully Paid, and stores transaction records for retrieval and monitoring.
   Status: Met.

9. The system generates branch-based reports such as the total number of cases and total sales to support business monitoring and decision-making.
   Status: Met.

## Recent Additions from This Review

- Added response security headers through `App\Http\Middleware\SecurityHeaders`.
- Applied `active` middleware to profile routes so inactive accounts cannot access profile pages.
- Documented the compliance mapping and clarified that cloud hosting is deployment-managed rather than enforced by application code.
