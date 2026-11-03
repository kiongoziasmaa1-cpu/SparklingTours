# Administrator and guide workflow

## Content and staff

Start at `/login`. There is no public staff signup. `staff:create` creates the initial administrator; subsequent accounts are created under Staff & permissions. Guides see only assigned bookings. Invoicing and payment-verification permissions are separate. Administrators always have both. The last active administrator cannot be disabled or demoted through the staff form.

Staff sign in with their email and password and are taken directly to the admin dashboard. Login attempts are rate-limited; use a unique, strong password and keep staff account access restricted.

Manage destinations, tours, activities, food, guides, page content, media and stay-preference options through the sidebar. Upload or replace catalogue, route and vehicle photographs directly on their edit forms. Photo descriptions are optional on these forms; if left blank, the record name is used as the accessible image text. Replacing a photo in the library updates every page that uses it. The Photos & galleries page also controls regional galleries and the images on Photo credits.

Website settings contain brand/contact details and direct upload/replacement controls for the homepage hero, homepage about photo, company logo and default fallback photo, as well as public content, booking notification email, payment instructions and the manual TZS-per-USD rate/effective date. Set either **Booking notification email** or **Public email**; booking notices go to the notification email first and fall back to the public email. Configure SMTP and a queue worker in the hosting environment for real delivery; the local `log` mailer only writes messages to the log. If a requested currency conversion needs a rate that has not been configured, the affected prices are marked pending quotation rather than blocking a visitor's request. Set the verified rate and its effective date in Website settings to calculate those prices automatically; never use an assumed rate. The homepage's illustrative testimonial examples appear only while the preview/demo collection is enabled; replace them with verified guest feedback before using them publicly. Each catalogue, route, fare, vehicle, preference and page record can be deleted from its list or edit form. Records used in existing bookings are protected to preserve booking history; unused fares are removed when their route or vehicle is deleted. The catalogue forms no longer show the photo-library selector, day-by-day itinerary or package included-service fields. Food forms focus on meal/menu details, dietary notes and per-meal pricing; guide forms focus on languages, coverage areas, qualifications and guide rates. Both omit tour duration. Existing photo assignments, itineraries and package service selections are preserved when other fields are saved. A blank price means quotation required; the explicit free-service flag distinguishes a complimentary catalogue item. No real exchange rate, bank account or legal identity is supplied by the sample application. Keep real SMTP/Turnstile secrets in `.env` rather than editable CMS fields.

## Tour requests and accommodation

1. Open the request. Review dates, traveler details, service snapshots and known subtotal. Assign a guide as appropriate.
2. If accommodation was requested, review its class/type/location, stay dates, rooms, guests, budget and notes. A stay may extend before or after the tour.
3. Check suitable hotels manually. Record the offered rate per room per night, dates/rooms/board, real availability-check time, cancellation terms and internal supplier contact.
4. Select Proposed when not reserved, Held only with a real supplier hold/reference and expiry, or Confirmed only when the supplier has actually confirmed. Record a new checked option to reflect a changed supplier arrangement; history remains available.
5. Select the option agreed by the customer. Its rooms × nights and converted unit rate are included when creating a **new** draft. Existing invoice drafts are not silently rewritten; update their hotel line explicitly or create a replacement when appropriate.
6. Prepare the invoice. Keep unquoted prices blank until agreed. Set the deposit percentage/fixed amount or full-payment request, deadline and terms; save before issue.
7. Verify payments and complete any remaining supplier actions. To confirm the entire booking, mark actual availability and record agreed confirmation/payment terms. Requested accommodation must be resolved (confirmed or explicitly declined). Payment alone does not automatically confirm the booking.

There is no public Accommodation menu or live hotel marketplace. Sourcing and supplier contact are intentional manual guide work.

## Independent transport

Create a vehicle type, save a draft route, add one or more route/vehicle fares, then publish the route. Each fare has its own price, basis, capacity and luggage allowance. A type being globally active does not automatically make it available on every route.

Per-vehicle fare: the calculator uses the required vehicle count, with multiple vehicles allowed only when configured and within the maximum. Per-person fare: it uses billable passenger count within the craft's capacity. No automatic child discount is assumed. Return booking appears only for a configured return fare; create an independent reverse route when needed.

Requests carry a route/type/capacity/price snapshot. Operator availability still needs checking; this is not live dispatch or seat inventory. Changing a fare affects new quotes, not saved request/invoice history.

## Invoices, payments and receipts

Issue only after services, rates, supplier terms and business payment instructions have been reviewed. Number allocation and ledger posting use transactions/uniqueness. Issued contents are frozen. The customer receives an expiring unguessable link; tokens are stored hashed, while queued mail payloads are encrypted.

Record only independently verified money. Use the actual date, matching currency, method and unique transaction reference. Deposit and final payments are separate entries. Overpayments are rejected: allocate only the amount belonging to the invoice and handle any excess under your business procedure. Refunds must reference an original payment and cannot exceed its refundable amount. Never treat a customer screenshot, a clicked link or a form submission as proof of payment.

The customer invoice shows verified net paid, outstanding balance and due now. Receipts/refund acknowledgements can be downloaded for individual ledger entries using the protected invoice link. Invoices show the full ledger as well. Financial notes and supplier contacts stay private.

An issued invoice with zero net paid may be voided with a reason; its links are revoked. If money has been recorded, refund the relevant net amount before voiding. Create the replacement from the booking; the old document and ledger remain, and the new draft links to the superseded invoice. This first version does not implement formal accounting credit notes or an electronic fiscal device integration.

## Delivery and exceptions

Bookings, items, preferences and outbox entries are committed together. A notification outage does not remove the request. Pending/failed mail can be inspected under Notifications & audit and retried. Local log mail is not real external email. A transport `sent` status is a handoff result, not read/receipt tracking.

If a configured price changes between review and final submit, the customer must review the new breakdown. CAPTCHA failure returns to the same reloadable review; Edit restores validated form values. Review drafts expire after one hour; private document links expire after 72 hours and can be regenerated through Send private invoice link.

## Data handling and limits

The initial form intentionally avoids passport scans and card numbers. Obtain essential supplier documents later through an appropriate private process. Update the privacy notice with your real retention and contact procedure. Keep audit, booking and finance retention rules appropriate to the business, and remove unnecessary personal information through a controlled maintenance process that preserves required financial records.

English is the primary interface. English/Kiswahili translation files cover the core navigation and booking labels; long editorial content and many operational help strings remain English until the operator supplies translations. No direct card/mobile gateway, live accommodation inventory, customer account portal, automated WhatsApp service, map-based fare calculation or external supplier API is included.
