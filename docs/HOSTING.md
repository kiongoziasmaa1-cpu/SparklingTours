# Hosting and release guide

The website, public routes, admin dashboard, booking workflows, and photos are the same on every host. Choose one production setup:

| Host | Runtime | Database | Deployment files |
|---|---|---|---|
| Render | Docker / FrankenPHP | PostgreSQL | Root `Dockerfile` and `Caddyfile` |
| Namecheap | PHP 8.4+ with cPanel/SSH or VPS | MySQL 8+ | Laravel `public` document root; no Docker required |

This Laravel application is not a static Vite site. Do not configure the project as a static-site deployment. The local `.env.example` uses SQLite only for local development; production must use a persistent database.

The dependency lockfile requires PHP **8.4.1+**. Do not bypass Composer platform checks to run PHP 8.2/8.3. Production also needs BCMath, GD, PDO and the PDO driver for the selected database (`pdo_pgsql` on Render or `pdo_mysql` on Namecheap). Use HTTPS, and configure PHP/web-server uploads for at least 10 MB (`upload_max_filesize=10M`, `post_max_size=14M`).

## Shared production configuration

Set these variables in the host's protected environment-variable settings, not in Git or a publicly served directory:

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

## Render

Create a Render **Web Service** from this repository. Select the `main` branch, Docker runtime, and root `Dockerfile`. The included Docker image installs PHP 8.4, PostgreSQL support, and Vite assets; it runs FrankenPHP without the low-port file capability and listens on Render's `PORT` (default `10000`). The root `Dockerfile` is specific to Render/container hosting; do not use it for Namecheap cPanel.

Create or select a Render PostgreSQL database in the same region and add its **internal** URL as `DATABASE_URL` in the web service environment. Also set:

```dotenv
DB_CONNECTION=pgsql
DATABASE_URL=<Render PostgreSQL internal URL>
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Remove any SQLite settings from the Render service or linked environment groups, especially `DB_CONNECTION=sqlite` and `DB_DATABASE=sparkling`. SQLite is only for local development; the Render image defaults to PostgreSQL and requires the database's `DATABASE_URL`.

Set the shared production configuration above using the service's actual `onrender.com` or custom hostname. Set the Render **Pre-Deploy Command** to:

```sh
php artisan migrate --force && php artisan db:seed --force
```

After pushing changes, use **Manual Deploy → Deploy latest commit**. The build log must not show `setcap CAP_NET_BIND_SERVICE=+eip`; runtime logs must show FrankenPHP starting without `Operation not permitted`. Check `https://your-service.onrender.com/up` and the public pages after deployment. Render's container filesystem is ephemeral: attach a persistent disk at `/app/public/images/uploads` for admin-uploaded photos, and ensure the service user can write there. Bundled photos under `public/images` are part of the image and do not need a disk.

If queued mail is enabled, configure a Render background worker for `php artisan queue:work database --sleep=3 --tries=4 --timeout=60`, and a scheduled task for `php artisan schedule:run`. Do not expect a web service to run permanent workers automatically.

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

Admin-uploaded images are stored under `public/images/uploads`. On Render, preserve them on a persistent disk or move them to object storage before relying on them across deploys. On Namecheap, include that directory in backups and do not overwrite/delete it during uploads. The checked-in photographs under `public/images` ship with the application.

After deployment, verify `/up`, `/`, `/about`, `/tours`, destination pages for Zanzibar and Mainland, `/transport`, `/contact`, `/privacy`, `/terms`, `/photo-credits`, and `/login`. Sign in with the administrator account you created, submit a test request, confirm email delivery, and check that all images load. Also complete a real Turnstile test on the production hostname. Deployment configuration cannot validate hosting credentials or external services until tested on the chosen host.

Back up the production database, uploaded photos, and the original `APP_KEY` securely. Test restoring a backup to a separate database before changing production data.
