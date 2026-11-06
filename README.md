# Sparkling Tours and Safaris

A Laravel tourism application for Tanzania Mainland and Zanzibar. It includes a responsive Blade/Bootstrap website, custom and package tour requests, guide-arranged accommodation, independent transport routes, staff administration, private PDF invoices, verified manual payments and receipts.

Start with **[ANZA_HAPA.md](ANZA_HAPA.md)** for Kiswahili installation instructions, **[docs/HOSTING.md](docs/HOSTING.md)** for deployment, and **[docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md)** for daily use.

## Requirements

- 64-bit PHP **8.4.1 or later in the 8.x series**, preferably the current patched 8.4/8.5 release. The supplied lockfile targets Symfony 8.1 and is not compatible with PHP 8.2 or 8.3.
- Extensions: BCMath, Ctype, cURL, DOM, Fileinfo, Filter, GD, Hash, Mbstring, OpenSSL, PCRE, PDO, PDO PostgreSQL, Session, Tokenizer, XML and ZIP. PDO SQLite is used by the default automated test suite.
- PostgreSQL, a database and a dedicated database user.
- Composer 2. Node 22.12+ / npm is needed only when rebuilding assets. Built CSS/JS and installed PHP vendor dependencies are included in the release ZIP.

Laravel is locked in `composer.lock`; Bootstrap and Vite are locked in `package-lock.json`. Install from these locks, rather than updating dependencies during initial setup.

On Windows, check `php -v` before running the commands below. If PowerShell selects XAMPP's PHP 8.2, put Herd's PHP 8.4 directory (`%USERPROFILE%\.config\herd\bin\php84`) before `C:\xampp\php` in your PowerShell `PATH`, then open a new terminal. Use `Get-Command php` to confirm which executable PowerShell selected.

Staff sign in with their email and password and are taken directly to the admin dashboard. Login attempts are rate-limited.

## Quick local start

1. Extract the ZIP. Open a terminal in the `sparkling-tours` folder (the folder containing `artisan`).
2. Copy `.env.example` to `.env` and configure the local PostgreSQL database credentials.
3. Run the following commands, one at a time:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan staff:create your-email@example.com
php artisan serve
```

Open `http://localhost:8000`. Staff login is `http://localhost:8000/login`. The administrator password is prompted securely by `staff:create`; there is no built-in permanent administrator account or default password.

Start the database-backed notification worker in a second terminal:

```bash
php artisan queue:work --tries=4 --timeout=60
```

Run `php artisan schedule:work` in a third terminal for local outbox recovery, or configure the production scheduler described in the hosting guide. Database sessions, cache and jobs are created by the migrations.

`.env.example` is explicitly for local development: CAPTCHA verification is disabled in `APP_ENV=local`, and mail uses the log transport. Production refuses this CAPTCHA bypass. Real email delivery and live Turnstile require your own configuration.

For optional local-only sample bookings (package + stay request, custom quote, and independent transfer), run `php artisan db:seed --class=DemoBookingSeeder`. No notifications are sent for fictitious requests.

## Main workflows

- **Tour:** browse a package or create a plan; choose dates, travelers and services; provide contacts; optionally request help with accommodation at the end; review; complete verification; submit. A request is saved with price snapshots and an acknowledgement reference.
- **Accommodation:** a guide reviews preferences, checks hotels manually, records checked options/actual supplier references and selects the customer-agreed option. A selected option can be included in a new invoice draft. It is never shown as a zero-priced confirmed hotel.
- **Transport:** the customer chooses a published route and one of that route's configured active vehicle fares. Transport works independently of tours and accommodation. Capacity, luggage, pickup time and optional return fares are validated server-side.
- **Finance:** prepare and save a draft, resolve unpriced lines, issue the invoice, send a private expiring link, verify and record deposit/final payments, and download receipts. Payment status and reservation status remain separate.

## Build assets / run checks

```bash
npm ci --ignore-scripts
npm run build
php artisan test
php artisan app:doctor
composer audit
```

`php artisan test` uses an in-memory SQLite database by default. Earlier release verification was performed against MySQL; see `docs/VERIFICATION.md` for exact results and verification limits. Verify the PostgreSQL deployment on your hosting environment before going live. Browser-based mobile/device testing and real external mail/CAPTCHA checks must be completed on the final hosting domain.

## Included files

| Path | Purpose |
|---|---|
| `app/Http/Controllers`, `Requests`, `Middleware` | Public/admin workflows, validation and access checks |
| `app/Services`, `app/Support` | Pricing, submissions, invoices, outbox, TOTP and money arithmetic |
| `database/migrations`, `seeders` | Relational schema and clearly illustrative catalogue data |
| `database/mysql-demo.sql` | Optional clean MySQL schema/catalogue import, when included; migrations remain the primary setup route |
| `resources/views`, `css`, `js` | Blade screens, custom Bootstrap styling and progressive interactions |
| `public/build`, `public/images` | Built local assets and licensed photographs |
| `tests` | Meaningful workflow, finance and security tests |
| `docs` | Hosting, operation, architecture, requirements and verification |

No live business contacts, bank accounts, exchange rates or legal credentials are invented. Configure those settings and replace illustrative policies, itineraries and prices before accepting real requests. No online payment gateway, live hotel inventory, live vehicle dispatch or external supplier booking API is included; hotel sourcing and payment verification are deliberate staff workflows.

Code is provided under the MIT licence in `LICENSE`. Laravel and other dependencies retain their own licences. Photography has separate attribution/licensing in `public/images/ATTRIBUTION.md` and the website's Photo Credits page. Share-alike photo derivative terms apply to the relevant photographs, not a blanket licence change to the application code.
