<?php

namespace App\Http\Controllers;

use App\Http\Requests\TourBookingRequest;
use App\Http\Requests\TransportBookingRequest;
use App\Models\Booking;
use App\Models\CatalogItem;
use App\Models\PreferenceOption;
use App\Models\Region;
use App\Models\SiteSetting;
use App\Models\TransportRoute;
use App\Services\BookingSubmissionService;
use App\Services\CaptchaVerifier;
use App\Services\TourPricingService;
use App\Services\TransportPricingService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function tourContactForm(string $slug)
    {
        $tour = CatalogItem::published()->where('kind', 'tour')->with('media', 'region')->where('slug', $slug)->firstOrFail();

        return view('booking.tour-contact', ['tour' => $tour, 'requestKey' => (string) Str::uuid()]);
    }

    public function submitTourContact(Request $r, string $slug, CaptchaVerifier $captcha, BookingSubmissionService $submissions)
    {
        $tour = CatalogItem::published()->where('kind', 'tour')->where('slug', $slug)->firstOrFail();
        $d = $r->validate([
            'request_key' => 'required|uuid',
            'name' => 'required|string|max:150',
            'email' => 'required|email:rfc|max:190',
            'phone' => 'required|string|min:6|max:35',
            'country' => 'required|string|max:100',
            'traveler_count' => 'required|integer|min:1|max:100',
            'terms' => 'accepted',
            'website' => 'nullable|string|max:0',
        ]);
        unset($d['request_key'], $d['terms'], $d['website']);
        $captcha->verify($r->input('cf-turnstile-response'), 'tour_booking', $r->ip());

        $guest = $r->session()->get('guest_booking_key');
        if (! $guest) {
            $guest = (string) Str::uuid();
            $r->session()->put('guest_booking_key', $guest);
        }
        $booking = $submissions->submitTourInquiry(
            $tour,
            $d,
            $r->string('request_key')->toString(),
            hash_hmac('sha256', $guest, config('app.key'))
        );

        return $this->receivedRedirect($booking);
    }

    public function tourForm(Request $r)
    {
        $tour = $r->filled('tour') ? CatalogItem::published()->where('kind', 'tour')->findOrFail($r->integer('tour')) : null;

        return view('booking.tour', ['tour' => $tour, 'regions' => Region::all(), 'items' => CatalogItem::published()->where('kind', '!=', 'tour')->orderBy('kind')->get(), 'options' => PreferenceOption::where('active', true)->get()->groupBy('group')]);
    }

    public function transportForm(string $slug)
    {
        $route = TransportRoute::where('slug', $slug)->where('published', true)->with('activeFares.vehicleType')->firstOrFail();
        abort_if($route->activeFares->isEmpty(), 404);

        return view('booking.transport', compact('route'));
    }

    public function reviewTour(TourBookingRequest $r, TourPricingService $pricing)
    {
        return $this->draft($r, 'tour', $r->validated(), $pricing->quote($r->validated()));
    }

    public function reviewTransport(TransportBookingRequest $r, TransportPricingService $pricing)
    {
        return $this->draft($r, 'transport', $r->validated(), $pricing->quote($r->validated()));
    }

    private function draft(Request $r, string $kind, array $data, array $quote)
    {
        $id = (string) Str::uuid();
        $draft = ['kind' => $kind, 'data' => $data, 'quote' => $quote, 'key' => (string) Str::uuid(), 'created' => now()->timestamp];
        $r->session()->put('booking_drafts.'.$id, $draft);
        $drafts = $r->session()->get('booking_drafts', []);
        if (count($drafts) > 8) {
            $r->session()->put('booking_drafts', array_slice($drafts, -8, null, true));
        }

        return redirect()->route('bookings.review', $id);
    }

    public function review(Request $r, string $draftId)
    {
        $draft = $r->session()->get('booking_drafts.'.$draftId);
        abort_unless($draft && now()->timestamp - $draft['created'] <= 3600, 419, 'This review has expired. Please start a new request.');

        return view('booking.review', ['draft' => $draft, 'draftId' => $draftId, 'priceChanged' => (bool) session('priceChanged')]);
    }

    public function edit(Request $r, string $draftId)
    {
        $draft = $r->session()->get('booking_drafts.'.$draftId);
        abort_unless($draft, 419);
        $r->session()->flashInput($draft['data']);
        if ($draft['kind'] === 'tour') {
            return redirect()->route('tour.book', array_filter(['tour' => $draft['data']['tour_id'] ?? null, 'region' => $draft['data']['region_id']]));
        }
        $route = TransportRoute::findOrFail($draft['data']['route_id']);

        return redirect()->route('transport.book', $route->slug);
    }

    public function submit(Request $r, string $kind, CaptchaVerifier $captcha, BookingSubmissionService $submissions)
    {
        abort_unless(in_array($kind, ['tour', 'transport'], true), 404);
        $r->validate(['draft_id' => 'required|uuid', 'website' => 'nullable|string|max:0']);
        $draft = $r->session()->get('booking_drafts.'.$r->draft_id);
        abort_unless($draft && $draft['kind'] === $kind, 419);
        $guest = $r->session()->get('guest_booking_key');
        if (! $guest) {
            $guest = (string) Str::uuid();
            $r->session()->put('guest_booking_key', $guest);
        }
        $guestHash = hash_hmac('sha256', $guest, config('app.key'));
        if ($existing = Booking::where('idempotency_key', $draft['key'])->first()) {
            abort_unless(hash_equals($guestHash, $existing->guest_hash), 403);
            abort_unless(hash_equals($existing->payload_hash, hash('sha256', json_encode($draft['data']))), 409);

            return $this->receivedRedirect($existing);
        }
        abort_if(now()->timestamp - $draft['created'] > 3600, 419, 'This review expired. Please start a new request.');
        $form = $kind === 'tour' ? new TourBookingRequest : new TransportBookingRequest;
        $form->merge($draft['data']);
        $rules = $form->rules();
        // The draft was validated before storage; validate again so date rules cannot expire unnoticed.
        try {
            $d = Validator::make($draft['data'], $rules)->validate();
            $quote = $kind === 'tour' ? app(TourPricingService::class)->quote($d) : app(TransportPricingService::class)->quote($d);
        } catch (ValidationException $e) {
            return redirect()->route('bookings.review', $r->draft_id)->withErrors($e->errors());
        }
        if (! hash_equals($draft['quote']['hash'], $quote['hash'])) {
            $draft['quote'] = $quote;
            $r->session()->put('booking_drafts.'.$r->draft_id, $draft);

            return redirect()->route('bookings.review', $r->draft_id)->with('priceChanged', true);
        }
        try {
            $captcha->verify($r->input('cf-turnstile-response'), $kind.'_booking', $r->ip());
        } catch (ValidationException $e) {
            return redirect()->route('bookings.review', $r->draft_id)->withErrors($e->errors());
        }
        $b = $submissions->submit($kind, $d, $quote, $draft['key'], $guestHash);

        return $this->receivedRedirect($b);
    }

    private function receivedRedirect(Booking $b)
    {
        return redirect(URL::temporarySignedRoute('bookings.received', now()->addHours(2), ['booking' => $b->public_id]));
    }

    public function received(string $booking)
    {
        $b = Booking::where('public_id', $booking)->firstOrFail();

        return view('booking.received', ['reference' => $b->reference]);
    }

    public function estimate(Request $r, string $kind)
    {
        $common = ['currency' => 'required|in:TZS,USD', 'adults' => 'required|integer|min:1|max:100', 'children' => 'required|integer|min:0|max:100'];
        $rules = $kind === 'tour' ? array_merge($common, ['region_id' => 'required|integer|exists:regions,id', 'tour_id' => 'nullable|integer', 'service_ids' => 'nullable|array|max:30', 'service_ids.*' => 'integer|distinct', 'start_date' => 'required|date_format:Y-m-d|after_or_equal:today', 'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date']) : array_merge($common, ['route_id' => 'required|integer', 'fare_id' => 'required|integer', 'pickup_at' => 'required|date_format:Y-m-d\TH:i', 'return_at' => 'nullable|date_format:Y-m-d\TH:i', 'luggage' => 'required|integer|min:0|max:200']);
        abort_unless(in_array($kind, ['tour', 'transport'], true), 404);
        $d = $r->validate($rules);
        $q = $kind === 'tour' ? app(TourPricingService::class)->quote($d) : app(TransportPricingService::class)->quote($d);

        return response()->json(['total' => Money::format($q['total_minor'],$q['currency']), 'pending' => $q['pending'], 'lines' => $q['lines'], 'vehicles' => $q['vehicles'] ?? null]);
    }
}
