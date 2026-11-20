# Laravel Toolkit

Utilities for Laravel applications that need to create and restore MySQL database backups.

## Requirements

- PHP 8.3 or later
- Laravel 12 or 13
- The `mysqldump` and `mysql` executables available on the system `PATH`

## Installation

Install the package with Composer:

```bash
composer require bestuniverse/laravel-toolkit
```

Laravel discovers the service provider automatically. Publish the configuration when you need to change the backup disk or directory:

```bash
php artisan vendor:publish --tag=laravel-toolkit-config
```

This creates `config/laravel-toolkit.php`:

```php
return [
    'disk' => 'local',
    'directory' => 'mysql-backup',
];
```

The configured disk must be defined in your application's `config/filesystems.php`.

## Commands

### Create a backup

Create a MySQL backup using the application's `mysql` database connection:

```bash
php artisan db:backup
```

Backup files are written to the configured disk and directory. The filename starts with `APP_ENV`. For example, `APP_ENV=production` on the local disk produces `storage/app/mysql-backup/production_2026_09_01_120000_full.sql`.

### Import a backup

Import a specific SQL file from the configured backup directory:

```bash
php artisan db:import production_2026_09_01_120000_full.sql
```

When no filename is supplied, the command interactively asks which `.sql` file to import:

```bash
php artisan db:import
```

Database connection values are taken from the application's `mysql` connection. You can override them for an individual import:

```bash
php artisan db:import backup.sql --user=root --pass=secret --db=example
```

Imports are blocked in the `production` environment unless you explicitly confirm them:

```bash
php artisan db:import backup.sql --force
```

> **Warning:** Importing a SQL backup can overwrite or delete existing database data. Verify the selected file and target database before running the command.

## Testing

```bash
composer test
```

## License

Laravel Toolkit is open-sourced software licensed under the [MIT license](LICENSE).
