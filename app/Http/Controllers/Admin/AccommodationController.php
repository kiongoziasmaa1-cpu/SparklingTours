<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccommodationProposal;
use App\Models\Booking;
use App\Services\Audit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccommodationController extends Controller
{
    public function store(Request $r, Booking $booking)
    {
        Gate::authorize('update', $booking);
        abort_unless($booking->kind === 'tour' && $booking->accommodation, 404);
        $d = $r->validate(['hotel_name' => 'required|string|max:150', 'location' => 'required|string|max:150', 'check_in' => 'required|date', 'check_out' => 'required|date|after:check_in', 'rooms' => 'required|integer|min:1|max:100', 'room_type' => 'required|string|max:80', 'meal_plan' => 'required|string|max:80', 'nightly_rate' => 'required|decimal:0,2|min:0|max:9999999999', 'currency' => 'required|in:TZS,USD', 'inclusions' => 'nullable|string|max:2000', 'terms' => 'required|string|max:3000', 'reservation_status' => 'required|in:proposed,held,confirmed', 'supplier_reference' => 'required_if:reservation_status,held,confirmed|nullable|string|max:150', 'supplier_contact' => 'nullable|string|max:190', 'availability_checked_at' => 'required|date|before_or_equal:'.now(config('tourism.timezone'))->format('Y-m-d H:i:s'), 'hold_expires_at' => 'required_if:reservation_status,held|nullable|date|after:'.now(config('tourism.timezone'))->format('Y-m-d H:i:s'), 'internal_notes' => 'nullable|string|max:3000']);
        foreach (['availability_checked_at', 'hold_expires_at'] as $f) {
            if (! empty($d[$f])) {
                $d[$f] = Carbon::parse($d[$f], config('tourism.timezone'))->utc();
            }
        }
        $p = $booking->accommodation->proposals()->create($d);
        $booking->accommodation->update(['status' => 'proposed']);
        Audit::record('hotel.proposed', $p);

        return back()->with('status', 'Hotel option recorded. Select the agreed option to include it on a new invoice.');
    }

    public function select(Request $r, Booking $booking, AccommodationProposal $proposal)
    {
        Gate::authorize('update', $booking);
        abort_unless($booking->accommodation && $proposal->accommodation_request_id === $booking->accommodation->id, 404);
        $r->validate(['customer_agreed' => 'accepted']);
        if ($proposal->reservation_status === 'held' && $proposal->hold_expires_at?->isPast()) {
            return back()->withErrors(['hotel' => 'This hotel hold has expired. Record a new checked option.']);
        }
        $booking->accommodation->update(['selected_proposal_id' => $proposal->id, 'status' => $proposal->reservation_status]);
        Audit::record('hotel.selected', $proposal);

        return back()->with('status', 'Agreed hotel selected. Existing invoice drafts are unchanged; update or recreate their hotel lines explicitly.');
    }

    public function status(Request $r, Booking $booking)
    {
        Gate::authorize('update', $booking);
        abort_unless($booking->accommodation, 404);
        $d = $r->validate(['status' => 'required|in:requested,sourcing,unavailable,declined', 'reason' => 'required|string|max:1000']);
        $booking->accommodation->update(['status' => $d['status']]);
        Audit::record('hotel.status', $booking->accommodation, $d);

        return back()->with('status','Accommodation status updated.');
    }
}
