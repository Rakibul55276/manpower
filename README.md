# Manpower and Safety Shop Management System

Laravel 8 application for workforce, branch operations, timesheets, payroll, employee advances, private documents, Safety Shop inventory/sales, optional invoicing, and voucher-controlled SaaS access.

The current architecture uses one shared database with server-side company and branch isolation. SaaS vouchers control company access; they do not create a separate physical database per company.

## Requirements

- PHP 7.4-compatible runtime and extensions
- Composer
- MySQL/MariaDB
- Apache/XAMPP with the web root directed to `public`
- Write access to `storage` and `bootstrap/cache`

Laravel 8 and PHP 7.4 are retained for the existing XAMPP environment but are out of support. Upgrade before public production deployment.

## Installation

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create an empty database and configure `.env`:

```dotenv
APP_NAME=Manpower
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/hr/public
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manpower
DB_USERNAME=root
DB_PASSWORD=
```

Then run:

```powershell
php artisan migrate
php artisan manpower:install
php artisan config:clear
```

Open `http://localhost/hr/public/login`. On a fresh database the installer creates initial users with random passwords in private file `storage/app/setup-credentials.txt`. Securely transfer the credentials, then delete or protect that file.

## Feature configuration

```dotenv
SAAS_VOUCHER_ENABLED=true
SAAS_VOUCHER_GRACE_DAYS=7
SAFETY_SHOP_ENABLED=true
DOCUMENT_LIBRARY_ENABLED=true
INVOICING_ENABLED=false
ZATCA_ENABLED=false
PRIVATE_DOCUMENT_DISK=local
STOCK_MOVEMENT_DOCUMENT_DIR=safety-shop/stock-documents
```

After changing `.env`, run `php artisan config:clear` (or `config:cache` in production).

## Roles

| Capability | Superadmin | Company admin | Branch manager |
|---|---:|---:|---:|
| Configure the platform | Yes | No | No |
| Access companies | All | Assigned company | Assigned company |
| Access branches | All | All company branches | Assigned branch only |
| Manage company users | Yes | No | No |
| Issue/suspend SaaS access | Yes | No | No |
| Redeem company voucher | N/A | Yes | No |
| Full company analytics | Yes | Yes | No |
| Edit pending hours | Yes | Yes | Workflow-limited |
| Reopen approved hours | Yes | No | No |
| Delete eligible records | Yes | Pending hours only | No |

One admin belongs to one company and covers all its branches. One manager belongs to one branch; another branch requires another manager account. Superadmins have no company/branch assignment and can configure anything required by the business.

Direct URLs, validation, queries, downloads, and service transactions enforce these scopes. Submitted company/branch IDs are never authorization by themselves.

## First-run workflow

1. Sign in as superadmin.
2. Create a company with a unique uppercase company code and optional trial days.
3. Create its shop/work locations and branches.
4. Assign one admin to the company.
5. Assign a separate manager to each branch.
6. Create designations and employees.
7. If there is no trial, generate a voucher under **SaaS vouchers**.
8. Give the one-time code to the company admin.
9. The admin redeems it under **Subscription**.

Retained users from an earlier database cleanup have no company/branch assignment until the superadmin assigns them.

## Five-company setup example

The following example can be used on localhost or cPanel. Names are illustrative; replace them with the real client records.

| Company | Code | Suggested branches | Company admin | Branch managers |
|---|---|---|---|---|
| Alpha Industrial Services | `ALPHA` | Riyadh Main, Dammam Site | `alpha_admin` | `alpha_riyadh_mgr`, `alpha_dammam_mgr` |
| Beta Contracting Company | `BETA` | Jeddah Main, Yanbu Project | `beta_admin` | `beta_jeddah_mgr`, `beta_yanbu_mgr` |
| Gulf Safety Trading | `GULF` | Riyadh Shop, Khobar Warehouse | `gulf_admin` | `gulf_riyadh_mgr`, `gulf_khobar_mgr` |
| Horizon Manpower | `HORIZON` | Head Office, Jubail Camp | `horizon_admin` | `horizon_head_mgr`, `horizon_jubail_mgr` |
| Summit Operations | `SUMMIT` | Makkah Branch, Madinah Branch | `summit_admin` | `summit_makkah_mgr`, `summit_madinah_mgr` |

For these five companies, create at least five company-admin accounts and one manager account for every branch. Never reuse one manager account across branches. Each company sees only its own employees, products, locations, documents, transactions, and analytics.

### Configure each company

Repeat these steps for `ALPHA`, `BETA`, `GULF`, `HORIZON`, and `SUMMIT`:

1. Sign in as superadmin and open **Companies → Add company**.
2. Enter the company name, unique code, location, registration/contact details, and optional trial days.
3. Open **Branches** for the new company and create its branches.
4. Open **Users** and create one `admin` assigned to the company with no branch assignment.
5. Create one `manager` per branch and assign both the correct company and branch.
6. Add company-specific shop locations, categories/SKU prefixes, suppliers, and products.
7. Generate a voucher assigned to that company, unless it has an active trial.
8. Sign in as its company admin and redeem the voucher under **Subscription**.
9. Sign in once as every manager and confirm only the assigned branch is visible.

Recommended initial voucher plan:

| Company code | Duration | Voucher assignment |
|---|---:|---|
| `ALPHA` | 365 days | Alpha only |
| `BETA` | 365 days | Beta only |
| `GULF` | 365 days | Gulf only |
| `HORIZON` | 365 days | Horizon only |
| `SUMMIT` | 365 days | Summit only |

Generate separate vouchers. Do not share one voucher between companies. Copy each plaintext code immediately because it is never displayed again.

## Localhost/XAMPP deployment for five companies

### 1. Prepare XAMPP

Start Apache and MySQL. Place the project at:

```text
C:\xampp\htdocs\hr
```

Create database `manpower` through phpMyAdmin or MySQL. For local development only, the XAMPP root account may be used:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/hr/public
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manpower
DB_USERNAME=root
DB_PASSWORD=
SAAS_VOUCHER_ENABLED=true
SAAS_VOUCHER_GRACE_DAYS=7
```

### 2. Install and verify

```powershell
Set-Location C:\xampp\htdocs\hr
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan manpower:install
php artisan config:clear
php artisan manpower:check
```

Ensure these folders are writable by the Windows account running Apache:

```text
storage
bootstrap/cache
```

### 3. Create the five companies

Sign in with the generated superadmin credentials. Create the five companies from the table above, then create their branches and users. The database should contain:

- 5 company records
- At least 10 branches in this example
- 5 company administrators
- 10 branch managers in this example
- 5 separately assigned subscription vouchers

### 4. Local isolation test

For every company:

1. Add one uniquely named employee and Safety Shop product.
2. Sign in as another company's admin and search for both records.
3. Confirm neither record is shown.
4. Attempt a copied direct record URL and confirm it returns 403 or 404.
5. Sign in as a branch manager and confirm another branch is unavailable.
6. Confirm a voucher assigned to another company is rejected.

Do not continue to production if any isolation check fails.

## cPanel deployment for five companies

The same application and shared database serve all five companies. You do not need five installations or five databases for the current architecture.

### SQL installation files

Two data-free SQL packages are included:

- `database/saas-central.sql` — central company registry, platform administrators, vouchers, and redemption audit.
- `database/company-customer-template.sql` — customer application schema with Laravel migration history, but no users or business records.

Example localhost imports:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE manpower_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
C:\xampp\mysql\bin\mysql.exe -u root manpower_central < database\saas-central.sql
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE manpower_customer_alpha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
C:\xampp\mysql\bin\mysql.exe -u root manpower_customer_alpha < database\company-customer-template.sql
```

On cPanel, create the databases/users in **MySQL Databases**, then import the matching file with phpMyAdmin. Reuse the customer template for each customer database, changing only the target database and its restricted credentials. The current application runtime still uses shared-database isolation; switching live requests between these physical customer databases requires the tenant-connection resolver described by the central registry and must not be assumed from importing the files alone.

### 1. Create the cPanel database

In **cPanel → MySQL Databases**:

1. Create a database, for example `account_manpower`.
2. Create a dedicated database user, for example `account_manpower_user`.
3. Generate a long random password.
4. Assign the user to the database with only the privileges required by the application and migrations.
5. Do not use the cPanel account/root database identity in the application.

Remember that cPanel normally prefixes database and usernames with the hosting account name.

### 2. Upload the application

Preferred structure:

```text
/home/account/manpower-app/       Laravel application and private files
/home/account/public_html/        Public web root
```

Point the domain or subdomain document root directly to:

```text
/home/account/manpower-app/public
```

If the host does not allow changing the document root, place only the contents of Laravel's `public` directory in `public_html` and carefully update `index.php` paths to the private application directory. Never copy `.env`, `storage`, database backups, or the whole project into a publicly downloadable path.

### 3. Production environment

Create `/home/account/manpower-app/.env`:

```dotenv
APP_NAME="Manpower"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://manpower.example.com

LOG_CHANNEL=stack
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=account_manpower
DB_USERNAME=account_manpower_user
DB_PASSWORD=REPLACE_WITH_LONG_RANDOM_PASSWORD

CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

SAAS_VOUCHER_ENABLED=true
SAAS_VOUCHER_GRACE_DAYS=7
SAFETY_SHOP_ENABLED=true
DOCUMENT_LIBRARY_ENABLED=true
INVOICING_ENABLED=false
ZATCA_ENABLED=false
PRIVATE_DOCUMENT_DISK=local
STOCK_MOVEMENT_DOCUMENT_DIR=safety-shop/stock-documents
```

Generate `APP_KEY` on the server with `php artisan key:generate`. Never reuse the sample database password or publish the completed `.env`.

### 4. Install dependencies and migrate

Using cPanel Terminal or SSH:

```bash
cd /home/account/manpower-app
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan manpower:install
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan manpower:check
```

If cPanel provides multiple PHP versions, use the PHP 7.4-compatible binary configured for the domain. Confirm Composer and CLI PHP use the same extensions/version as the web application.

### 5. Permissions

Grant the web-server account write access to:

```text
storage
storage/app
storage/framework
storage/logs
bootstrap/cache
```

Typical hosting permissions are `755` for directories and `644` for files, but writable Laravel directories may require group write access depending on the provider. Avoid `777`.

### 6. Scheduler and backups

If scheduled Laravel tasks are added, configure one cPanel cron entry:

```cron
* * * * * /usr/local/bin/php /home/account/manpower-app/artisan schedule:run >> /dev/null 2>&1
```

Adjust the PHP path using cPanel's documented binary. Schedule database/private-file backups separately. The application command can create database backups:

```bash
php artisan manpower:backup --keep=14
```

Download or replicate backups outside the hosting account. A backup stored only on the same server is not sufficient disaster recovery.

### 7. Create and validate five tenants

Sign in as superadmin over HTTPS and create the five-company example. For each company:

- Create branches before managers.
- Assign exactly one company admin.
- Assign one manager per branch.
- Issue a company-specific voucher.
- Redeem as the company admin.
- Create a unique employee/product test record.
- Verify another company cannot search, open, edit, download, or report on it.

### 8. Production acceptance checklist

- HTTPS is valid and HTTP redirects to HTTPS.
- `APP_DEBUG=false` and error pages expose no stack trace.
- `.env`, logs, backups, and `storage/app` are not web-accessible.
- All migrations completed successfully.
- Superadmin can open SaaS voucher administration.
- All five company admins can sign in and redeem only their assigned voucher.
- Each manager sees exactly one branch.
- Cross-company direct URLs return 403/404.
- Private document links require authentication and correct scope.
- Stock transfers cannot cross companies.
- Database and private-file restore has been tested.
- Mail delivery is configured if the deployment later adds email notifications.

### Updating the cPanel installation

Back up first, enable maintenance mode, deploy reviewed files, then run:

```bash
php artisan down
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

If an update fails, preserve the error log and restore both code and database from the same backup point. Do not roll back only one side of a schema-changing release.

## SaaS vouchers

Configured in `config/saas.php` with `'saas_voucher_enabled' => true` and controlled by `SAAS_VOUCHER_ENABLED`.

| State | Access |
|---|---:|
| ACTIVE | Yes |
| TRIAL | Yes |
| GRACE | Yes, until grace expiry |
| EXPIRED | No |
| INACTIVE | No |
| SUSPENDED | No; redemption also blocked |

Vouchers may be company-specific or usable by any company, last 1–3,650 days, and have an optional redeem-by date. Plain codes are displayed once; only a SHA-256 hash and prefix are stored. Redemption uses a transaction and row locks, rejects replay and cross-company use, and records the old/new expiry, company, redeemer, and time. Valid remaining days are preserved when extending a subscription.

Company users without access are redirected to Subscription. Profile and logout remain reachable. Only the company admin can redeem; superadmins bypass the gate.

## Employees

Rental and own employees have separate directories. Records support identity/contact data, company, branch, designation, joining date, photo, CV details, previous experience, status, and private supporting PDFs.

Rental commercial fields are normal rental rate, overtime rate, PO/customer billing rate, internal company cost, and expected regular hours. Company cost is used for profitability; it is not automatically paid to the rental employee. Own employees use monthly salary components and an overtime rate.

Archiving preserves history. Referenced master records cannot be deleted.

## Timesheets and attendance

- Rental employees use Timesheets; own employees use Attendance.
- One entry per employee/date.
- `0.00` regular hours is allowed for absence.
- Hours cannot be negative or exceed 24 total per date.
- Extra hours are entered as overtime.
- Dates before joining and future dates are rejected.
- Payroll-locked months cannot be changed.
- Company admins can edit/delete pending entries, including normal hours.
- Approved entries are locked; only superadmin can reopen them.
- Bulk entry shows all dates on one page and accepts up to 120 dates atomically.
- Reports mark short-hour dates in red without creating negative values.

## Payroll and advances

```text
Rental regular pay = approved regular hours × saved regular rate
Rental overtime    = approved overtime hours × saved overtime rate
Rental net         = regular + overtime + allowances − deductions − advance repayments

Own employee net   = monthly salary + overtime + allowances − deductions − advance repayments
```

Money uses integer cents and hours use hundredths. Historical rate snapshots do not change when employee rates change. Payroll generation locks the employee/month. Paid payroll is immutable. “Paid” records status only; the system does not transfer money.

Advances are company/branch scoped and deducted through payroll repayment records.

## PDFs and private documents

Payslips stay on one page. Employee receipt and authorized approval blocks are aligned in the footer; the attestation seal sits with the approver and appears only after approval. CVs may append validated PDFs.

Employee files, stock receipts, and scans stay under `storage/app` and are served only through authorized routes. Never expose `storage/app` publicly. Stock-document location is centrally configurable.

## Safety Shop

Enable with `SAFETY_SHOP_ENABLED=true`.

### Catalog and masters

Products contain barcode, SKU, name, category, brand, size/variant, stock unit, safety standard, reorder level, cost, price, status, and notes. Searchable dropdowns and saved suggestions are company-scoped.

Superadmins configure a category SKU prefix. SKU suggestions use that prefix and the next company-scoped sequence. SKU and barcode are unique per company, so another company may reuse them.

Suppliers support optional company/contact, phone, email, VAT, CR, website, and address fields. Locations represent shops/warehouses and are company-scoped.

### Stock

Supported movements: receipt/opening, issue, return, adjustment, and transfer. Transfer shows source/destination only when selected; both must be distinct locations in the same company. Posting is atomic, negative stock is rejected, and request keys prevent duplicates.

Receipts can include supplier, reference, notes, and a private supporting document. Posted ledger rows are immutable; correct errors with a compensating movement referencing the original.

### Barcode sales and returns

Scan/type a barcode and press Enter; repeated scans increase quantity. Checkout transactionally validates company, location, product, price, payment, and stock. Failed multi-line sales fully roll back. Receipts preserve names/prices. Returns restore stock and cannot exceed sold/unreturned quantity.

Company admins receive full company Safety Shop analytics; managers do not. Metrics include sales, net revenue excluding VAT, gross profit/loss, refunds, inventory value, and operational totals.

## Optional modules

`INVOICING_ENABLED=false` hides and blocks invoicing without deleting data. `ZATCA_ENABLED=false` independently disables ZATCA. Enabling either does not certify statutory compliance; production tax workflows require separate configuration and validation.

## Commands

```powershell
php artisan manpower:install
php artisan manpower:demo
php artisan manpower:check
php artisan manpower:reset-password user@example.com --activate
php artisan manpower:backup --keep=14
php artisan migrate
php artisan test
composer check-platform-reqs
```

Demo data is for development only. Never import demo SQL over populated data.

## Backup and recovery

Back up both the database and `storage/app`. Test restoration regularly. A lost voucher plaintext cannot be recovered from its hash; issue a replacement voucher. Always back up before migrations or bulk deletion.

## Testing

Tests use in-memory SQLite and private fake storage. General suites disable the SaaS gate to test underlying modules independently; `SaasVoucherTest` enables it. Coverage includes voucher replay/cross-company rejection, branch isolation, authentication, hours, payroll, advances, PDFs, documents, inventory, sales rollback, returns, and analytics.

Focused commands:

```powershell
php artisan test --filter=SaasVoucherTest
php artisan test --filter=BranchIsolationTest
```

## Production security

- Set `APP_DEBUG=false` and use HTTPS.
- Use a restricted DB account, not root.
- Protect `.env`, `APP_KEY`, backups, and private uploads.
- Use strong unique passwords and secure cookies.
- Keep CSRF and authorization middleware enabled.
- Never log/store plaintext voucher codes.
- Preserve voucher redemption, payroll, stock, and audit history.
- Prefer deactivation/compensating entries over deleting financial records.

## Troubleshooting

- Configuration stale: `php artisan config:clear`
- Views stale: `php artisan view:clear`
- Missing column/table: `php artisan migrate`
- Redirected to Subscription: check assignment, company active status, subscription state/dates, and suspension
- 403: check role, company, branch, ownership, and workflow state
- Upload failure: check PHP limits, type/size, PDF encryption, private disk, path permissions, and free space

## License

GNU GPL v3 as recorded in `LICENSE`. Third-party dependencies retain their own licenses.
