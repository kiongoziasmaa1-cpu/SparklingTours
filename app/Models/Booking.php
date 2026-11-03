<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'availability_confirmed' => 'boolean', 'quote_required' => 'boolean', 'known_subtotal' => 'decimal:2'];
    }

    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function accommodation()
    {
        return $this->hasOne(AccommodationRequest::class);
    }

    public function transport()
    {
        return $this->hasOne(TransportBookingDetail::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
