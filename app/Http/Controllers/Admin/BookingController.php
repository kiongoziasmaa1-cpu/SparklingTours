<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Audit;
use App\Services\OutboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function dashboard(Request $r)
    {
        $q = Booking::query();
        if (! $r->user()->isAdmin()) {
            $q->where('assigned_to', $r->user()->id);
        }

        return view('admin.dashboard', ['total' => (clone $q)->count(), 'pending' => (clone $q)->whereIn('status', ['submitted', 'under_review'])->count(), 'upcoming' => (clone $q)->whereNotNull('start_date')->whereDate('start_date', '>=', today())->count(), 'bookings' => $q->latest()->limit(8)->get(), 'invoices' => Invoice::whereHas('booking', fn ($b) => $r->user()->isAdmin() ? $b : $b->where('assigned_to', $r->user()->id))->with('payments')->latest()->limit(8)->get()]);
    }

    public function index(Request $r)
    {
        $r->validate(['kind' => 'nullable|in:tour,transport', 'status' => 'nullable|string|max:30', 'q' => 'nullable|string|max:100', 'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $q = Booking::with('assignee');
        if (! $r->user()->isAdmin()) {
            $q->where('assigned_to', $r->user()->id);
        }
        foreach (['kind', 'status'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->input($f));
            }
        }
        if ($r->filled('from')) {
            $q->whereDate('start_date', '>=', $r->input('from'));
        } if ($r->filled('to')) {
            $q->whereDate('start_date', '<=', $r->input('to'));
        }
        if ($r->filled('q')) {
            $q->where(fn ($x) => $x->where('reference', 'like', '%'.$r->q.'%')->orWhere('name', 'like', '%'.$r->q.'%')->orWhere('email', 'like', '%'.$r->q.'%'));
        }

        return view('admin.bookings.index', ['bookings' => $q->latest()->paginate(20)->withQueryString()]);
    }

    public function show(Booking $booking)
    {
        Gate::authorize('view', $booking);
        $booking->load(['items', 'region']);
        if ($booking->source === 'tour_request') {
            return view('admin.bookings.tour-request', ['booking' => $booking, 'staff' => User::where('active', true)->get()]);
        }

        return view('admin.bookings.show', ['booking' => $booking->load(['items', 'accommodation.proposals', 'accommodation.selectedProposal', 'transport', 'invoices.payments']), 'staff' => User::where('active', true)->get()]);
    }

    public function update(Request $r, Booking $booking)
    {
        Gate::authorize('update', $booking);
        $d = $r->validate(['status' => 'required|in:submitted,under_review,awaiting_customer,awaiting_payment,confirmed,completed,declined,cancelled', 'assigned_to' => 'nullable|integer|exists:users,id', 'availability_confirmed' => 'required|boolean', 'internal_notes' => 'nullable|string|max:6000', 'confirmation_note' => 'nullable|string|max:1000']);
        if (! $r->user()->isAdmin()) {
            unset($d['assigned_to']);
        }
        if ($d['status'] === 'confirmed') {
            if (! $d['availability_confirmed'] || empty($d['confirmation_note'])) {
                return back()->withErrors(['status' => 'Confirm actual supplier availability and explain the agreed payment/confirmation terms.']);
            }
            if ($booking->accommodation && ! in_array($booking->accommodation->status, ['confirmed', 'declined'], true)) {
                return back()->withErrors(['status' => 'Resolve the accommodation arrangement before confirming the whole trip.']);
            }
        }
        $previous = $booking->status;
        $note = $d['confirmation_note'] ?? null;
        unset($d['confirmation_note']);
        DB::transaction(function () use ($booking, $d, $note, $previous) {
            $booking->update($d);
            Audit::record('booking.updated', $booking, ['status' => $booking->status, 'confirmation_note' => $note]);
            if ($booking->status === 'confirmed' && $previous !== 'confirmed') {
                app(OutboxService::class)->create($booking->email, 'Booking confirmed: '.$booking->reference, ['type' => 'confirmation', 'booking_id' => $booking->id]);
            }
        });
        app(OutboxService::class)->dispatchPending();

        return back()->with('status','Booking updated.');
    }
}
