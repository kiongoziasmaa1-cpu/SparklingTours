# Maelekezo ya kuanza — Sparkling Tours and Safaris

Project hii ina website, dashboard, booking za tour, accommodation preferences, booking za transport na invoices. Muonekano wake umetengenezwa kwa Blade na Bootstrap 5; backend ni Laravel/PHP na database ni MySQL.

## 1. Hakikisha una mahitaji haya

- PHP **8.4.1 au toleo jipya la 8.x** lenye extensions zilizo kwenye README. XAMPP yenye PHP 8.2 haitoshi kwa lockfile ya project hii. Unaweza kutumia mazingira yenye PHP 8.4 iliyosasishwa, kwa mfano Laravel Herd pamoja na MySQL.
- MySQL 8.0 au zaidi.
- Composer 2.
- Node/npm utazihitaji tu ukibadilisha CSS au JavaScript na kutaka kuzijenga upya. Faili zilizojengwa tayari zimo ndani ya ZIP.

Kwenye PowerShell, hakikisha `php -v` inaonyesha PHP 8.4.1 au zaidi kabla ya kuanza. Ikiwa inachagua PHP 8.2 ya XAMPP, weka folder la PHP 8.4 la Herd (`%USERPROFILE%\.config\herd\bin\php84`) kabla ya `C:\xampp\php` kwenye `PATH`, kisha fungua terminal mpya. Tumia `Get-Command php` kuona PHP itakayotumika.

Staff huingia kwa email na password kisha hufikishwa moja kwa moja kwenye admin dashboard. Jaribio la login limewekewa rate limit.

## 2. Fungua ZIP na tengeneza database

Extract ZIP. Fungua folder `sparkling-tours`; ndani yake utaona `artisan`, `composer.json`, `app` na `public`.

Kwenye MySQL/phpMyAdmin tengeneza database inayoitwa `sparkling_tours`. Tumia `utf8mb4_unicode_ci`. Kwa hosting, tengeneza pia user maalumu wa database na mpe ruhusa za database hiyo.

## 3. Andaa `.env`

Nakili `.env.example` na liite nakala yake `.env`. Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Linux/macOS:

```bash
cp .env.example .env
```

Fungua `.env` na uweke taarifa zako:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sparkling_tours
DB_USERNAME=database_user_wako
DB_PASSWORD=password_ya_database
```

Tumia jina na password halisi ulizotengeneza. `.env` halitakiwi kuwekwa kwenye sehemu inayofunguliwa moja kwa moja na browser.

## 4. Weka dependencies na schema

Fungua terminal ndani ya folder yenye `artisan`, kisha endesha amri hizi moja baada ya nyingine:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
```

Vendor na assets zimejumuishwa ndani ya ZIP; `composer install` itahakiki dependencies kulingana na lockfile. `migrate --seed` inaweka tables na huduma/bei za mfano. Haina account ya admin yenye password ya kawaida.

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
