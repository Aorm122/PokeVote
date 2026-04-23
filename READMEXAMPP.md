1. Install XAMPP and open the XAMPP Control Panel.
2. Start MySQL in XAMPP. Apache is optional for this repo if you plan to use PHP’s built-in server.
3. Create a database named `pokevote` or import the provided 'pokevote.sql'
4. Copy .env.example to .env.
5. Edit .env and set these values:
   - `DB_DRIVER=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_NAME=pokevote`
   - `DB_USER=root`
   - `DB_PASSWORD=` if your XAMPP root account has no password
6. Open PowerShell in PokeVote.
7. Run the migrations with `php scripts/migrate.php`.
8. Seed the default categories with `php scripts/seed_categories.php`.
9. Import Pokémon with `php scripts/import_pokemon.php`. This can take a while.
10. Start the app with `php -S localhost:8000 -t public`.
11. Open http://localhost:8000/ in your browser.

Or run these scripts
& C:\xampp\php\php.exe scripts\migrate.php
& C:\xampp\php\php.exe scripts\seed_categories.php
& C:\xampp\php\php.exe scripts\import_pokemon.php
