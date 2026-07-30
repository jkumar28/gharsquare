# GharSquare database

This directory is the source of truth for creating and evolving the database.

## Files

- `schema.sql` — baseline structure for a clean database. It contains tables,
  indexes, constraints, and auto-increment settings, but no application data.
- `seed_reference_data.sql` — non-sensitive reference data required by forms and
  filters: geography, listing types, property types, and amenities.
- `migrations/` — forward-only schema changes created after this baseline.

User accounts, passwords, OTPs, properties, enquiries, saved properties, activity
logs, uploaded media, and mail records must never be committed as seed data.

## Create a local database

Run these commands from the project root:

```powershell
D:\xampp\mysql\bin\mysql.exe -uroot -e "CREATE DATABASE gharsquare CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
D:\xampp\mysql\bin\mysql.exe -uroot gharsquare < database\schema.sql
D:\xampp\mysql\bin\mysql.exe -uroot gharsquare < database\seed_reference_data.sql
```

The first command is only needed when the database does not already exist.
Credentials should be adjusted for the target environment.

## Add a migration

1. Create a forward-only SQL file in `database/migrations`.
2. Name it `YYYYMMDDHHMM_short_description.sql`.
3. Make the migration safe to run against the current production schema.
4. Include a comment explaining the purpose and any irreversible operation.
5. Test it against a disposable copy of the database before deployment.
6. Apply migrations in filename order and record deployed filenames in the
   release notes until an automated migration runner is introduced.

Do not edit an already-deployed migration. Add a new migration that corrects it.

## Refreshing the baseline

The baseline represents the schema at the start of migration history. Routine
changes belong in `migrations/`; do not regenerate `schema.sql` after every
change. A new baseline should only be cut intentionally during a coordinated
release, after all existing migrations have been incorporated and verified.
