# FULAFIA NELFUND Refund Portal

A lightweight PHP + MySQL + Tailwind CSS portal for students to submit
NELFUND balance evidence and track their refund status, with an admin
panel to review submissions and update statuses.

## File Structure

```
fulafia-nelfund/
├── config.php              # DB connection + shared helpers (EDIT THIS FIRST)
├── schema.sql               # Run once to create the database
├── index.php                 # Public landing page
├── signup.php                 # Student registration
├── login.php                   # Student login
├── logout.php                   # Shared logout (student + admin)
├── forgot_password.php           # Request reset link
├── reset_password.php             # Set new password via token
├── dashboard.php                   # Student dashboard (status + upload, 5MB/PNG/JPG validated)
├── includes/
│   ├── header.php
│   └── footer.php
├── assets/
│   └── logo.png              # FULAFIA logo (already extracted for you)
├── uploads/                   # Student-submitted screenshots (auto-created)
│   └── .htaccess               # Blocks script execution in this folder
└── admin/
    ├── login.php               # Admin login (logs the login event)
    ├── create_admin.php         # ONE-TIME script to create the first admin (becomes SuperAdmin)
    ├── dashboard.php              # Master student table + status control + CSV export button
    ├── update_status.php           # Handles status update POST (logs the change, emails the student)
    ├── export_csv.php               # Streams Approved Refunds as a downloadable CSV
    ├── manage_admins.php            # SuperAdmin-only: create/delete admin accounts
    └── activity_log.php               # Activity log / audit trail view
```

## Running Locally on KSWEB (Android)

This build ships pre-configured for KSWEB with `127.0.0.1` / `root` /
empty password / database `fulafia_nelfund` already set in
`config.php` — you shouldn't need to edit database credentials at all.
Just:

1. **Copy the project folder** into KSWEB's web root (usually
   `/sdcard/ksweb/htdocs/`), keeping the folder name `fulafia-nelfund`
   so it matches `BASE_URL` in `config.php`. If you rename the folder
   or KSWEB gives you a different port, update `BASE_URL` to match
   exactly what you see in the browser (e.g.
   `http://127.0.0.1:8080/fulafia-nelfund`).
2. **Start MySQL and Apache/Nginx** from the KSWEB app.
3. **Import `schema.sql`** via KSWEB's bundled phpMyAdmin
   (`http://127.0.0.1/phpmyadmin` or whatever KSWEB shows you) —
   create the `fulafia_nelfund` database there first if importing
   doesn't create it automatically, since `root` with an empty
   password already has full access by default on KSWEB.
4. Visit `http://127.0.0.1/fulafia-nelfund/admin/create_admin.php`
   once to create your first (SuperAdmin) account, then delete that
   file.
5. **Heads up on email:** `forgot_password.php` and the status-change
   notification both use PHP's `mail()`. KSWEB has no mail server
   configured out of the box, so those emails will silently fail to
   send in this local environment — the rest of the app (including the
   password reset token/link itself) still works fine; you'd just need
   to grab the reset link from the database's `reset_token` column
   manually while testing, or wire up SMTP later for production.

## Deploying to production (cPanel)

When you're ready to move this off KSWEB onto your real university
host:

1. **Upload the files** to your hosting account (e.g.
   `public_html/nelfund-portal`).
2. **Create the database** in cPanel → MySQL Databases, along with a
   database user with full privileges on it (cPanel usually prefixes
   both, e.g. `cpaneluser_nelfund`).
3. **Import `schema.sql`** via phpMyAdmin, or from SSH:
   ```
   mysql -u your_db_username -p your_db_name < schema.sql
   ```
4. **Update `config.php`** — this is the only file that needs it,
   since every other page reads the database connection through it:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_username');
   define('DB_PASS', 'your_db_password');

   define('BASE_URL', 'https://yourdomain.edu.ng/nelfund-portal'); // no trailing slash
   define('MAIL_FROM_ADDRESS', 'no-reply@yourdomain.edu.ng');
   ```
5. **Set folder permissions** so PHP can write to `uploads/`:
   ```
   chmod 755 uploads
   ```
6. **Create your first admin account** by visiting
   `https://yourdomain.edu.ng/nelfund-portal/admin/create_admin.php`
   once, then **delete `admin/create_admin.php` from the server**
   immediately afterward.
7. **Test it end to end** — student signup/login/upload, admin
   login/status-change/CSV export, and password reset.



There are two admin roles:

- **SuperAdmin** — everything a regular Admin can do, plus access to
  **Manage Admins** (create new Admin accounts, delete existing ones).
- **Admin** — reviews students and updates refund status; can view the
  **Activity Log** but cannot manage other admin accounts.

The very first account created via `admin/create_admin.php` is
automatically made a SuperAdmin. Every account created afterwards
through the **Manage Admins** panel defaults to a regular Admin.
SuperAdmin accounts can't be deleted from the panel (including your
own) — that has to be done directly in the database, so the privilege
stays deliberate.

## About the logo

The FULAFIA crest lives at the project root as `logo.png`. Every page
references it with a plain, static `<img>` tag and a relative path
(`logo.png` from root pages, `../logo.png` from `/admin/` pages) — no
JavaScript, `onerror` handler, or opacity toggle touches it, so it
can't be dynamically hidden after the page loads. To swap in a
higher-resolution version later, just replace `logo.png` in the
project root (keep the same filename).

## Email delivery notes

`forgot_password.php` uses PHP's built-in `mail()` function, which on
most cPanel hosts sends through the server's local mail service without
extra setup. If reset emails aren't arriving:

- Check your cPanel **Email → Track Delivery** log for the message.
- Many hosts require the `From:` address (`MAIL_FROM_ADDRESS` in
  `config.php`) to be a real mailbox on your domain, or emails get
  flagged as spam.
- If your host requires SMTP authentication (common on Gmail-routed
  or security-hardened hosts), replace the body of `sendResetEmail()`
  in `forgot_password.php` with a [PHPMailer](https://github.com/PHPMailer/PHPMailer)
  SMTP call instead — the rest of the flow (token generation, the
  `reset_password.php?token=...` link, expiry) needs no changes.

## Security notes already built in

- Passwords are hashed with `password_hash()` / verified with
  `password_verify()` — never stored in plain text.
- All SQL uses PDO prepared statements.
- Every form includes a CSRF token checked on submit.
- Uploaded files are validated with `getimagesize()` (real image
  content, not just file extension) and capped at 3MB.
- The `uploads/` folder has a `.htaccess` blocking PHP execution, so
  even if someone renamed a malicious file to `.jpg`, it could never
  run as a script.
- Password reset tokens are random 64-character strings that expire
  after 1 hour and are single-use (cleared after a successful reset).
- Every admin login, student status change, and admin account
  create/delete is written to `audit_logs`, viewable at
  `admin/activity_log.php`.
- `admin/manage_admins.php` is gated by `requireSuperAdminLogin()` in
  `config.php`, so regular Admins are shown an "Access Denied" page
  rather than the account list.
- `admin/create_admin.php` locks itself after the first admin account
  is created — visiting it again just shows "already set up" instead
  of silently minting more accounts if you forget to delete it.
- Session cookies are set `HttpOnly`, `SameSite=Lax`, and `Secure`
  (once you're on HTTPS) in `config.php`, so JavaScript can never read
  the session cookie and it won't ride along on cross-site requests.
- Every file upload is checked three ways before being accepted:
  declared extension, real image header via `getimagesize()`, and an
  independent MIME check via `finfo` — so a script renamed to `.jpg`
  fails at least two of the three. Capped at 5MB.
- Registration explicitly rejects a duplicate matric number or bank
  account number (`signup.php`), with a database-level UNIQUE
  constraint as a second line of defense if two signups ever race.

## New in this update

- **Student email notifications** — `admin/update_status.php` emails
  the student automatically whenever their status changes, via the
  shared `sendStatusUpdateEmail()` helper in `config.php`. Subject to
  the same `mail()` deliverability notes above.
- **CSV export** — the "Export Approved Refunds" button on
  `admin/dashboard.php` downloads a clean CSV (Full Name, Matric
  Number, Account Number) of every Approved student, ready to hand to
  the bursary/bank. The export itself is written to the activity log.
- **Duplicate registration guard** — `signup.php` now checks matric
  number and account number together and shows: *"This Matriculation
  Number or Account Number is already registered."*
- **Stricter uploads** — 5MB cap (up from 3MB) plus the three-layer
  extension/`getimagesize()`/`finfo` check described above.

## Upgrading an existing install

If you already deployed the portal before this update, don't re-run
the full `schema.sql` (it would try to recreate tables you already
have). Instead, run the commented **MIGRATION FOR EXISTING INSTALLS**
block at the bottom of `schema.sql` — it adds the new `full_name` and
`role` columns to `admins`, creates the `audit_logs` table, and
promotes your existing admin to SuperAdmin so you're not locked out of
the new Manage Admins panel. Then re-upload the changed PHP files
(`config.php`, `includes/header.php`, `admin/login.php`,
`admin/update_status.php`, `admin/create_admin.php`, plus the two new
files `admin/manage_admins.php` and `admin/activity_log.php`).

## Things to customize before going live

- Replace the placeholder DB credentials and `BASE_URL` in `config.php`.
- Set `display_errors` to `0` in `config.php` once you've confirmed
  everything works (currently on for easier debugging).
- Consider adding rate-limiting to `login.php` and `forgot_password.php`
  if this will be publicly exposed, to slow down brute-force attempts.
