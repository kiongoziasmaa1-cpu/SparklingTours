# Verification report

Verified on 15 October 2026 against the delivered source and lockfiles.

| Check | Result |
|---|---|
| SQLite automated suite | **31 passed; 250 assertions; exit 0** |
| MySQL 8.0.46 automated suite | **31 passed; 250 assertions; exit 0**; driver asserted as MySQL in every feature test |
| Release installation smoke test | Production-only vendor dependencies booted; fresh schema/seed, prompted admin creation/password hash, HTTP homepage 200 and cache commands passed |
| Fresh MySQL schema | All four migrations applied successfully |
| MySQL export/import | Clean catalogue/schema dumped and restored into a separate database; region, catalogue, route/fare, settings, preference and migration counts matched |
| Production asset build | `npm run build` passed |
| Composer validation | `composer.json` and lockfile valid |
| Composer security audit | 0 reported advisories; no abandoned packages reported |
| npm security audit | 0 reported vulnerabilities |
| Laravel route/config cache | Creation and clearing both passed |
| Blade screens | Public and admin routes/forms rendered through HTTP tests |
| Invoice / receipt PDF | Generated using Dompdf and visually inspected after rasterization; one-page sample layouts had no clipping/overlap |
| Local image references | Rendered homepage references resolved to local files; 7 licensed photos with credits are bundled |

## Versions checked

- Laravel: v13.36.0
- PHP test runtime: 8.4.1 (64-bit)
- Dompdf: v3.1.6
- Bootstrap: 5.3.8
- Vite: 8.3.4
- Node build runtime: 24.19.0

## Meaningful workflow/security coverage

The suite covers public navigation and routes; independent transport; new admin-configured route/type/fare booking; per-person/per-vehicle/return calculations; capacity and compatibility rejection; custom/package pricing and included-service deduplication; pending prices; optional/conditional hotel preferences; accommodation before/after tour dates; review/edit state preservation; CAPTCHA failure recovery; idempotent submission; price-change review; immutable snapshots; guide assignment and separate finance permissions; hotel selection; unresolved accommodation blocking invoice issue; deposits, partial/final payments and bounded refunds; wrong-currency/overpayment rejection; immutable invoices; expiring/revoked/scoped private links and receipt access; mail/database failure retention; forged/expired/replayed/wrong-action/wrong-hostname CAPTCHA responses; CSRF; throttling; privileged-account TOTP with an RFC test vector; executable-upload rejection; and production rejection of CAPTCHA bypass.

Optional local demo requests are repeatable and create no fictitious mail/outbox entries. The clean SQL import contains no staff accounts, customer bookings, payments, private document tokens, outbox payloads or sessions.

## Practical verification limits

The automated route/form checks are server HTTP tests, not a real browser/device session. Browser-based responsive/keyboard testing was unavailable in this environment. Test the finished deployment in desktop and mobile browsers.

Turnstile response handling was checked using mocked provider responses. No live domain/site key was supplied. Real widget validation, hostname/CSP behaviour and failure recovery must be checked on the final domain.

Email persistence, retry/failure handling and templates were checked locally; no live SMTP inbox delivery was claimed. Configure the operator's SMTP, verify a controlled test delivery and run the queue/scheduler on the host.

No online payment gateway, bank verification API, hotel live inventory or supplier booking API is part of this project. Staff verify external payments and supplier reservations. Real business identity, contacts, payment instructions, prices, exchange rates and policies remain operator configuration.

The website has not been deployed to a public hosting account. This release is the requested installable source ZIP. Security audits are point-in-time dependency reports, not a claim of a full independent penetration test.
