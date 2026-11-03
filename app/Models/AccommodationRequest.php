<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['preferences' => 'array'];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function proposals()
    {
        return $this->hasMany(AccommodationProposal::class);
    }

    public function selectedProposal()
    {
        return $this->belongsTo(AccommodationProposal::class, 'selected_proposal_id');
    }
}
