# Manpower Laravel project

See `README.md` for the complete workforce workflows, accounts, demo records, and verification instructions.

This project uses Laravel 8.83.29 with XAMPP's PHP 7.4.23.

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open http://localhost:8080/manpower/public/.

The local `.env` uses the MySQL database `manpower`, username `root`, and an empty password. Update these values if your XAMPP credentials change. The application key has been generated, the database created, and the starter migrations applied.

Run these commands from `C:\xampp\htdocs\manpower`:

```powershell
php artisan migrate
php artisan test
```

Tests use an isolated SQLite database in memory.

For an alternative local server, run `php artisan serve` and open http://127.0.0.1:8000.

Laravel 8 and PHP 7.4 are no longer supported. Upgrade PHP and Laravel before using this project in production. For deployment, the web server document root must point to the `public` directory.
