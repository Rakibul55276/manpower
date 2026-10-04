# Manpower management system

A Laravel 8 application for XAMPP PHP 7.4, with separate rental manpower and own-employee workflows.

## Start locally

1. Start Apache and MySQL in XAMPP.
2. Open http://localhost:8080/manpower/public/login.
3. Sign in with username `admin` or `manager`. The initial passwords are in the private `storage/app/setup-credentials.txt` file. Passwords are hashed in MySQL.

The database is `manpower`, using the local XAMPP root account. Connection details are in `.env`. Apache is configured on port 8080.

For a fresh checkout:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
# Create the manpower MySQL database before running migrations.
php artisan migrate
php artisan manpower:install
# Optional: add the requested demonstration records.
php artisan manpower:demo
```

The installer generates random initial passwords on a fresh database and does not overwrite existing accounts. Demo seeding is idempotent and adds exactly 100 fictional employees: 80 rental and 20 own employees. It includes five demo companies, sample timesheets/attendance, previous-month salary history, placeholder portraits, and ten supporting PDFs. Employee and company names appear without demo prefixes. Sample salary records and supporting certificates retain their fictional-content notes. Demo paid statuses represent fictional records, not actual payments. The initial Manager receives three demo companies plus the original company assignment.

## Import the SQL demo

`database/manpower_demo.sql` includes the schema, migration history, two disabled initial accounts, company assignments, and the 100 demo employees with hours and payroll. Import it into an **empty** MySQL/MariaDB database through phpMyAdmin or the MySQL client, then point `.env` to that database. Do not import it over your existing installation.

The SQL intentionally contains no live credential hashes, sessions, or activity logs. After import:

```powershell
php artisan key:generate
php artisan manpower:demo
php artisan manpower:reset-password admin@manpower.local --activate
php artisan manpower:reset-password manager@manpower.local --activate
```

The password commands prompt privately for a new password and confirmation. The demo command recreates private placeholder photos and supporting PDFs without duplicating database records. Imported accounts cannot sign in until activated. On an existing installation, the same interactive password command can be used for account recovery.

## Roles and company access

| Capability | Super Admin | Manager |
|---|---|---|
| View companies | All | Assigned companies |
| Maintain employees, photos, CV attachments, and hours | All | Assigned companies |
| Manage designations | Yes | Yes |
| Manage companies and account assignments | Yes | No |
| Approve, reject, or reopen hours | Yes | No |
| View CVs, payslips, and salary CSV exports | All | Assigned companies |
| Generate, adjust, void draft payroll, record payment | Yes | No |
| View activity history | Yes | No |

One manager can have multiple companies. Company boundaries are enforced on server-side queries and individual record routes, including private photos and PDF downloads. Disabled accounts are signed out; password changes invalidate other authenticated sessions.

## Employee records

Rental and own employees have separate directories. Both require a photo, name, unique 10-digit Iqama, unique passport number, phone, company, designation, blood group, and joining date. Previous experience supports up to five text entries, each up to 2,000 characters, with add/remove controls.

Photos accept JPG, PNG, or WebP up to 2 MB. Supporting documents accept an unencrypted PDF up to 10 MB / 30 pages. PDFs are checked at upload. Unsupported compression can be resolved by printing the document to a new PDF and uploading that copy. The generated CV includes the employee photo, contact information, designation, and previous experience; supporting PDF pages are appended in their original dimensions. Identity document numbers and blood group stay in the internal profile.

Archiving sets an employee inactive and preserves history. Reactivate through Edit profile. Inactive companies and designations are hidden from new selections. Records referenced by employees cannot be deleted.

## Rental hours and payroll

1. Add a rental employee and set their regular hourly rate and overtime multiplier.
2. Submit daily hours under Rental timesheets. Use **Date range** to select an employee and start/end dates, then generate 10 regular hours per day excluding Fridays. Optionally enter a total-hours target; dates fill in order with a shorter final day. Add/remove dates and edit regular/overtime hours in the preview before submitting. A target must match the edited rows, or you may clear it. The same feature is available in Own employee attendance, with monthly salaries remaining separate. Each submission supports up to 120 dates and saves all rows together; duplicate dates, existing entries, dates before joining, future dates, and payroll-locked months reject the entire batch.
3. The Super Admin approves or rejects the entry. Rejected entries can be corrected and resubmitted; approved entries can be reopened by the Super Admin before payroll generation.
4. Generate a draft salary for the employee and month after all pending entries are reviewed.
5. Adjust allowances or deductions, download a payslip, and record payment.

`Regular pay = approved regular hours × the hourly rate saved on each entry`.

`Overtime pay = approved overtime hours × saved hourly rate × saved overtime multiplier`.

`Net salary = regular pay + overtime pay + allowances − deductions`.

Amounts are calculated using integer cents; hours and multipliers use hundredths. Pay is rounded per daily entry. Updating an employee's rates does not change existing hour entries. Hours are decimal: 8.50 means 8 hours 30 minutes. Total daily hours must be greater than zero and no more than 24. Future dates, dates before joining, and duplicate employee/day entries are rejected.

## Own employees and monthly salary

Own employees have a separate directory, attendance register, and monthly salary register. Set their fixed monthly salary, an explicit overtime base hourly rate, and multiplier.

`Net salary = full fixed monthly base + approved overtime pay + allowances − deductions`.

Regular attendance is tracked but does not automatically prorate monthly base salary. Joining mid-month, absences, or other agreed adjustments must be entered as an allowance or deduction, with a note. The full monthly base can be generated with no attendance entries, but pending entries must be reviewed first. Rental and own payroll cannot be mixed through the UI or server endpoints.

Salary generation locks the employee/month against additional or edited hours. Void a draft to unlock the month, add/correct hours, and regenerate. Paid payslips are immutable and cannot be voided. Mark paid records a payment status; the system does not transfer money. Payroll registers open the latest available salary month by default and support month/company/status filters. CSV exports include the selected month and workforce within the user's company scope.

## PDFs and records

Download employee CVs from Employee profile. Download or print payslips from a salary record. Uploaded photos/documents and credentials are private under `storage/app`, never in the public storage disk. The root `.htaccess` routes XAMPP requests through `public`; production document roots should point directly to `public`.

## Verification

```powershell
php artisan test
composer check-platform-reqs
```

Tests use a separate SQLite database in memory and private fake storage. Coverage includes authentication, role/company boundaries, multi-company assignments, employee validation, experience limits, timesheet review and uniqueness, exact payroll amounts, rate snapshots, monthly salary separation, payroll locks, immutable paid records, PDF merging, pagination, and idempotent demo seeding. PDF QA fixtures are written only to ignored `tmp/pdfs`.

## Deployment notes

Laravel 8 and PHP 7.4 are retained to match the existing XAMPP installation. Both are out of support; upgrade them before public deployment. For deployment, use HTTPS, unique account passwords, a restricted database account, `APP_DEBUG=false`, and backups of the database and private employee files. No public registration, email password-reset service, bank integration, statutory payroll deductions, or automatic absence/proration rules are configured.

## License

The repository's existing GNU GPL v3 license is preserved in `LICENSE`. Third-party packages retain their respective licenses.
