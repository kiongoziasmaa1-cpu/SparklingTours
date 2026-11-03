<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['deposit_value' => 'decimal:2', 'tax_percent' => 'decimal:2', 'due_date' => 'date', 'issue_date' => 'date', 'issued_at' => 'datetime', 'company_snapshot' => 'array', 'customer_snapshot' => 'array', 'grand_total' => 'decimal:2', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'deposit_required' => 'decimal:2'];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tokens()
    {
        return $this->hasMany(DocumentToken::class);
    }

    public function netPaidMinor(): int
    {
        return $this->payments->sum(fn ($p) => ($p->kind === 'refund' ? -1 : 1) * Money::minor($p->amount));
    }

    public function balanceMinor(): int
    {
        return Money::minor($this->grand_total) - $this->netPaidMinor();
    }

    public function dueNowMinor(): int
    {
        return max(0, $this->payment_mode === 'full' ? $this->balanceMinor() : min($this->balanceMinor(), Money::minor($this->deposit_required) - $this->netPaidMinor()));
    }

    public function settlement(): string
    {
        return $this->balanceMinor() <= 0 ? 'paid' : ($this->netPaidMinor() > 0 ? 'partially paid' : 'unpaid');
    }
}
