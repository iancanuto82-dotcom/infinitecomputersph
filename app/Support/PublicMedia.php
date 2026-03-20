<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

class PublicMedia
{
    public static function diskName(): string
    {
        $configuredDisk = (string) config('filesystems.public_media_disk', 'public');

        if ($configuredDisk === 's3' && ! static::hasS3Credentials()) {
            return 'public';
        }

        return $configuredDisk;
    }

    public static function url(string $path): string
    {
        $disk = static::diskName();

        try {
            return Storage::disk($disk)->url($path);
        } catch (Throwable $throwable) {
            if ($disk !== 'public') {
                return Storage::disk('public')->url($path);
            }

            throw $throwable;
        }
    }

    private static function hasS3Credentials(): bool
    {
        $diskConfig = (array) config('filesystems.disks.s3', []);

        return static::filled($diskConfig['key'] ?? null)
            && static::filled($diskConfig['secret'] ?? null)
            && static::filled($diskConfig['bucket'] ?? null);
    }

    private static function filled(mixed $value): bool
    {
        return is_string($value)
            ? trim($value) !== ''
            : $value !== null;
    }
}
