<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image_path',
        'image_url',
        'price',
        'cost_price',
        'initial_stock',
        'stock',
        'category_id',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

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
