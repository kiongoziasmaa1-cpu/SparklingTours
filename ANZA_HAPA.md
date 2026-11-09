# Maelekezo ya kuanza — Sparkling Tours and Safaris

Project hii ina website, dashboard, booking za tour, accommodation preferences, booking za transport na invoices. Muonekano wake umetengenezwa kwa Blade na Bootstrap 5; backend ni Laravel/PHP. Database iliyosanidiwa ni MySQL 8+ kwa local na Namecheap.

## 1. Hakikisha una mahitaji haya

- PHP **8.4.1 au toleo jipya la 8.x** lenye extensions zilizo kwenye README. XAMPP yenye PHP 8.2 haitoshi kwa lockfile ya project hii. Unaweza kutumia mazingira yenye PHP 8.4 iliyosasishwa, kwa mfano Laravel Herd.
- MySQL 8.0+ na PHP extension `pdo_mysql`. PHPUnit hutumia SQLite ya muda kwa tests.
- Composer 2.
- Node/npm utazihitaji tu ukibadilisha CSS au JavaScript na kutaka kuzijenga upya. Faili zilizojengwa tayari zimo ndani ya ZIP.

Kwenye PowerShell, hakikisha `php -v` inaonyesha PHP 8.4.1 au zaidi kabla ya kuanza. Ikiwa inachagua PHP 8.2 ya XAMPP, weka folder la PHP 8.4 la Herd (`%USERPROFILE%\.config\herd\bin\php84`) kabla ya `C:\xampp\php` kwenye `PATH`, kisha fungua terminal mpya. Tumia `Get-Command php` kuona PHP itakayotumika.

Staff huingia kwa email na password kisha hufikishwa moja kwa moja kwenye admin dashboard. Jaribio la login limewekewa rate limit.

## 2. Fungua project

Extract ZIP. Fungua folder `sparkling-tours`; ndani yake utaona `artisan`, `composer.json`, `app` na `public`.

Tengeneza MySQL database na user. Kwenye Namecheap cPanel, tumia majina halisi yenye prefix ya akaunti. Weka taarifa hizo kwenye `.env` kabla ya kuendesha migrations.

## 3. Andaa `.env`

Nakili `.env.example` na liite nakala yake `.env`. Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Linux/macOS:

```bash
cp .env.example .env
```

Fungua `.env` na weka taarifa za MySQL:

```dotenv
APP_ENV=local
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanel_database_name
DB_USERNAME=cpanel_database_user
DB_PASSWORD=your_cpanel_database_password
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

`.env` halitakiwi kuwekwa kwenye sehemu inayofunguliwa moja kwa moja na browser wala kupakiwa GitHub.

### Kuchagua hosting

- **Namecheap:** tumia cPanel/SSH au VPS inayotoa PHP 8.4.1+, `pdo_mysql`, MySQL 8+, na uwezo wa kuelekeza document root kwenye `public`. Fuata [mwongozo wa hosting](docs/HOSTING.md#namecheap).
- Credentials halisi za MySQL hutolewa na cPanel. Usipakie `.env` kwenye GitHub au ndani ya public web folder.

## 4. Weka dependencies, schema na data za website

Fungua terminal ndani ya folder yenye `artisan`, kisha endesha amri hizi moja baada ya nyingine:

```bash
composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
```

Vendor na assets zimejumuishwa ndani ya ZIP; `composer install` itahakiki dependencies kulingana na lockfile. Migrations huunda tables; seeder huweka kurasa za mwanzo, picha za gallery na catalogue. Seeder inaweza kurudiwa bila kufuta mipangilio iliyopo. Haina account ya admin yenye password ya kawaida.

Ikiwa unaamua kuimport `database/mysql-demo.sql` kupitia phpMyAdmin badala yake, import kwenye database mpya tupu; usiunganishe njia mbili bila kuelewa hali ya migrations. Njia inayopendekezwa ni amri ya migrations hapo juu.

Unaweza pia kuweka requests tatu za mfano kwa majaribio ya local tu (hazitumi email):

```bash
php artisan db:seed --class=DemoBookingSeeder
```

## 5. Tengeneza administrator wako

```bash
php artisan staff:create email-yako@example.com
```

Andika jina, password yenye angalau herufi 12 na uthibitisho wa password. Password haitachapishwa kwenye terminal.

## 6. Fungua website

```bash
php artisan serve
```

Website: `http://localhost:8000`

Staff login: `http://localhost:8000/login`

Ingia kwa email na password ya account uliyotengeneza; utafikishwa moja kwa moja kwenye admin dashboard.

Kwenye dashboard, tumia **Website settings** kubadilisha au kupakia picha ya mwanzo (hero), picha ya ukurasa wa nyumbani, logo na picha ya akiba. Tumia **Photos & galleries** kupakia picha mpya, kubadilisha picha iliyopo, kusimamia picha za gallery na kuchagua picha za ukurasa wa Photo credits. Kwenye fomu za tours, destinations, routes na vehicles unaweza pia kupakia au kubadilisha picha husika. Picha iliyobadilishwa kwenye library itasasishwa kila mahali ilipotumika.

## 7. Weka taarifa za biashara

Kwenye Website settings badilisha jina, logo, contacts, hero, payment instructions na invoice terms. Kama unataka kubadilisha TZS/USD, weka exchange rate uliyoichagua na tarehe yake. Hakuna rate ya kubuni iliyowekwa.

Kwenye catalogue badilisha tours, bei, maelezo na picha. Kila rekodi ina kitufe cha **Delete** kwenye orodha na fomu ya kuhariri. Rekodi iliyotumika kwenye booking haiwezi kufutwa ili kuhifadhi historia; fares zisizotumika hufutwa pamoja na route au vehicle yake. Kwenye Photos & galleries chagua picha za kuonekana kwenye galleries. Policies za Privacy/Terms ni rasimu zinazohitaji taarifa halisi za biashara yako.

## 8. Andaa usafiri

1. Ongeza aina ya chombo kwenye Vehicle types.
2. Ongeza route ikiwa draft.
3. Ongeza fare inayounganisha route na aina ya chombo; weka bei, capacity, luggage na pricing basis.
4. Chapisha route baada ya kuweka angalau fare moja active.

Bei ya kurudi ni optional; ikiwekwa tupu, aina hiyo haina return booking. Reverse route inaweza kuongezwa tofauti ikiwa masharti/bei ni tofauti.

## 9. Tumia booking na invoice

Mteja akituma ombi, litaonekana Bookings. Admin anaweza kulipanga kwa guide. Guide atakagua accommodation preferences, atafute hotel na aweke option aliyohakiki pamoja na reservation/hold reference ikiwa supplier ametoa.

Chagua hotel iliyokubaliwa, andaa invoice, hakiki bei, weka deposit au full payment, save draft, kisha issue invoice na tuma private link. Bei za hotel ambazo bado hazijapatikana zitaendelea kuwa Pending quotation.

Baada ya kuhakiki fedha zilizopokelewa, rekodi payment au refund. Balance na due now zitajihesabu. Receipt PDF inapatikana kupitia private invoice. Kubonyeza booking au invoice link hakuthibitishi malipo yenyewe.

## 10. Washa email na ulinzi wa production

Kwa majaribio ya local, email zinaandikwa kwenye `storage/logs/laravel.log`. Ili kutuma email halisi, weka SMTP sahihi kwenye `.env` na endesha queue worker:

```bash
php artisan queue:work --tries=4 --timeout=60
```

Kwa live website weka `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, secure cookies, live Cloudflare Turnstile keys na hostname halisi. `CAPTCHA_ENABLED=false` hairuhusiwi production. Maelekezo kamili yako kwenye `docs/HOSTING.md`.

Ukipata tatizo la setup, endesha:

```bash
php artisan app:doctor
```

File la `.env`, database backups na `APP_KEY` vihifadhiwe kwa usalama. Project hii haijapandishwa kwenye hosting; ZIP ni ya kuinstall kwenye mazingira yako.
