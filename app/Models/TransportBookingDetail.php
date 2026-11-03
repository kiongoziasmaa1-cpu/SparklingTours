<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportBookingDetail extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'pickup_at' => 'datetime', 'return_at' => 'datetime'];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
