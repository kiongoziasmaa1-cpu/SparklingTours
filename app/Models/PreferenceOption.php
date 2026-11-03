<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreferenceOption extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
