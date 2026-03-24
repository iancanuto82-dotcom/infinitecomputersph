<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PublicCatalogCache
{
    public const CATEGORIES_LIST_KEY = 'public_categories_list_v2';
    public const LANDING_DATA_KEY = 'public_landing_data_v2';
    public const BUILDER_CATEGORIES_KEY = 'public_builder_categories_v2';
    public const HEADER_NAVIGATION_KEY = 'public_header_navigation_v1';
    public const HEADER_FALLBACK_PRODUCTS_KEY = 'public_header_fallback_products_v1';

    public static function forgetAll(): void
    {
        Cache::forget(self::CATEGORIES_LIST_KEY);
        Cache::forget(self::LANDING_DATA_KEY);
        Cache::forget(self::BUILDER_CATEGORIES_KEY);
        Cache::forget(self::HEADER_NAVIGATION_KEY);
        Cache::forget(self::HEADER_FALLBACK_PRODUCTS_KEY);

        // Backward-compat with previous cache keys.
        Cache::forget('public_categories_list_v1');
        Cache::forget('public_landing_data_v1');
        Cache::forget('public_builder_categories_v1');
    }

    public static function forgetLanding(): void
    {
        Cache::forget(self::LANDING_DATA_KEY);
        Cache::forget('public_landing_data_v1');
    }
}
