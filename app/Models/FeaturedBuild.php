<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;

class FeaturedBuild extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'image_url',
        'gallery_images',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'sort_order' => 'integer',
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

    /**
     * @return array<int, string>
     */
    public function getGalleryImageSrcListAttribute(): array
    {
        $raw = $this->gallery_images;
        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(function ($image): string {
                $value = trim((string) $image);
                if ($value === '') {
                    return '';
                }

                if (preg_match('#^https?://#i', $value) === 1) {
                    return $value;
                }

                return PublicMedia::url($value);
            })
            ->filter(fn (string $image) => $image !== '')
            ->unique()
            ->values()
            ->all();
    }
}
