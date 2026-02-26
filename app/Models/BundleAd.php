<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleAd extends Model
{
    protected $fillable = [
        'bundle_type',
        'image_url',
        'link_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
