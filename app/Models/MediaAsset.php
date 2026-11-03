<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['gallery' => 'boolean'];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function url(): string
    {
        return asset($this->path);
    }
}
