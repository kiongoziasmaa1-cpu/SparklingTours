<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'published' => 'boolean', 'is_free' => 'boolean', 'included_item_ids' => 'array'];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function media()
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function scopePublished($q)
    {
        return $q->where('published', true);
    }

    public function displayName(): string
    {
        return app()->getLocale() === 'sw' && $this->name_sw ? $this->name_sw : $this->name;
    }
}
