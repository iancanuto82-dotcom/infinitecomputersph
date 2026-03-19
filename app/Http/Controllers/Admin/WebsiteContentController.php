<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BundleAd;
use App\Models\CarouselSlide;
use App\Models\FeaturedBuild;
use App\Models\FeaturedBrand;
use App\Models\WebsiteReview;
use App\Support\AuditLogger;
use App\Support\PublicCatalogCache;
use App\Support\PublicMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    private const MAX_FEATURED_BUILDS = 18;
    private const MAX_FEATURED_BUILD_GALLERY_IMAGES = 4;
    private const MAX_REVIEWS = 12;

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

        $reviews = WebsiteReview::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $featuredBuilds = FeaturedBuild::query()
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
            'featuredBuildRows' => $this->buildFeaturedBuildRows($featuredBuilds, self::MAX_FEATURED_BUILDS),
            'reviewRows' => $this->buildReviewRows($reviews, self::MAX_REVIEWS),
            'slideImageSrcMap' => $slides
                ->mapWithKeys(fn (CarouselSlide $slide) => [(string) $slide->id => $slide->image_src])
                ->all(),
            'brandLogoSrcMap' => $brands
                ->mapWithKeys(fn (FeaturedBrand $brand) => [(string) $brand->id => $brand->logo_src])
                ->all(),
            'bundleImageSrcMap' => $bundleAds
                ->mapWithKeys(fn (BundleAd $bundleAd) => [(string) $bundleAd->id => $bundleAd->image_src])
                ->all(),
            'featuredBuildImageSrcMap' => $featuredBuilds
                ->mapWithKeys(fn (FeaturedBuild $featuredBuild) => [(string) $featuredBuild->id => $featuredBuild->image_src])
                ->all(),
            'maxSlides' => self::MAX_SLIDES,
            'maxBrands' => self::MAX_BRANDS,
            'maxBundleAdsPerType' => self::MAX_BUNDLE_ADS_PER_TYPE,
            'maxFeaturedBuilds' => self::MAX_FEATURED_BUILDS,
            'maxReviews' => self::MAX_REVIEWS,
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
            'entry_bundle_ads.*.image_file' => ['nullable', 'image', 'max:5120'],
            'entry_bundle_ads.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'entry_bundle_ads.*.is_active' => ['nullable', 'boolean'],
            'entry_bundle_ads.*._delete' => ['nullable', 'boolean'],

            'gaming_bundle_ads' => ['nullable', 'array', 'max:'.self::MAX_BUNDLE_ADS_PER_TYPE],
            'gaming_bundle_ads.*.id' => ['nullable', 'integer', 'exists:bundle_ads,id'],
            'gaming_bundle_ads.*.image_url' => ['nullable', 'url', 'max:2048'],
            'gaming_bundle_ads.*.image_file' => ['nullable', 'image', 'max:5120'],
            'gaming_bundle_ads.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'gaming_bundle_ads.*.is_active' => ['nullable', 'boolean'],
            'gaming_bundle_ads.*._delete' => ['nullable', 'boolean'],

            'featured_builds' => ['nullable', 'array', 'max:'.self::MAX_FEATURED_BUILDS],
            'featured_builds.*.id' => ['nullable', 'integer', 'exists:featured_builds,id'],
            'featured_builds.*.title' => ['nullable', 'string', 'max:180'],
            'featured_builds.*.image_file' => ['nullable', 'image', 'max:15360'],
            'featured_builds.*.gallery_files' => ['nullable', 'array', 'max:'.self::MAX_FEATURED_BUILD_GALLERY_IMAGES],
            'featured_builds.*.gallery_files.*' => ['nullable', 'image', 'max:15360'],
            'featured_builds.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'featured_builds.*.is_active' => ['nullable', 'boolean'],
            'featured_builds.*.remove_image' => ['nullable', 'boolean'],
            'featured_builds.*._delete' => ['nullable', 'boolean'],

            'reviews' => ['nullable', 'array', 'max:'.self::MAX_REVIEWS],
            'reviews.*.id' => ['nullable', 'integer', 'exists:website_reviews,id'],
            'reviews.*.title' => ['nullable', 'string', 'max:180'],
            'reviews.*.content' => ['nullable', 'string', 'max:2000'],
            'reviews.*.author_name' => ['nullable', 'string', 'max:120'],
            'reviews.*.rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'reviews.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'reviews.*.is_active' => ['nullable', 'boolean'],
            'reviews.*._delete' => ['nullable', 'boolean'],
        ]);

        $slidesPayload = (array) ($validated['slides'] ?? []);
        $brandsPayload = (array) ($validated['brands'] ?? []);
        $entryBundleAdsPayload = (array) ($validated['entry_bundle_ads'] ?? []);
        $gamingBundleAdsPayload = (array) ($validated['gaming_bundle_ads'] ?? []);
        $featuredBuildsPayload = (array) ($validated['featured_builds'] ?? []);
        $reviewsPayload = (array) ($validated['reviews'] ?? []);

        $errors = [];
        $this->validateContentRows(
            $request,
            $slidesPayload,
            $brandsPayload,
            $entryBundleAdsPayload,
            $gamingBundleAdsPayload,
            $featuredBuildsPayload,
            $reviewsPayload,
            $errors
        );

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $summary = DB::transaction(function () use ($request, $slidesPayload, $brandsPayload, $entryBundleAdsPayload, $gamingBundleAdsPayload, $featuredBuildsPayload, $reviewsPayload): array {
            return [
                'slides' => $this->syncSlides($request, $slidesPayload),
                'brands' => $this->syncBrands($request, $brandsPayload),
                'bundle_ads' => [
                    'entry' => $this->syncBundleAds($request, $entryBundleAdsPayload, 'entry'),
                    'gaming' => $this->syncBundleAds($request, $gamingBundleAdsPayload, 'gaming'),
                ],
                'featured_builds' => $this->syncFeaturedBuilds($request, $featuredBuildsPayload),
                'reviews' => $this->syncReviews($reviewsPayload),
            ];
        });

        AuditLogger::record(
            $request,
            'updated',
            'website_content',
            null,
            'Pricelist content',
            'Updated carousel, featured brands, bundle ads, featured builds, and reviews.',
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
     * @param array<int, array<string, mixed>> $featuredBuildsPayload
     * @param array<int, array<string, mixed>> $reviewsPayload
     * @param array<string, string> $errors
     */
    private function validateContentRows(
        Request $request,
        array $slidesPayload,
        array $brandsPayload,
        array $entryBundleAdsPayload,
        array $gamingBundleAdsPayload,
        array $featuredBuildsPayload,
        array $reviewsPayload,
        array &$errors
    ): void
    {
        foreach ($slidesPayload as $index => $row) {
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $imageUrl = $this->normalizeImageUrl(trim((string) ($row['image_url'] ?? '')));
            $hasFile = $request->hasFile("slides.$index.image_file");

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
            $hasFile = $request->hasFile("brands.$index.logo_file");

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
            $hasFile = $request->hasFile("entry_bundle_ads.$index.image_file");
            $bundleAd = ! empty($row['id']) ? BundleAd::query()->find((int) $row['id']) : null;
            $hasExistingImage = $bundleAd && (trim((string) $bundleAd->image_url) !== '' || trim((string) $bundleAd->image_path) !== '');

            if ($delete) {
                continue;
            }

            $hasAnyContent = $imageUrl !== '' || $hasFile || ! empty($row['id']);
            if (! $hasAnyContent) {
                continue;
            }

            if ($imageUrl === '' && ! $hasFile && ! $hasExistingImage) {
                $errors["entry_bundle_ads.$index.image_url"] = 'Image upload or image URL is required.';
            }
        }

        foreach ($gamingBundleAdsPayload as $index => $row) {
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $imageUrl = trim((string) ($row['image_url'] ?? ''));
            $hasFile = $request->hasFile("gaming_bundle_ads.$index.image_file");
            $bundleAd = ! empty($row['id']) ? BundleAd::query()->find((int) $row['id']) : null;
            $hasExistingImage = $bundleAd && (trim((string) $bundleAd->image_url) !== '' || trim((string) $bundleAd->image_path) !== '');

            if ($delete) {
                continue;
            }

            $hasAnyContent = $imageUrl !== '' || $hasFile || ! empty($row['id']);
            if (! $hasAnyContent) {
                continue;
            }

            if ($imageUrl === '' && ! $hasFile && ! $hasExistingImage) {
                $errors["gaming_bundle_ads.$index.image_url"] = 'Image upload or image URL is required.';
            }
        }

        foreach ($featuredBuildsPayload as $index => $row) {
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $title = trim((string) ($row['title'] ?? ''));
            $hasPrimaryFile = $request->hasFile("featured_builds.$index.image_file");
            $hasGalleryFile = $this->hasFeaturedBuildGalleryUpload($request, $index);
            $featuredBuild = ! empty($row['id']) ? FeaturedBuild::query()->find((int) $row['id']) : null;
            $hasExistingImage = $featuredBuild
                && (
                    trim((string) $featuredBuild->image_url) !== ''
                    || trim((string) $featuredBuild->image_path) !== ''
                    || count((array) ($featuredBuild->gallery_images ?? [])) > 0
                );

            if ($delete) {
                continue;
            }

            $hasAnyContent = $title !== '' || $hasPrimaryFile || $hasGalleryFile || ! empty($row['id']);
            if (! $hasAnyContent) {
                continue;
            }

            if (! $hasPrimaryFile && ! $hasGalleryFile && ! $hasExistingImage) {
                $errors["featured_builds.$index.image_file"] = 'Primary image upload or at least one gallery image upload is required.';
            }
        }

        foreach ($reviewsPayload as $index => $row) {
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $title = trim((string) ($row['title'] ?? ''));
            $content = trim((string) ($row['content'] ?? ''));
            $authorName = trim((string) ($row['author_name'] ?? ''));

            if ($delete) {
                continue;
            }

            $hasAnyContent = $title !== ''
                || $content !== ''
                || $authorName !== ''
                || ! empty($row['id']);

            if (! $hasAnyContent) {
                continue;
            }

            if ($title === '') {
                $errors["reviews.$index.title"] = 'Review title is required.';
            }

            if ($content === '') {
                $errors["reviews.$index.content"] = 'Review text is required.';
            }

            if ($authorName === '') {
                $errors["reviews.$index.author_name"] = 'Reviewer name is required.';
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
                    $slide->image_path = $file->store('website/carousel', PublicMedia::diskName());
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
                    $brand->logo_path = $file->store('website/brands', PublicMedia::diskName());
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
    private function syncBundleAds(Request $request, array $bundleAdsPayload, string $bundleType): array
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

            $imageUrl = $this->normalizeImageUrl(trim((string) ($row['image_url'] ?? '')));
            $sortOrder = isset($row['sort_order']) ? (int) $row['sort_order'] : ($index + 1);
            $isActive = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $hasFile = $request->hasFile("{$bundleType}_bundle_ads.$index.image_file");

            $hasAnyContent = $imageUrl !== '' || $hasFile || $bundleAd !== null;
            if (! $hasAnyContent) {
                continue;
            }

            $bundleAd ??= new BundleAd();

            $bundleAd->bundle_type = $bundleType;
            if ($hasFile) {
                $file = $request->file("{$bundleType}_bundle_ads.$index.image_file");
                if ($file) {
                    if ($bundleAd->image_path) {
                        $this->deletePublicFile($bundleAd->image_path);
                    }
                    $bundleAd->image_path = $file->store('website/bundles', PublicMedia::diskName());
                }
            } elseif ($imageUrl !== '') {
                if ($bundleAd->image_path) {
                    $this->deletePublicFile($bundleAd->image_path);
                    $bundleAd->image_path = null;
                }
            }

            if ($imageUrl !== '') {
                $bundleAd->image_url = $imageUrl;
            } elseif ($hasFile) {
                $bundleAd->image_url = null;
            }

            $bundleAd->link_url = null;
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

    /**
     * @param array<int, array<string, mixed>> $featuredBuildsPayload
     * @return array<string, int>
     */
    private function syncFeaturedBuilds(Request $request, array $featuredBuildsPayload): array
    {
        $created = 0;
        $updated = 0;
        $deleted = 0;

        foreach ($featuredBuildsPayload as $index => $row) {
            $featuredBuild = ! empty($row['id']) ? FeaturedBuild::query()->find((int) $row['id']) : null;
            if (! $featuredBuild && ! empty($row['id'])) {
                continue;
            }

            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($delete) {
                if ($featuredBuild) {
                    $this->deletePublicFile($featuredBuild->image_path);
                    $this->deleteFeaturedBuildGalleryFiles((array) ($featuredBuild->gallery_images ?? []));
                    $featuredBuild->delete();
                    $deleted++;
                }
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $galleryImages = $this->resolveFeaturedBuildGalleryImages($request, $index, $featuredBuild);
            $sortOrder = isset($row['sort_order']) ? (int) $row['sort_order'] : ($index + 1);
            $isActive = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $removeImage = filter_var($row['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $hasAnyContent = $title !== ''
                || count($galleryImages) > 0
                || $request->hasFile("featured_builds.$index.image_file")
                || $featuredBuild !== null;
            if (! $hasAnyContent) {
                continue;
            }

            $featuredBuild ??= new FeaturedBuild();
            $featuredBuild->title = $title !== '' ? $title : null;
            $featuredBuild->image_url = null;
            $featuredBuild->gallery_images = $galleryImages !== [] ? $galleryImages : null;
            $featuredBuild->sort_order = max(1, $sortOrder);
            $featuredBuild->is_active = $isActive;

            if ($removeImage && $featuredBuild->image_path) {
                $this->deletePublicFile($featuredBuild->image_path);
                $featuredBuild->image_path = null;
            }

            if ($request->hasFile("featured_builds.$index.image_file")) {
                $file = $request->file("featured_builds.$index.image_file");
                if ($file) {
                    if ($featuredBuild->image_path) {
                        $this->deletePublicFile($featuredBuild->image_path);
                    }
                    $featuredBuild->image_path = $file->store('website/featured-builds', PublicMedia::diskName());
                }
            }

            $wasExisting = $featuredBuild->exists;
            $featuredBuild->save();

            if ($wasExisting) {
                $updated++;
            } else {
                $created++;
            }
        }

        return [
            'featured_builds_created' => $created,
            'featured_builds_updated' => $updated,
            'featured_builds_deleted' => $deleted,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $reviewsPayload
     * @return array<string, int>
     */
    private function syncReviews(array $reviewsPayload): array
    {
        $created = 0;
        $updated = 0;
        $deleted = 0;

        foreach ($reviewsPayload as $index => $row) {
            $review = ! empty($row['id']) ? WebsiteReview::query()->find((int) $row['id']) : null;
            if (! $review && ! empty($row['id'])) {
                continue;
            }

            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($delete) {
                if ($review) {
                    $review->delete();
                    $deleted++;
                }
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $content = trim((string) ($row['content'] ?? ''));
            $authorName = trim((string) ($row['author_name'] ?? ''));
            $rating = isset($row['rating']) ? (int) $row['rating'] : 5;
            $sortOrder = isset($row['sort_order']) ? (int) $row['sort_order'] : ($index + 1);
            $isActive = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $hasAnyContent = $title !== '' || $content !== '' || $authorName !== '' || $review !== null;
            if (! $hasAnyContent) {
                continue;
            }

            $review ??= new WebsiteReview();

            $review->title = $title;
            $review->content = $content;
            $review->author_name = $authorName;
            $review->rating = max(1, min(5, $rating));
            $review->sort_order = max(1, $sortOrder);
            $review->is_active = $isActive;

            $wasExisting = $review->exists;
            $review->save();

            if ($wasExisting) {
                $updated++;
            } else {
                $created++;
            }
        }

        return [
            'reviews_created' => $created,
            'reviews_updated' => $updated,
            'reviews_deleted' => $deleted,
        ];
    }

    private function deletePublicFile(?string $path): void
    {
        if ($path) {
            Storage::disk(PublicMedia::diskName())->delete($path);
        }
    }

    private function hasFeaturedBuildGalleryUpload(Request $request, int $index): bool
    {
        for ($galleryIndex = 0; $galleryIndex < self::MAX_FEATURED_BUILD_GALLERY_IMAGES; $galleryIndex++) {
            if ($request->hasFile("featured_builds.$index.gallery_files.$galleryIndex")) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function resolveFeaturedBuildGalleryImages(Request $request, int $index, ?FeaturedBuild $featuredBuild): array
    {
        $galleryImages = collect((array) ($featuredBuild?->gallery_images ?? []))
            ->map(fn ($image) => trim((string) $image))
            ->take(self::MAX_FEATURED_BUILD_GALLERY_IMAGES)
            ->values()
            ->all();

        while (count($galleryImages) < self::MAX_FEATURED_BUILD_GALLERY_IMAGES) {
            $galleryImages[] = '';
        }

        for ($galleryIndex = 0; $galleryIndex < self::MAX_FEATURED_BUILD_GALLERY_IMAGES; $galleryIndex++) {
            if (! $request->hasFile("featured_builds.$index.gallery_files.$galleryIndex")) {
                continue;
            }

            $file = $request->file("featured_builds.$index.gallery_files.$galleryIndex");
            if (! $file) {
                continue;
            }

            $oldImage = trim((string) ($galleryImages[$galleryIndex] ?? ''));
            if ($oldImage !== '' && ! $this->isRemoteImageSource($oldImage)) {
                $this->deletePublicFile($oldImage);
            }

            $galleryImages[$galleryIndex] = $file->store('website/featured-builds/gallery', PublicMedia::diskName());
        }

        return collect($galleryImages)
            ->map(fn ($image) => trim((string) $image))
            ->filter(fn (string $image) => $image !== '')
            ->take(self::MAX_FEATURED_BUILD_GALLERY_IMAGES)
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $galleryImages
     */
    private function deleteFeaturedBuildGalleryFiles(array $galleryImages): void
    {
        collect($galleryImages)
            ->map(fn ($image) => trim((string) $image))
            ->filter(fn (string $image) => $image !== '')
            ->filter(fn (string $image) => ! $this->isRemoteImageSource($image))
            ->each(fn (string $image) => $this->deletePublicFile($image));
    }

    private function isRemoteImageSource(string $value): bool
    {
        return Str::startsWith(Str::lower(trim($value)), ['http://', 'https://']);
    }

    private function forgetPublicLandingCaches(): void
    {
        PublicCatalogCache::forgetLanding();
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
     * @param array<int, mixed> $images
     * @return array<int, string>
     */
    private function normalizeGalleryImages(array $images): array
    {
        return collect($images)
            ->map(fn ($image) => $this->normalizeImageUrl(trim((string) $image)))
            ->filter(fn (string $image) => $image !== '')
            ->unique()
            ->take(self::MAX_FEATURED_BUILD_GALLERY_IMAGES)
            ->values()
            ->all();
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFeaturedBuildRows(Collection $featuredBuilds, int $maxRows): array
    {
        $rows = $featuredBuilds
            ->map(fn (FeaturedBuild $featuredBuild) => [
                'id' => $featuredBuild->id,
                'title' => $featuredBuild->title,
                'gallery_images' => collect((array) ($featuredBuild->gallery_image_src_list ?? []))
                    ->take(self::MAX_FEATURED_BUILD_GALLERY_IMAGES)
                    ->values()
                    ->all(),
                'is_active' => $featuredBuild->is_active,
                'sort_order' => $featuredBuild->sort_order,
            ])
            ->values()
            ->all();

        while (count($rows) < $maxRows) {
            $rows[] = [
                'id' => null,
                'title' => '',
                'gallery_images' => [],
                'is_active' => true,
                'sort_order' => count($rows) + 1,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildReviewRows(Collection $reviews, int $maxRows): array
    {
        $rows = $reviews
            ->map(fn (WebsiteReview $review) => [
                'id' => $review->id,
                'title' => $review->title,
                'content' => $review->content,
                'author_name' => $review->author_name,
                'rating' => $review->rating,
                'is_active' => $review->is_active,
                'sort_order' => $review->sort_order,
            ])
            ->values()
            ->all();

        while (count($rows) < $maxRows) {
            $rows[] = [
                'id' => null,
                'title' => '',
                'content' => '',
                'author_name' => '',
                'rating' => 5,
                'is_active' => true,
                'sort_order' => count($rows) + 1,
            ];
        }

        return $rows;
    }
}
