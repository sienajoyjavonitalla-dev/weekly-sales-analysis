# Beginner's Guide

This guide explains how to run and use the Weekly Sales Analysis app on your local computer.

## 1. Requirements

Make sure these are installed and working:

- PHP 8.2 or newer
- Composer
- Node.js and npm
- MySQL
- Laragon or another local PHP server

Check versions:

```powershell
php -v
composer --version
node -v
npm -v
```

## 2. Project Setup

Open a terminal in the project folder:

```powershell
cd C:\laragon\www\weekly-sales-analysis
```

Install PHP dependencies:

```powershell
composer install
```

Install frontend dependencies:

```powershell
npm install
```

Copy the environment file if `.env` does not exist:

```powershell
copy .env.example .env
```

Generate the Laravel app key:

```powershell
php artisan key:generate
```

## 3. Configure the Database

Create a MySQL database:

```sql
CREATE DATABASE weekly_sales_analysis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

In `.env`, confirm these values:

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=weekly_sales_analysis
DB_USERNAME=root
DB_PASSWORD=
```

If your MySQL password is different, update `DB_PASSWORD`.

## 4. Create Tables And Default User

Run migrations and seeders:

```powershell
php artisan migrate --seed
```

Default login:

```text
Email: sjavonitalla@wagnermeters.com
Password: password
```

If you need to recreate only the user:

```powershell
php artisan db:seed --class=UserSeeder
```

## 5. Run The App

Start Laravel:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Open a second terminal and start Vite:

```powershell
npm run dev
```

Open the app:

```text
http://localhost:8000
```

## 6. Login

Use the default account:

```text
Email: sjavonitalla@wagnermeters.com
Password: password
```

After login, you will see the workflow screens.

## 7. Upload Screen

The upload screen lets you choose the six weekly Excel files:

- Sales Analysis
- Income Statement
- Total Sales Report
- Weekly Meter Report
- Open Orders
- PTD Orders

Important: file choosing is available, but browser upload/import saving is not fully connected yet. The backend parser and validation command already exist, but the final upload endpoint is still pending.

## 8. Validate Workbooks From Terminal

Use this command to validate the sample files:

```powershell
php artisan weekly-analysis:validate-workbooks --sales-analysis="C:\Users\WINDOWS\Documents\wagner\WEEKLY ANALYSIS project\04-17-2026 Sales Analysis (1).Xlsx" --income-statement="C:\Users\WINDOWS\Documents\wagner\WEEKLY ANALYSIS project\04-17-2026 Income Statement.xls.xlsx" --total-sales-report="C:\Users\WINDOWS\Documents\wagner\WEEKLY ANALYSIS project\Total Sales Report 2026.xlsx" --weekly-meter-report="C:\Users\WINDOWS\Documents\wagner\WEEKLY ANALYSIS project\Weekly Meter Report 2026.xlsx" --open-orders="C:\Users\WINDOWS\Documents\wagner\WEEKLY ANALYSIS project\04-17-2026 Open Orders.Xlsx" --ptd-orders="C:\Users\WINDOWS\Documents\wagner\WEEKLY ANALYSIS project\04-17-2026 PTD Orders.Xlsx"
```

## 9. Workflow Screens

### Mapping Rules

Use this screen to view and create rules that classify Sales Analysis rows.

Examples:

- Match `item_id` starting with `880-R`
- Match `customer_name` containing `AMAZON`
- Match `description` containing `FIELD SERVICE`

### Review Rows

Use this screen to review unmatched Sales Analysis rows after classification.

You need a valid `import_batches.id` to use this screen.

### Reconcile

Use this screen to:

- Add marketplace fees
- Run reconciliation
- Compare Sales Analysis totals against Income Statement totals
- Review PTD/Open Orders report metrics

You need imported rows before this screen shows meaningful numbers.

### Exports

Use this screen to generate and download Excel reports.

Generated files are stored privately in:

```text
storage/app/private/generated-reports/{batch_id}/
```

## 10. Useful Commands

Clear Laravel cache:

```powershell
php artisan optimize:clear
```

Run tests:

```powershell
php artisan test
```

Build frontend:

```powershell
npm run build
```

Run frontend lint:

```powershell
npm run lint
```

Reset local database:

```powershell
php artisan migrate:fresh --seed
```

## 11. Current Status

Ready:

- Login page
- Database tables
- Workbook validation command
- Mapping rules
- Classification logic
- Reconciliation logic
- Export generation logic
- React workflow screens

Still pending:

- Full browser upload endpoint
- Saving selected workbook files from the browser
- Creating `sales_rows`, `order_rows`, and `income_statement_lines` directly from browser uploads

## 12. Recommended Next Step

The next major feature should be the upload/import endpoint. Once that is done, the app can support a complete browser-based weekly workflow from file upload to report export.
