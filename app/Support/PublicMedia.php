<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicMedia
{
    public static function diskName(): string
    {
        return (string) config('filesystems.public_media_disk', 'public');
    }

    public static function url(string $path): string
    {
        return Storage::disk(static::diskName())->url($path);
    }
}
