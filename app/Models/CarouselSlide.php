<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;

class CarouselSlide extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'label',
        'image_path',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageSrcAttribute(): ?string
    {
        if ($this->image_path) {
            return PublicMedia::url((string) $this->image_path);
        }

        if ($this->image_url) {
            return $this->image_url;
        }

        return null;
    }
}
