<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BundleAd;
use App\Models\CarouselSlide;
use App\Models\FeaturedBrand;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WebsiteContentController extends Controller
{
    private const MAX_SLIDES = 6;
    private const MAX_BRANDS = 18;
    private const MAX_BUNDLE_ADS_PER_TYPE = 12;

    public function edit(): View
    {
        $slides = CarouselSlide::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $brands = FeaturedBrand::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $bundleAds = BundleAd::query()
            ->orderBy('bundle_type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $entryBundleAds = $bundleAds
            ->where('bundle_type', 'entry')
            ->values();

        $gamingBundleAds = $bundleAds
            ->where('bundle_type', 'gaming')
            ->values();

        return view('admin.content.edit', [
            'slideRows' => $this->buildSlideRows($slides, self::MAX_SLIDES),
            'brandRows' => $this->buildBrandRows($brands, self::MAX_BRANDS),
            'entryBundleAdRows' => $this->buildBundleRows($entryBundleAds, self::MAX_BUNDLE_ADS_PER_TYPE),
            'gamingBundleAdRows' => $this->buildBundleRows($gamingBundleAds, self::MAX_BUNDLE_ADS_PER_TYPE),
            'slideImageSrcMap' => $slides
                ->mapWithKeys(fn (CarouselSlide $slide) => [(string) $slide->id => $slide->image_src])
                ->all(),
            'brandLogoSrcMap' => $brands
                ->mapWithKeys(fn (FeaturedBrand $brand) => [(string) $brand->id => $brand->logo_src])
                ->all(),
            'maxSlides' => self::MAX_SLIDES,
            'maxBrands' => self::MAX_BRANDS,
            'maxBundleAdsPerType' => self::MAX_BUNDLE_ADS_PER_TYPE,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slides' => ['nullable', 'array', 'max:'.self::MAX_SLIDES],
            'slides.*.id' => ['nullable', 'integer', 'exists:carousel_slides,id'],
            'slides.*.title' => ['nullable', 'string', 'max:120'],
            'slides.*.subtitle' => ['nullable', 'string', 'max:255'],
            'slides.*.label' => ['nullable', 'string', 'max:60'],
            'slides.*.image_url' => ['nullable', 'url', 'max:2048'],
            'slides.*.image_file' => ['nullable', 'image', 'max:5120'],
            'slides.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'slides.*.is_active' => ['nullable', 'boolean'],
            'slides.*.remove_image' => ['nullable', 'boolean'],
            'slides.*._delete' => ['nullable', 'boolean'],

            'brands' => ['nullable', 'array', 'max:'.self::MAX_BRANDS],
            'brands.*.id' => ['nullable', 'integer', 'exists:featured_brands,id'],
            'brands.*.name' => ['nullable', 'string', 'max:120'],
            'brands.*.logo_url' => ['nullable', 'url', 'max:2048'],
            'brands.*.logo_file' => ['nullable', 'image', 'max:5120'],
            'brands.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'brands.*.is_active' => ['nullable', 'boolean'],
            'brands.*.remove_logo' => ['nullable', 'boolean'],
            'brands.*._delete' => ['nullable', 'boolean'],

            'entry_bundle_ads' => ['nullable', 'array', 'max:'.self::MAX_BUNDLE_ADS_PER_TYPE],
            'entry_bundle_ads.*.id' => ['nullable', 'integer', 'exists:bundle_ads,id'],
            'entry_bundle_ads.*.image_url' => ['nullable', 'url', 'max:2048'],
            'entry_bundle_ads.*.link_url' => ['nullable', 'url', 'max:2048'],
            'entry_bundle_ads.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'entry_bundle_ads.*.is_active' => ['nullable', 'boolean'],
            'entry_bundle_ads.*._delete' => ['nullable', 'boolean'],

            'gaming_bundle_ads' => ['nullable', 'array', 'max:'.self::MAX_BUNDLE_ADS_PER_TYPE],
            'gaming_bundle_ads.*.id' => ['nullable', 'integer', 'exists:bundle_ads,id'],
            'gaming_bundle_ads.*.image_url' => ['nullable', 'url', 'max:2048'],
            'gaming_bundle_ads.*.link_url' => ['nullable', 'url', 'max:2048'],
            'gaming_bundle_ads.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'gaming_bundle_ads.*.is_active' => ['nullable', 'boolean'],
            'gaming_bundle_ads.*._delete' => ['nullable', 'boolean'],
        ]);

        $slidesPayload = (array) ($validated['slides'] ?? []);
        $brandsPayload = (array) ($validated['brands'] ?? []);
        $entryBundleAdsPayload = (array) ($validated['entry_bundle_ads'] ?? []);
        $gamingBundleAdsPayload = (array) ($validated['gaming_bundle_ads'] ?? []);

        $errors = [];
        $this->validateContentRows(
            $slidesPayload,
            $brandsPayload,
            $entryBundleAdsPayload,
            $gamingBundleAdsPayload,
            $errors
        );

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $summary = DB::transaction(function () use ($request, $slidesPayload, $brandsPayload, $entryBundleAdsPayload, $gamingBundleAdsPayload): array {
            return [
                'slides' => $this->syncSlides($request, $slidesPayload),
                'brands' => $this->syncBrands($request, $brandsPayload),
                'bundle_ads' => [
                    'entry' => $this->syncBundleAds($entryBundleAdsPayload, 'entry'),
                    'gaming' => $this->syncBundleAds($gamingBundleAdsPayload, 'gaming'),
                ],
            ];
        });

        AuditLogger::record(
            $request,
            'updated',
            'website_content',
            null,
            'Pricelist content',
            'Updated carousel, featured brands, and bundle ads.',
            $summary
        );

        $this->forgetPublicLandingCaches();

        return redirect()
            ->route('admin.content.edit')
            ->with('status', 'Website content updated.');
    }

    /**
     * @param array<int, array<string, mixed>> $slidesPayload
     * @param array<int, array<string, mixed>> $brandsPayload
     * @param array<int, array<string, mixed>> $entryBundleAdsPayload
     * @param array<int, array<string, mixed>> $gamingBundleAdsPayload
     * @param array<string, string> $errors
     */
    private function validateContentRows(
        array $slidesPayload,
        array $brandsPayload,
        array $entryBundleAdsPayload,
        array $gamingBundleAdsPayload,
        array &$errors
    ): void
    {
        foreach ($slidesPayload as $index => $row) {
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $imageUrl = $this->normalizeImageUrl(trim((string) ($row['image_url'] ?? '')));
            $hasFile = isset($row['image_file']);

            if ($delete) {
                continue;
            }

            $hasAnyContent = $imageUrl !== '' || $hasFile || ! empty($row['id']);
            if (! $hasAnyContent) {
                continue;
            }
        }

        foreach ($brandsPayload as $index => $row) {
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $name = trim((string) ($row['name'] ?? ''));
            $logoUrl = trim((string) ($row['logo_url'] ?? ''));
            $hasFile = isset($row['logo_file']);

            if ($delete) {
                continue;
            }

            $hasAnyContent = $name !== '' || $logoUrl !== '' || $hasFile || ! empty($row['id']);
            if (! $hasAnyContent) {
                continue;
            }

            if ($name === '') {
                $errors["brands.$index.name"] = 'Brand name is required.';
            }
        }

        foreach ($entryBundleAdsPayload as $index => $row) {
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $imageUrl = trim((string) ($row['image_url'] ?? ''));
            $linkUrl = trim((string) ($row['link_url'] ?? ''));

            if ($delete) {
                continue;
            }

            $hasAnyContent = $imageUrl !== '' || $linkUrl !== '' || ! empty($row['id']);
            if (! $hasAnyContent) {
                continue;
            }

            if ($imageUrl === '') {
                $errors["entry_bundle_ads.$index.image_url"] = 'Image URL is required.';
            }
        }

        foreach ($gamingBundleAdsPayload as $index => $row) {
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $imageUrl = trim((string) ($row['image_url'] ?? ''));
            $linkUrl = trim((string) ($row['link_url'] ?? ''));

            if ($delete) {
                continue;
            }

            $hasAnyContent = $imageUrl !== '' || $linkUrl !== '' || ! empty($row['id']);
            if (! $hasAnyContent) {
                continue;
            }

            if ($imageUrl === '') {
                $errors["gaming_bundle_ads.$index.image_url"] = 'Image URL is required.';
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $slidesPayload
     * @return array<string, int>
     */
    private function syncSlides(Request $request, array $slidesPayload): array
    {
        $created = 0;
        $updated = 0;
        $deleted = 0;

        foreach ($slidesPayload as $index => $row) {
            $slide = ! empty($row['id']) ? CarouselSlide::query()->find((int) $row['id']) : null;
            if (! $slide && ! empty($row['id'])) {
                continue;
            }

            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($delete) {
                if ($slide) {
                    $this->deletePublicFile($slide->image_path);
                    $slide->delete();
                    $deleted++;
                }
                continue;
            }

            $imageUrl = $this->normalizeImageUrl(trim((string) ($row['image_url'] ?? '')));
            $sortOrder = isset($row['sort_order']) ? (int) $row['sort_order'] : ($index + 1);
            $isActive = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $removeImage = filter_var($row['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $hasAnyContent = $imageUrl !== '' || $request->hasFile("slides.$index.image_file") || $slide !== null;
            if (! $hasAnyContent) {
                continue;
            }

            $slide ??= new CarouselSlide();

            $slide->title = trim((string) ($slide->title ?? '')) !== '' ? (string) $slide->title : 'Slide '.max(1, $sortOrder);
            $slide->subtitle = null;
            $slide->label = null;
            $slide->image_url = $imageUrl !== '' ? $imageUrl : null;
            $slide->sort_order = max(1, $sortOrder);
            $slide->is_active = $isActive;

            if ($removeImage && $slide->image_path) {
                $this->deletePublicFile($slide->image_path);
                $slide->image_path = null;
            }

            if ($request->hasFile("slides.$index.image_file")) {
                $file = $request->file("slides.$index.image_file");
                if ($file) {
                    if ($slide->image_path) {
                        $this->deletePublicFile($slide->image_path);
                    }
                    $slide->image_path = $file->store('website/carousel', 'public');
                }
            }

            $wasExisting = $slide->exists;
            $slide->save();

            if ($wasExisting) {
                $updated++;
            } else {
                $created++;
            }
        }

        return [
            'slides_created' => $created,
            'slides_updated' => $updated,
            'slides_deleted' => $deleted,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $brandsPayload
     * @return array<string, int>
     */
    private function syncBrands(Request $request, array $brandsPayload): array
    {
        $created = 0;
        $updated = 0;
        $deleted = 0;

        foreach ($brandsPayload as $index => $row) {
            $brand = ! empty($row['id']) ? FeaturedBrand::query()->find((int) $row['id']) : null;
            if (! $brand && ! empty($row['id'])) {
                continue;
            }

            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($delete) {
                if ($brand) {
                    $this->deletePublicFile($brand->logo_path);
                    $brand->delete();
                    $deleted++;
                }
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $logoUrl = trim((string) ($row['logo_url'] ?? ''));
            $sortOrder = isset($row['sort_order']) ? (int) $row['sort_order'] : ($index + 1);
            $isActive = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $removeLogo = filter_var($row['remove_logo'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $hasAnyContent = $name !== '' || $logoUrl !== '' || $request->hasFile("brands.$index.logo_file") || $brand !== null;
            if (! $hasAnyContent) {
                continue;
            }

            $brand ??= new FeaturedBrand();

            $brand->name = $name;
            $brand->logo_url = $logoUrl !== '' ? $logoUrl : null;
            $brand->sort_order = max(1, $sortOrder);
            $brand->is_active = $isActive;

            if ($removeLogo && $brand->logo_path) {
                $this->deletePublicFile($brand->logo_path);
                $brand->logo_path = null;
            }

            if ($request->hasFile("brands.$index.logo_file")) {
                $file = $request->file("brands.$index.logo_file");
                if ($file) {
                    if ($brand->logo_path) {
                        $this->deletePublicFile($brand->logo_path);
                    }
                    $brand->logo_path = $file->store('website/brands', 'public');
                }
            }

            $wasExisting = $brand->exists;
            $brand->save();

            if ($wasExisting) {
                $updated++;
            } else {
                $created++;
            }
        }

        return [
            'brands_created' => $created,
            'brands_updated' => $updated,
            'brands_deleted' => $deleted,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $bundleAdsPayload
     * @return array<string, int>
     */
    private function syncBundleAds(array $bundleAdsPayload, string $bundleType): array
    {
        $created = 0;
        $updated = 0;
        $deleted = 0;

        foreach ($bundleAdsPayload as $index => $row) {
            $bundleAd = ! empty($row['id']) ? BundleAd::query()->find((int) $row['id']) : null;
            if (! $bundleAd && ! empty($row['id'])) {
                continue;
            }

            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($delete) {
                if ($bundleAd) {
                    $bundleAd->delete();
                    $deleted++;
                }
                continue;
            }

            $imageUrl = trim((string) ($row['image_url'] ?? ''));
            $linkUrl = trim((string) ($row['link_url'] ?? ''));
            $sortOrder = isset($row['sort_order']) ? (int) $row['sort_order'] : ($index + 1);
            $isActive = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $hasAnyContent = $imageUrl !== '' || $linkUrl !== '' || $bundleAd !== null;
            if (! $hasAnyContent) {
                continue;
            }

            $bundleAd ??= new BundleAd();

            $bundleAd->bundle_type = $bundleType;
            $bundleAd->image_url = $imageUrl !== '' ? $imageUrl : null;
            $bundleAd->link_url = $linkUrl !== '' ? $linkUrl : null;
            $bundleAd->sort_order = max(1, $sortOrder);
            $bundleAd->is_active = $isActive;

            $wasExisting = $bundleAd->exists;
            $bundleAd->save();

            if ($wasExisting) {
                $updated++;
            } else {
                $created++;
            }
        }

        return [
            'bundle_ads_created' => $created,
            'bundle_ads_updated' => $updated,
            'bundle_ads_deleted' => $deleted,
        ];
    }

    private function deletePublicFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function forgetPublicLandingCaches(): void
    {
        Cache::forget('public_landing_data_v1');
        Cache::forget('public_landing_data_v2');
    }

    private function normalizeImageUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return $url;
        }

        $host = Str::lower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (in_array($host, ['drive.google.com', 'www.drive.google.com'], true)) {
            if (preg_match('#/file/d/([^/]+)#', $path, $matches) === 1) {
                return 'https://drive.google.com/uc?export=view&id='.$matches[1];
            }
        }

        if (in_array($host, ['dropbox.com', 'www.dropbox.com'], true)) {
            $query = [];
            parse_str((string) ($parts['query'] ?? ''), $query);
            $query['raw'] = '1';
            $normalizedQuery = http_build_query($query);

            return 'https://www.dropbox.com'.$path.($normalizedQuery !== '' ? '?'.$normalizedQuery : '');
        }

        if (in_array($host, ['imgur.com', 'www.imgur.com', 'm.imgur.com'], true)) {
            $trimmedPath = trim($path, '/');
            if (preg_match('/^([A-Za-z0-9]+)\.(jpg|jpeg|png|webp|gif)$/i', $trimmedPath, $matches) === 1) {
                return 'https://i.imgur.com/'.$matches[1].'.'.Str::lower($matches[2]);
            }
        }

        return $url;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSlideRows(Collection $slides, int $maxRows): array
    {
        $rows = $slides
            ->map(fn (CarouselSlide $slide) => [
                'id' => $slide->id,
                'title' => $slide->title,
                'subtitle' => $slide->subtitle,
                'label' => $slide->label,
                'image_url' => $slide->image_url,
                'is_active' => $slide->is_active,
                'sort_order' => $slide->sort_order,
            ])
            ->values()
            ->all();

        while (count($rows) < $maxRows) {
            $rows[] = [
                'id' => null,
                'title' => '',
                'subtitle' => '',
                'label' => '',
                'image_url' => '',
                'is_active' => true,
                'sort_order' => count($rows) + 1,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBrandRows(Collection $brands, int $maxRows): array
    {
        $rows = $brands
            ->map(fn (FeaturedBrand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo_url' => $brand->logo_url,
                'is_active' => $brand->is_active,
                'sort_order' => $brand->sort_order,
            ])
            ->values()
            ->all();

        while (count($rows) < $maxRows) {
            $rows[] = [
                'id' => null,
                'name' => '',
                'logo_url' => '',
                'is_active' => true,
                'sort_order' => count($rows) + 1,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBundleRows(Collection $bundleAds, int $maxRows): array
    {
        $rows = $bundleAds
            ->map(fn (BundleAd $bundleAd) => [
                'id' => $bundleAd->id,
                'image_url' => $bundleAd->image_url,
                'link_url' => $bundleAd->link_url,
                'is_active' => $bundleAd->is_active,
                'sort_order' => $bundleAd->sort_order,
            ])
            ->values()
            ->all();

        while (count($rows) < $maxRows) {
            $rows[] = [
                'id' => null,
                'image_url' => '',
                'link_url' => '',
                'is_active' => true,
                'sort_order' => count($rows) + 1,
            ];
        }

        return $rows;
    }
}
