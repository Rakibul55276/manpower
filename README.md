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
## Module availability

The invoice module is disabled by default with `INVOICING_ENABLED=false`.
Its menu is hidden and invoice requests return 404 while disabled. Existing
invoice data is preserved, and other modules keep their existing behavior.
For later invoice work, set `INVOICING_ENABLED=true` in `.env` and run
`php artisan config:clear` (or rebuild the configuration cache).

ZATCA has its own independent switch, `ZATCA_ENABLED=false`, which hides its menu and blocks its route. Set it to `true` and clear the configuration cache when ready.

## Safety shop inventory

The independent safety shop module is available at `/safety-shop` with
`SAFETY_SHOP_ENABLED=true`. Its switch, tables, routes, models, service, and
views are separate from invoicing, ZATCA, employees, attendance, and payroll.
Run `php artisan migrate` to install its six `safety_shop_*` tables.

1. Add categories, suppliers, and locations in the module's master directories.
2. Create products with unique SKUs, size/brand, unit, certification, current
   cost, selling price, and reorder level. Each size/variant uses its own SKU.
3. Receive opening stock or supplier deliveries into a location.
4. Post issues/sales, customer or unused returns, signed adjustments, or
   transfers. Quantity is in whole stock units; an issue needs a recipient,
   and every movement needs a reason. Negative balances are rejected.
5. Review stock by product/location, low-stock alerts, current-cost valuation,
   and the filtered movement ledger. Export stock or movement reports as CSV.

All active users can view this shared shop inventory. Admins and super admins
can maintain products/masters and post movements. Records are deactivated
instead of deleted, and posted movements are immutable. Correct stock through
a compensating movement referencing the original entry. Transfers update both
locations atomically. A unique request key prevents accidental duplicate posts.
Ledger balances follow posting order; document dates can be backdated but cannot
be in the future. Stock valuation uses the product's current unit cost.

This module records physical inventory; issues and sales do not create invoices,
post to accounting, or change payroll. It does not manage purchase orders, payment
accounts, serial numbers, or expiry batches. Disable it independently using
`SAFETY_SHOP_ENABLED=false` and clear/rebuild the configuration cache.

### Barcode sales

Products can have a unique barcode (preserving leading zeros) and a selling
price in SAR. Use a keyboard-style barcode scanner or type the code in Barcode
checkout and press Enter. Repeated scans increase quantity. Select a location,
scan products, review quantities, enter cash/card/bank payment received, and
complete the sale. The server validates current prices and available stock,
then creates a permanent receipt and reduces all stock in one transaction.
A rejected sale changes no stock. Receipts preserve the names and prices at sale
time and can be printed. Cash overpayment shows change; card/bank must match
the total. No payment provider is charged by this module.

Safety shop sales remain separate from invoicing and ZATCA. Product prices
are final checkout prices; this module does not calculate taxes or issue tax
invoices. For returned goods, post a stock return referencing the sale receipt;
refund payments are handled outside the inventory ledger.

### Feature directories

Each safety shop feature has its own directory under `app/Modules/SafetyShop`:
`Products`, `Categories`, `Suppliers`, `Locations`, `Stock`, `Sales`, and
`Reports`. Each owns its routes and controller; models/services stay with the
feature that owns them. `Shared` holds common master-record infrastructure.
Views use matching directories under `resources/views/safety-shop`. Barcode
checkout assets live under `public/js/safety-shop/sales` and
`public/css/safety-shop/sales`.

Dedicated directories are available at `/safety-shop/products`, `/categories`,
`/suppliers`, `/locations`, `/stock`, `/sales`, and `/reports` (all prefixed with
`/safety-shop`). Barcode checkout is at `/safety-shop/sales/checkout`.
