<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedBrand extends Model
{
    protected $fillable = [
        'name',
        'logo_path',
        'logo_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getLogoSrcAttribute(): ?string
    {
        if ($this->logo_path) {
            return '/storage/'.ltrim((string) $this->logo_path, '/');
        }

        if ($this->logo_url) {
            return $this->logo_url;
        }

        return null;
    }
}
