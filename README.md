# ZPPSU Lost & Found Management

Native PHP 8+, MySQL/MariaDB on XAMPP, PDO/mysqli prepared statements, responsive maroon/gold CSS and vanilla JavaScript.

## Run locally

Start Apache and MySQL. PHP requires PDO MySQL, mysqli, mbstring and fileinfo. Import `schema.sql` through phpMyAdmin or the MySQL client, then run:

```powershell
C:\xampp\php\php.exe database\seed_demo_accounts.php
```

The local installation is already upgraded and demo accounts are installed. Open `http://localhost/Initial-lost-found-project/`; guests land on the login page. Seed reruns never reset an existing password. The pre-change backup is `database/before_gateway_upgrade.sql`, denied over HTTP and excluded from git.

Connection defaults: localhost, database `lost_found_db`, root, empty password. Override `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` in the Apache environment. Existing records remain approved; all new reports enter Pending. Importing `schema.sql` again preserves current review decisions.

## Demo credentials

| Role | Email | Password |
|---|---|---|
| Administrator | admin@zppsu.edu.ph | password123 |
| Student | student@zppsu.edu.ph | password123 |

These local testing credentials are displayed on the login page as requested. Before public hosting, change or disable these accounts and remove the displayed credentials. No password is stored as plaintext in the accounts table.

Students may self-register with a unique email and Student ID. Registration always creates a student even if a request supplies an administrator role. An existing administrator can promote a registered user through Manage users, with confirmation of their current administrator password.

Legacy sample reporters are separate from login accounts. Matching an old email does not grant ownership of their reports. The sample Gmail addresses are illustrative and unverified; no mail is sent by this application.

## Navigation and student portal

The top-right profile menu shows the signed-in name and role, My Profile, My Reports, My Claims and POST-only Logout. Administrators also see Admin Dashboard. The dropdown supports keyboard navigation, Escape and closing when clicking outside.

Students can browse approved items, filter by keyword/category/status, report lost/found items, submit ownership evidence and track review decisions. All pages require login. Profile contact updates and password changes require the current password. Previously published contact details are preserved when changing an account email.

Reports are attributed to the signed-in account and are hidden until administrator approval. Owners can preview their own pending/rejected/archived reports; other students cannot. Claims use the signed-in identity, prevent duplicate pending requests, reject self-claims and do not automatically change item status.

## Administrator portal

- Pending report approval/rejection and tracking of active, claimed, rejected and archived items.
- Item editing for title, description, category, location, date, photo and contact details. Claim status cannot be altered through the editor.
- Claim approval/rejection with private proof and downloadable supporting files. Approval atomically marks the item claimed and rejects competing pending claims. An administrator cannot approve their own claim.
- Archiving retains history, hides reports from the browse feed and rejects pending requests. Permanent deletion is not exposed.
- User search, contact/name/Student ID edits, role changes, activation and deactivation. Changes invalidate the target account's sessions. Administrators cannot disable or demote themselves; at least one active administrator must remain.

## Security and uploads

Passwords use `password_hash` / `password_verify`; sign-in regenerates the session ID. Sessions expire after 30 minutes of inactivity or 8 hours total. Password changes, role changes and deactivation revoke existing sessions. Roles and active status are read from the database on every request. Logout destroys the session, expires its cookie and redirects to login. Protected pages use no-store caching.

State-changing forms use CSRF tokens. Persistent login/registration/report/claim limits and honeypots reduce abuse. User content is escaped in HTML, and request values are bound in PDO or mysqli statements. Login, registration, profile and management workflows use PDO.

Report images accept real JPG/PNG/WebP files up to 5 MB and 8000 pixels per side. Uploaded names are randomized; Apache denies execution. Online sample photos require internet access. Claims accept up to three JPG/PNG/WebP/PDF verification files, at most 5 MB each, stored in `database/verification/`. Direct HTTP access is denied. Only authenticated administrators can download through `attachment.php`, with forced-download, nosniff and no-store headers.

For three maximum-size attachments, configure PHP `upload_max_filesize=5M`, `post_max_size=20M` and `max_file_uploads>=3`, then restart Apache. No global server configuration was changed. Email verification, password reset and email delivery are not configured.

## Integration test

```powershell
python tests\gateway_flow.py
```

Run from the project directory against the local development server. The test checks the gateway, both demo roles, registration privilege boundaries, report/claim review, item editing and archiving, private histories, password/session invalidation and user management. It creates uniquely named test records and removes them afterward. Defaults can be overridden with `PHP_BINARY` and `LOST_FOUND_TEST_URL`.


## Focused administrator workspace

The Admin Dashboard now has a collapsible sidebar with Dashboard, Manage Items, Pending Approvals, Claims, Users and Settings. Its browser-local compact preference does not alter account permissions. Four KPI cards show active items, pending approvals, pending claims and resolved/claimed items.

Dashboard previews show five records; dedicated queues use 15-row pagination. Item and user search, status filters, truncated fields and row action menus keep tables compact. Review modals show the complete item or private claim evidence. Account edits use a modal and still require the administrator password. Archive actions use a confirmation dialog; histories remain intact.

`admin_detail.php` is administrator-only and returns escaped modal templates. `admin_action.php` preserves the same transactional role/CSRF/ownership checks and adds JSON responses for modal saves. Successful saves refresh only workspace content and counters. Validation errors stay in the modal for correction. The existing full-page account editor remains available as a fallback. No database migration is needed for this dashboard update.


### Admin sample activity

Run `C:\xampp\php\php.exe database\seed_admin_samples.php` from the project directory to add four sample student accounts, four pending reports, one archived report, one rejected report, four pending claims, one approved claim and one rejected claim. Sample student passwords are `password123`. The script preserves existing data, account passwords and subsequent review decisions when re-run. Two pending reports and one pending claim belong to the existing demo student account, so My Reports/My Claims also have sample activity.


### Shared navigation update

Administrator pages now use the same Home, Contact and About Us navigation as students, with a full-width workspace and no sidebar. Admin Dashboard, Manage Users and Settings remain available in the profile dropdown. KPI links open the item, approval and claim management views. Sidebar collapse controls and browser preferences are no longer used.
