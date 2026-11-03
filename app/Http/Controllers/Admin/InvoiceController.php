<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\OutboxService;
use App\Services\PdfService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function create(Booking $booking, InvoiceService $s)
    {
        Gate::authorize('invoice', $booking);
        try {
            $i = $s->draft($booking);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }

return redirect()->route('admin.invoices.edit', $i);
    }

    public function edit(Invoice $invoice)
    {
        abort_unless(Gate::any(['invoice', 'payment'], $invoice->booking), 403);

        return view('admin.invoices.edit', ['invoice' => $invoice->load('items', 'payments')]);
    }

    public function update(Request $r, Invoice $invoice, InvoiceService $s)
    {
        Gate::authorize('invoice', $invoice->booking);
        $d = $r->validate(['due_date' => 'required|date|after_or_equal:'.now(config('tourism.timezone'))->toDateString(), 'payment_mode' => 'required|in:deposit,full', 'deposit_type' => 'required|in:percent,fixed', 'deposit_value' => 'required|decimal:0,2|min:0|max:9999999999', 'discount' => 'required|decimal:0,2|min:0|max:9999999999', 'tax_percent' => 'required|decimal:0,2|min:0|max:100', 'terms' => 'nullable|string|max:4000', 'items' => 'required|array|min:1|max:100', 'items.*.description' => 'required|string|max:500', 'items.*.unit' => 'required|string|max:40', 'items.*.quantity' => 'required|integer|min:1|max:100000', 'items.*.unit_price' => 'nullable|decimal:0,2|min:0|max:9999999999']);
        if ($d['deposit_type'] === 'percent' && Money::minor((string) $d['deposit_value']) > 10000) {
            return back()->withErrors(['deposit_value' => 'Percentage must not exceed 100.']);
        }
        $s->save($invoice, $d);

        return back()->with('status', 'Draft saved.');
    }

    public function issue(Request $r, Invoice $invoice, InvoiceService $s)
    {
        Gate::authorize('invoice', $invoice->booking);
        $r->validate(['agreement_confirmed' => 'accepted']);
        $s->issue($invoice);

        return back()->with('status', 'Invoice issued and contents frozen.');
    }

    public function send(Invoice $invoice, InvoiceService $s)
    {
        Gate::authorize('invoice', $invoice->booking);
        $url = $s->send($invoice);

        return back()->with('status', 'Invoice notification queued. Check delivery status.')->with('document_url', $url);
    }

    public function payment(Request $r, Invoice $invoice, InvoiceService $s)
    {
        Gate::authorize('payment', $invoice->booking);
        $d = $r->validate(['kind' => 'required|in:payment,refund', 'amount' => 'required|decimal:0,2|min:0.01|max:9999999999', 'currency' => 'required|in:TZS,USD', 'paid_on' => 'required|date|before_or_equal:today', 'method' => 'required|in:bank,mobile,cash,other', 'transaction_reference' => 'required|string|max:150', 'source_payment_id' => 'nullable|integer', 'notes' => 'nullable|string|max:1000', 'verified' => 'accepted']);
        $s->recordPayment($invoice, $d, $r->user()->id);
        app(OutboxService::class)->dispatchPending();

        return back()->with('status', 'Verified ledger entry recorded.');
    }

    public function void(Request $r, Invoice $invoice, InvoiceService $s)
    {
        Gate::authorize('invoice', $invoice->booking);
        $r->validate(['reason' => 'required|string|max:1000']);
        $s->void($invoice, $r->reason);

        return back()->with('status', 'Invoice voided and document links revoked. Create a replacement from the booking.');
    }

    public function pdf(Invoice $invoice)
    {
        abort_unless(Gate::any(['invoice', 'payment'], $invoice->booking), 403);

        return app(PdfService::class)->invoice($invoice);
    }
}
