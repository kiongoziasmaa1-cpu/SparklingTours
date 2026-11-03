<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\DocumentToken;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function draft(Booking $booking): Invoice
    {
        return DB::transaction(function () use ($booking) {
            Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($existing = $booking->invoices()->where('status', 'draft')->first()) {
                return $existing;
            }
            $superseded = $booking->invoices()->where('status', 'void')->latest('id')->first();
            $invoice = $booking->invoices()->create(['public_id' => (string) Str::uuid(), 'supersedes_id' => $superseded?->id, 'currency' => $booking->currency, 'due_date' => now()->addDays(7)->toDateString(), 'terms' => SiteSetting::valueFor('invoice_terms', 'Subject to supplier availability and the agreed cancellation terms.')])->refresh();
            foreach ($booking->items as $item) {
                $invoice->items()->create(['description' => $item->description, 'unit' => $item->unit, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'amount' => $item->amount]);
            }
            if ($p = $booking->accommodation?->selectedProposal) {
                $amount = Money::convert(Money::minor($p->nightly_rate), $p->currency, $invoice->currency, SiteSetting::valueFor('usd_tzs_rate'));
                $qty = $p->rooms * $p->nights();
                $invoice->update(['terms' => trim($invoice->terms."\n\nHotel terms: ".$p->terms)]);
                $invoice->items()->create(['description' => $p->hotel_name.' - '.$p->room_type.', '.$p->meal_plan.', '.$p->check_in->toDateString().' to '.$p->check_out->toDateString(), 'quantity' => $qty, 'unit' => 'room-night', 'unit_price' => Money::decimal($amount), 'amount' => Money::decimal($amount * $qty)]);
            }
            $this->recalculate($invoice);
            Audit::record('invoice.draft', $invoice);

            return $invoice;
        }, 3);
    }

    public function save(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $i = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->requireDraft($i);
            $i->fill(collect($data)->only(['due_date', 'deposit_type', 'deposit_value', 'payment_mode', 'discount', 'tax_percent', 'terms'])->all());
            $i->save();
            $i->items()->delete();
            foreach ($data['items'] as $line) {
                $price = ($line['unit_price'] ?? '') === '' ? null : Money::minor((string) $line['unit_price']);
                $qty = (int) $line['quantity'];
                $amount = $price === null ? null : $price * $qty;
                if ($amount !== null && $amount > Money::MAX) {
                    $this->fail('A line exceeds the supported amount.');
                }
                $i->items()->create(['description' => $line['description'], 'unit' => $line['unit'], 'quantity' => $qty, 'unit_price' => $price === null ? null : Money::decimal($price), 'amount' => $amount === null ? null : Money::decimal($amount)]);
            }
            $this->recalculate($i);
            Audit::record('invoice.updated', $i);

            return $i->fresh();
        }, 3);
    }

    public function recalculate(Invoice $i): void
    {
        $subtotal = $i->items()->get()->sum(fn ($x) => $x->amount === null ? 0 : Money::minor($x->amount));
        $discount = Money::minor((string) $i->discount);
        if ($discount > $subtotal || $subtotal > Money::MAX) {
            $this->fail('Discount or subtotal is invalid.');
        }
        $tax = Money::percent($subtotal - $discount, (string) $i->tax_percent);
        $grand = $subtotal - $discount + $tax;
        if ($grand > Money::MAX) {
            $this->fail('Invoice exceeds the supported amount.');
        }
        $deposit = $i->payment_mode === 'full' ? $grand : ($i->deposit_type === 'percent' ? Money::percent($grand, (string) $i->deposit_value) : Money::minor((string) $i->deposit_value));
        $i->update(['subtotal' => Money::decimal($subtotal), 'tax_amount' => Money::decimal($tax), 'grand_total' => Money::decimal($grand), 'deposit_required' => Money::decimal(min($grand, $deposit))]);
    }

    public function issue(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $i = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->requireDraft($i);
            $stay = $i->booking->accommodation;
            if ($stay && $stay->status !== 'declined' && ! $stay->selectedProposal) {
                $this->fail('Resolve the accommodation request and select the agreed hotel before issuing the invoice.');
            }
            if ($stay?->selectedProposal?->reservation_status === 'held' && $stay->selectedProposal->hold_expires_at?->isPast()) {
                $this->fail('The hotel hold has expired. Recheck availability before issuing the invoice.');
            }
            if (! $i->items()->exists() || $i->items()->whereNull('unit_price')->exists()) {
                $this->fail('Every requested item must have an agreed price before issue.');
            }
            $this->recalculate($i);
            $company = SiteSetting::whereIn('key', ['company_name', 'email', 'phone', 'address', 'payment_instructions', 'logo_path', 'usd_tzs_rate', 'exchange_rate_date'])->pluck('value', 'key')->all();
            if (empty($company['payment_instructions'])) {
                $this->fail('Configure the payment instructions before issuing invoices.');
            }
            $i->update(['number' => app(SequenceService::class)->next('INV'), 'status' => 'issued', 'issued_at' => now(), 'issue_date' => now(config('tourism.timezone'))->toDateString(), 'company_snapshot' => $company,
                'customer_snapshot' => $i->booking->only(['name', 'email', 'phone', 'country'])]);
            Audit::record('invoice.issued', $i);

            return $i->fresh();
        }, 3);
    }

    public function send(Invoice $i): string
    {
        if ($i->status !== 'issued') {
            $this->fail('Issue the invoice before sending it.');
        }
        $url = DB::transaction(function () use ($i) {
            $raw = bin2hex(random_bytes(32));
            $i->tokens()->create(['token_hash' => hash('sha256', $raw), 'expires_at' => now()->addHours(config('tourism.document_link_hours'))]);
            $url = route('documents.show', [$i->public_id, $raw]);
            app(OutboxService::class)->create($i->booking->email, 'Invoice '.$i->number, ['type' => 'invoice', 'booking_id' => $i->booking_id, 'url' => $url, 'number' => $i->number]);
            Audit::record('invoice.sent', $i);

            return $url;
        });
        app(OutboxService::class)->dispatchPending();

        return $url;
    }

    public function resolve(string $publicId, string $token): Invoice
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/D', $token), 404);
        $t = DocumentToken::where('token_hash', hash('sha256', $token))->whereNull('revoked_at')->where('expires_at', '>', now())->whereHas('invoice', fn ($q) => $q->where('public_id', $publicId)->where('status', 'issued'))->first();
        abort_unless($t, 404);

        return $t->invoice->load(['booking', 'items', 'payments']);
    }

    public function recordPayment(Invoice $invoice, array $d, int $actor): Payment
    {
        return DB::transaction(function () use ($invoice, $d, $actor) {
            $i = Invoice::lockForUpdate()->findOrFail($invoice->id);
            if ($i->status !== 'issued') {
                $this->fail('Payments require an issued invoice.');
            }
            if ($d['currency'] !== $i->currency) {
                $this->fail('Payment currency must match the invoice.');
            }
            $amount = Money::minor((string) $d['amount']);
            if ($amount < 1) {
                $this->fail('Amount must be positive.');
            }
            if (Payment::where('method', $d['method'])->where('transaction_reference', $d['transaction_reference'])->exists()) {
                $this->fail('This transaction reference is already recorded.');
            }
            if ($d['kind'] === 'refund') {
                $source = $i->payments()->where('kind', 'payment')->find($d['source_payment_id'] ?? null);
                if (! $source) {
                    $this->fail('Select the original payment for the refund.');
                }
                $refunded = $i->payments()->where('source_payment_id', $source->id)->get()->sum(fn ($p) => Money::minor($p->amount));
                if ($amount > Money::minor($source->amount) - $refunded || $amount > $i->netPaidMinor()) {
                    $this->fail('Refund exceeds the refundable amount.');
                }
            } elseif ($amount > $i->balanceMinor()) {
                $this->fail('Overpayment is not accepted. Record only the amount allocated to this invoice.');
            }
            $p = $i->payments()->create(collect($d)->only(['kind', 'amount', 'currency', 'paid_on', 'method', 'transaction_reference', 'notes'])->merge(['source_payment_id' => $d['kind'] === 'refund' ? $d['source_payment_id'] : null, 'recorded_by' => $actor])->all());
            Audit::record('payment.'.$d['kind'], $p, ['invoice_id' => $i->id, 'amount' => $p->amount, 'currency' => $p->currency]);
            app(OutboxService::class)->create($i->booking->email, 'Payment update for '.$i->number, ['type' => 'payment', 'booking_id' => $i->booking_id, 'number' => $i->number, 'amount' => $p->amount, 'currency' => $p->currency, 'kind' => $p->kind]);

            return $p;
        }, 3);
    }

    public function void(Invoice $i, string $reason): void
    {
        DB::transaction(function () use ($i, $reason) {
            $locked = Invoice::lockForUpdate()->findOrFail($i->id);
            if ($locked->status !== 'issued' || $locked->netPaidMinor() !== 0) {
                $this->fail('Only an issued invoice with no net payment can be voided. Refund recorded funds first.');
            }
            $locked->update(['status' => 'void', 'void_reason' => $reason]);
            $locked->tokens()->update(['revoked_at' => now()]);
            Audit::record('invoice.void', $locked, ['reason' => $reason]);
        }, 3);
    }

    private function requireDraft(Invoice $i): void
    {
        if ($i->status !== 'draft') {
            $this->fail('Issued invoice contents cannot be edited. Void and replace an eligible invoice.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['invoice' => $message]);
    }
}
