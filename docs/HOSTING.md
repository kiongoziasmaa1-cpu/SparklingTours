# Hosting and release guide

## Supported target

Use an HTTPS PHP/MySQL host with PHP 8.4.1+ (a current patched 8.4/8.5 release), required PHP extensions, a MySQL 8.0 database, writable application storage, cron and either a queue supervisor or cron-based PHP execution. A VPS with Nginx/Apache or a compatible cPanel plan works. This project is a Laravel application; it is not a Cloudflare Workers/Sites deployment.

Allow image uploads up to 10 MB by setting PHP `upload_max_filesize` to at least `10M` and `post_max_size` to at least `14M` for the web PHP handler. Restart PHP-FPM/Apache after changing these values. Also allow request bodies of at least 14 MB in the web server or proxy. If the admin reports that an image "did not reach the application," check these limits in the web PHP handler and proxy; command-line PHP settings may be different.

The SRS proposed Laravel 13 / PHP 8.3+ as a baseline subject to host/dependency verification. The resolved release uses Laravel 13.36.0 and Symfony 8.1.9, so this lockfile requires **PHP 8.4.1+**. Do not ignore Composer platform checks to run it on PHP 8.2/8.3.

## Prepare the application

1. Upload/extract the project outside the web document root, for example `/home/account/sparkling-tours`.
2. Point the domain/subdomain document root to `/home/account/sparkling-tours/public`. Keep `app`, `.env`, `vendor`, `storage`, backups and the project ZIP outside the publicly served directory. Retain Laravel's `public/.htaccess` when using Apache.
3. Create a dedicated MySQL database user. Create `.env` from the example and fill the production values below before running Composer/artisan in production.
4. Keep `APP_KEY` stable after installation. Generate it only for a brand-new installation, not on every deployment; it protects encrypted staff secrets, sessions and outbox data.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-real-domain.example
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_dedicated_user
DB_PASSWORD=your_secret
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=bookings@your-real-domain.example
MAIL_FROM_NAME="Your business name"
CAPTCHA_ENABLED=true
TURNSTILE_SITE_KEY=your_live_site_key
TURNSTILE_SECRET_KEY=your_live_secret_key
TURNSTILE_HOSTNAMES=your-real-domain.example,www.your-real-domain.example
```

Use the SMTP provider's documented scheme/port combination; some providers use `smtps` on port 465. The `.example` values above are placeholders, not working credentials.

Create a Managed Cloudflare Turnstile widget for the exact public domain(s). Add the allowed hostnames in both Cloudflare and the environment. The application sends tokens to Siteverify and checks `success`, hostname and action (`tour_booking`, `transport_booking`, `contact`). Never deploy test keys, a disabled verifier or a frontend-only CAPTCHA. The application refuses the development bypass in production.

## Install and launch

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan staff:create operator@your-real-domain.example
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan app:doctor
```

Run `key:generate` only for this first installation. For later releases, keep the existing `.env`, key and database and run the remaining applicable migration/cache commands. Do not use `migrate:fresh` against customer data.

The release includes `public/build` so Node is not required on the production server. If frontend source changes, build with `npm ci --ignore-scripts` and `npm run build` before uploading the new assets. Production does not require a permanent Node process or Vite development server.

Ensure the PHP runtime user can write to `storage` and `bootstrap/cache`, and only administrators can read `.env`. Typical Unix directory permissions are 0755/0775 depending on the host's ownership model; avoid world-writable 0777 permissions. Uploaded photographs are re-encoded into `public/images/uploads`; preserve that directory on updates. Private PDF temp files stay under `storage/app/private`.

Use the host's HTTPS redirect. If a reverse proxy terminates TLS, configure Laravel trusted proxies for the actual proxy addresses, not arbitrary client-supplied forwarded headers. Verify secure request detection, secure cookies and generated HTTPS links.

## Queues and scheduler

On a VPS, supervise:

```bash
php /home/account/sparkling-tours/artisan queue:work database --sleep=3 --tries=4 --timeout=60
```

Use the same PHP 8.4+ binary as the website. Restart workers after each deployment with `php artisan queue:restart`.

Scheduler cron (once per minute):

```cron
* * * * * cd /home/account/sparkling-tours && /path/to/php artisan schedule:run >> /home/account/cron.log 2>&1
```

The scheduler retries pending outbox dispatch and recovers stale delivery claims. On shared hosting without supervised workers, add a separate per-minute cron using a host-supported lock to prevent overlapping workers:

```cron
* * * * * cd /home/account/sparkling-tours && flock -n /home/account/sparkling-worker.lock /path/to/php artisan queue:work database --stop-when-empty --tries=4 --timeout=60 >> /home/account/worker.log 2>&1
```

Check the host's availability of `flock` or use its equivalent single-instance scheduler. If the plan cannot run cron/queue jobs, choose a plan that can; the notification workflow depends on this. A `sent` outbox entry means the configured mail transport accepted the message, not proof that the recipient read or received it. SMTP recovery is at least once; a crash after SMTP handoff may cause a retry of a message.

## cPanel details

- Use MultiPHP Manager or the plan's equivalent to select a supported PHP version and required extensions.
- Assign the subdomain/addon domain's document root to the application's `public` folder. If the plan cannot change the main-domain root, use a subdomain or ask the host for a secure Laravel deployment layout.
- Supply the cPanel-prefixed database/user names exactly in `.env`.
- Run commands in Terminal/SSH if available. The included vendor/build files support hosts without server-side build tools, but a secure way to run initial migrations and the prompted admin command is still required.
- Do not add a public web installer, permanent default password or browser route that runs arbitrary artisan commands.

## Before accepting customers

Configure real business identity, contacts, administrative email, payment instructions, actual routes/fare capacities, hotel arrangements and policies. Replace or unpublish illustrative catalogue data, then disable the demo banner. Send a real test email to an address you control, complete live Turnstile checks on the actual domain, run a booking/invoice/deposit test under your operating procedure, and check mobile/desktop browsers. Test backup restoration and expiry of private invoice links.

## Backups and restoration

Back up MySQL, uploaded images, private documents (if retained), the deployed source/lockfiles, and the environment key/configuration securely outside the web root. Protect secrets separately and retain the `APP_KEY` with restricted access; it is needed to decrypt existing 2FA/outbox/session data.

Use your host's backup system or `mysqldump --single-transaction --routines --triggers --no-tablespaces --set-gtid-purged=OFF` with a protected credentials file. Put `--defaults-extra-file=/secure/path/mysql-backup.cnf` first after the command name; do not pass database passwords in command-line arguments. Save exports to a private location and encrypt off-site copies as appropriate.

To restore, place the site in maintenance mode, restore into a **separate** test database first, restore uploads and the correct environment key, run `php artisan migrate --force`, clear/rebuild caches, and verify counts, staff 2FA, a booking, an invoice and a private download. Switch the live configuration only after verification. Keep the previous working backup until the new site is verified. The release verification report records a clean MySQL export/import check; your hosting backups must also be tested on your actual infrastructure.
