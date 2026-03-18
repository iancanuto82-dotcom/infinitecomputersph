<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BundleAd extends Model
{
    protected $fillable = [
        'bundle_type',
        'image_path',
        'image_url',
        'link_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageSrcAttribute(): ?string
    {
        if ($this->image_path) {
            return Storage::disk('public')->url($this->image_path);
        }

        if ($this->image_url) {
            return $this->image_url;
        }

        return null;
    }
}
