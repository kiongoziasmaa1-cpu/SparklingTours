<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'active_from' => 'date', 'active_until' => 'date'];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function fares()
    {
        return $this->hasMany(TransportFare::class);
    }

    public function media()
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function activeFares()
    {
        return $this->fares()->where('active', true)->whereHas('vehicleType', fn ($q) => $q->where('active', true));
    }
}
