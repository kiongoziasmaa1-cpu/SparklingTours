# Hosting and release guide

The website, public routes, admin dashboard, booking workflows, and photos are unchanged. This guide configures the application for Namecheap with MySQL:

| Host | Runtime | Database | Deployment files |
|---|---|---|---|
| Namecheap | PHP 8.4+ with cPanel/SSH or VPS | MySQL 8+ | Laravel `public` document root; no Docker required |

This Laravel application is not a static Vite site. Do not configure the project as a static-site deployment. The application is configured to use MySQL. PHPUnit tests use in-memory SQLite and do not change the application's configured database.

The dependency lockfile requires PHP **8.4.1+**. Do not bypass Composer platform checks to run PHP 8.2/8.3. Production also needs BCMath, GD, PDO, and `pdo_mysql`. Use HTTPS, and configure PHP/web-server uploads for at least 10 MB (`upload_max_filesize=10M`, `post_max_size=14M`).

## Shared production configuration

Set these variables in the host's protected environment-variable settings, not in Git or a publicly served directory. `APP_URL` must be the public origin only (scheme and hostname, with an optional port), not a URL containing a path:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-real-domain.example
APP_KEY=base64:your-existing-generated-key
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CAPTCHA_ENABLED=true
TURNSTILE_SITE_KEY=your-live-site-key
TURNSTILE_SECRET_KEY=your-live-secret-key
TURNSTILE_HOSTNAMES=your-real-domain.example
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=bookings@your-real-domain.example
MAIL_FROM_NAME="Your business name"
```

Generate `APP_KEY` once for a new environment with `php artisan key:generate --show`, then save it in the hosting dashboard. Keep it unchanged across deployments and backups. Never use development/test Turnstile keys in production. Add every public domain to both the Turnstile widget and `TURNSTILE_HOSTNAMES`. Production intentionally rejects missing CAPTCHA credentials, disabled verification, test keys, and `APP_DEBUG=true`.

The application seeder uses create-if-missing behavior to install baseline regions, gallery media, About/Privacy/Terms pages, homepage settings and sample catalogue. It does not overwrite existing settings or republish routes administrators have hidden. On a new deployment, migrate and seed the selected production database. **Never run `migrate:fresh` against a live database.** Existing data remains on the original database; point the deployment at that same database to retain it.

Create the first administrator securely using `php artisan staff:create your-email@example.com`. The command prompts for a password and does not accept a password as a command argument. No default administrator is installed.

## Namecheap

Use Namecheap cPanel/SSH or a VPS plan that provides the required PHP **8.4.1+**, extensions including `pdo_mysql`, MySQL 8+, HTTPS, writable storage, and a way to set the site's document root to the project's `public` directory. Confirm those capabilities in the specific plan before purchase; shared hosting plans differ. If the cPanel plan cannot point the domain to `public`, use a supported VPS or ask Namecheap support for a secure Laravel document-root setup. Do not move `.env`, `app`, or `vendor` into a publicly served directory.

Create a MySQL database and dedicated user in cPanel. Upload the project outside the web root, set the domain document root to `<project>/public`, and configure the protected environment:

```dotenv
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanel_database_name
DB_USERNAME=cpanel_database_user
DB_PASSWORD=database_password
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Use the exact database and username shown by cPanel; they are often prefixed with the cPanel account name. Add the shared production configuration and real Turnstile/mail values. From SSH or cPanel Terminal, run `composer install --no-dev --optimize-autoloader`, then `php artisan migrate --force` and `php artisan db:seed --force`. Run `php artisan config:cache` after the environment is complete. Keep `storage` and `bootstrap/cache` writable by the PHP user. If the selected Namecheap plan cannot run Composer/Artisan, prepare dependencies with a compatible PHP 8.4 build environment, upload them, and confirm the host's platform requirements before launch.

The standard Laravel `public/.htaccess` is included for Apache. Do not replace it with the Render `Caddyfile`. Set up a cron job for `php artisan schedule:run` and a supervised queue worker if using database queues; if the plan cannot run workers, configure a persistent worker through a supported VPS process manager rather than assuming email jobs will execute.

## Preserve data and verify the site

Admin-uploaded images are stored under `public/images/uploads`. Include that directory in backups and do not overwrite/delete it during uploads. The checked-in photographs under `public/images` ship with the application.

After deployment, verify `/up`, `/`, `/about`, `/tours`, destination pages for Zanzibar and Mainland, `/transport`, `/contact`, `/privacy`, `/terms`, `/photo-credits`, and `/login`. Sign in with the administrator account you created, submit a test request, confirm email delivery, and check that all images load. Also complete a real Turnstile test on the production hostname. Deployment configuration cannot validate hosting credentials or external services until tested on the chosen host.

Back up the production database, uploaded photos, and the original `APP_KEY` securely. Test restoring a backup to a separate database before changing production data.
