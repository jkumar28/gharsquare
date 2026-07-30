# Database migrations

Place forward-only SQL migrations in this directory.

Filename format:

```text
YYYYMMDDHHMM_short_description.sql
```

Example:

```text
202607301430_add_property_featured_flag.sql
```

Each file should begin with a short purpose comment. Prefer changes that are
safe with existing data, explicitly name indexes and constraints, and avoid
destructive operations unless a verified backup and deployment plan exist.

Check and apply migrations from the project root:

```powershell
D:\xampp\php\php.exe database\migrate.php --status
D:\xampp\php\php.exe database\migrate.php
```

Applied migration files are checksum-protected and must not be edited.
