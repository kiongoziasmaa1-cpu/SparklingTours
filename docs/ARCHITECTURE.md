# Architecture and data relationships

The application uses server-rendered Blade, Bootstrap 5 and progressive JavaScript. Booking forms work through a server-calculated, session-bound review followed by final CAPTCHA verification and a transactional write. A lightweight JSON estimate endpoint previews prices but cannot set the persisted amount.

| Area | Records / relationship |
|---|---|
| Regions and content | `regions` → `catalog_items` (kind: destination, tour, activity, food or guide); media references and package included-service IDs |
| Transport | `transport_routes` → `transport_fares` ← `vehicle_types`; active route/type pairs are explicitly configured |
| Requests | `bookings` → immutable `booking_items`; kind is tour or transport, source distinguishes package/custom/route |
| Accommodation | A tour booking optionally has one `accommodation_requests` row → several checked `accommodation_proposals`; one selected proposal |
| Transfer details | A transport booking has one `transport_booking_details` row; no required tour or accommodation relationship |
| Finance | Booking → `invoices` → `invoice_items`, `payments`, `document_tokens`; refund rows reference original payment rows |
| Operations | Staff users, assignment, `audit_logs`, `outbox_messages`, `inquiries`, `site_settings`, `pages`, `preference_options` |

Catalogue types share a `catalog_items` table rather than separate near-identical tables for tours/food/etc. Controllers whitelist the kind and fields, while services enforce compatibility. Tour itineraries/inclusions are editable line-based content; this is an intentional practical implementation of the suggested SRS model, not a change of technology.

Money is calculated in integer minor units, with BCMath for currency conversion. DECIMAL fields retain canonical two-decimal values. Each invoice has one settlement currency; manual conversion rates are explicit. Missing prices remain null, never an automatic zero.

Price hashes are server HMACs over the computed quote. Final submission reloads the validated session draft, rechecks current data and requires another review if its quote changed. Scoped idempotency keys and a unique constraint prevent repeat requests from creating duplicate rows; the reference itself is not an access credential.

Public requests do not require accounts. Staff use hashed passwords, TOTP, database sessions and role/policy checks. Customer documents use high-entropy expiring/revocable bearer links, with only token hashes stored in the token table. Financial content is escaped and PDFs disable remote resources/PHP execution. Uploaded images are type-checked, size/dimension-limited and re-encoded as WebP.

All service dates use the Tanzania local calendar/timezone; transport instants are stored in UTC. Issued invoices and saved requests keep historical descriptions/prices when catalogue data changes. Booking state, hotel reservation state, invoice lifecycle and ledger settlement are distinct.

Persistent outbox records are written in the booking transaction and queued after commit. Queue failure is recoverable through the scheduler/dashboard. Mail payloads are encrypted because they may carry private document links. Delivery retries are at least once; SMTP handoff is not a guarantee of inbox receipt.

Tests cover public/admin HTTP routes, validation, server pricing, region/capacity constraints, optional preferences, idempotency, price refresh, assignment/finance permissions, invoices/receipts, deposits/refunds, failed mail/database handling, CSRF, throttling, CAPTCHA response validation and TOTP. Final browser and real external-service checks require the target deployment.
