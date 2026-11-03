<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'unit_price' => 'decimal:2', 'amount' => 'decimal:2'];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function catalogItem()
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
