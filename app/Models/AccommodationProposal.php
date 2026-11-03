<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationProposal extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['check_in' => 'date', 'check_out' => 'date', 'availability_checked_at' => 'datetime', 'hold_expires_at' => 'datetime', 'nightly_rate' => 'decimal:2'];
    }

    public function accommodationRequest()
    {
        return $this->belongsTo(AccommodationRequest::class);
    }

    public function nights(): int
    {
        return (int) $this->check_in->diffInDays($this->check_out);
    }
}
