<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportFare extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'multiple_vehicles' => 'boolean', 'price' => 'decimal:2', 'return_price' => 'decimal:2'];
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }
}
