# GharSquare

GharSquare is a PHP 8.2 and MySQL/MariaDB property marketplace with a public
website, owner listing workflow, enquiry management, and an admin panel.

## Requirements

- PHP 8.2 with PDO MySQL, fileinfo, and mbstring
- MySQL 8 or MariaDB 10.4+
- Apache with `mod_rewrite`
- Composer

## Local setup

1. Install PHP dependencies:

```powershell
composer install
```

2. Create the database and import its baseline:

```powershell
D:\xampp\mysql\bin\mysql.exe -uroot -e "CREATE DATABASE gharsquare CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
D:\xampp\mysql\bin\mysql.exe -uroot gharsquare < database\schema.sql
D:\xampp\mysql\bin\mysql.exe -uroot gharsquare < database\seed_reference_data.sql
```

3. Apply migrations:

```powershell
D:\xampp\php\php.exe database\migrate.php
```

4. Copy any required examples from `config/private/*.example.php` to the same
filename without `.example`, then enter local secrets. Private files are ignored
by Git.

The default local database connection is `127.0.0.1`, database `gharsquare`,
user `root`, and an empty password. Override it through environment variables
or `config/private/database.php` when needed.

## Configuration

Environment variables take precedence over private PHP configuration. Private
configuration takes precedence over safe application defaults.

Supported application variables:

- `APP_ENV` (`local` or `live`)
- `APP_NAME`
- `APP_URL`
- `APP_TIMEZONE`
- `APP_DB_TIMEZONE_OFFSET`
- `CONTACT_EMAIL`
- `SESSION_COOKIE_SECURE`

Supported database variables:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Legacy environment-specific names `LOCAL_DB_*` and `LIVE_DB_*` are also
  accepted.

Supported mail variables:

- `MAIL_TRANSPORT`
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- `ADMIN_ENQUIRY_EMAIL`

Maps uses `GOOGLE_MAPS_API_KEY`.

Never commit real credentials. For production, configure secrets through the
hosting environment or ignored files under `config/private`.

## Useful checks

```powershell
D:\xampp\php\php.exe database\migrate.php --status
D:\xampp\php\php.exe tests\smoke-location-integrity.php
D:\xampp\php\php.exe tests\smoke-owner-dashboard.php
D:\xampp\php\php.exe tests\smoke-owner-dashboard.php leads
D:\xampp\php\php.exe tests\smoke-admin-enquiries.php
```

The public site is served from `/website`; `/admin` contains the administrative
application.
