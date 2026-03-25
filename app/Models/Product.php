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
        'gallery_images',
        'price',
        'cost_price',
        'initial_stock',
        'stock',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'gallery_images' => 'array',
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

        $galleryImageSources = $this->stored_gallery_image_src_list;
        if ($galleryImageSources !== []) {
            return $galleryImageSources[0];
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function getStoredGalleryImagesAttribute(): array
    {
        $raw = $this->gallery_images;
        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(fn ($image): string => trim((string) $image))
            ->filter(fn (string $image): bool => $image !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getStoredGalleryImageSrcListAttribute(): array
    {
        return collect($this->stored_gallery_images)
            ->map(function (string $image): string {
                if (preg_match('#^https?://#i', $image) === 1) {
                    return $image;
                }

                return PublicMedia::url($image);
            })
            ->filter(fn (string $image): bool => $image !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getGalleryImageSrcListAttribute(): array
    {
        $images = collect(array_merge(
            $this->image_src ? [$this->image_src] : [],
            $this->stored_gallery_image_src_list
        ))
            ->filter(fn (string $image): bool => $image !== '')
            ->unique()
            ->take(10)
            ->values()
            ->all();

        return $images;
    }
}
