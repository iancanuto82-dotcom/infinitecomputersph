<x-app-layout>
    @php
        $displaySlides = is_array(old('slides')) ? old('slides') : $slideRows;
        $displayBrands = is_array(old('brands')) ? old('brands') : $brandRows;
        $displayEntryBundleAds = is_array(old('entry_bundle_ads')) ? old('entry_bundle_ads') : $entryBundleAdRows;
        $displayGamingBundleAds = is_array(old('gaming_bundle_ads')) ? old('gaming_bundle_ads') : $gamingBundleAdRows;
        $displayFeaturedBuilds = is_array(old('featured_builds')) ? old('featured_builds') : $featuredBuildRows;
        $displayReviews = is_array(old('reviews')) ? old('reviews') : $reviewRows;

        $lastSlideIndex = collect($displaySlides)->reduce(function ($last, $row, $idx) {
            $hasContent = ! empty($row['id'])
                || trim((string) ($row['image_url'] ?? '')) !== '';
            return $hasContent ? $idx : $last;
        }, -1);
        $initialSlideRows = min($maxSlides, max(1, $lastSlideIndex + 1));

        $lastBrandIndex = collect($displayBrands)->reduce(function ($last, $row, $idx) {
            $hasContent = ! empty($row['id'])
                || trim((string) ($row['name'] ?? '')) !== ''
                || trim((string) ($row['logo_url'] ?? '')) !== '';
            return $hasContent ? $idx : $last;
        }, -1);
        $initialBrandRows = min($maxBrands, max(1, $lastBrandIndex + 1));

        $lastEntryBundleIndex = collect($displayEntryBundleAds)->reduce(function ($last, $row, $idx) {
            $hasContent = ! empty($row['id'])
                || trim((string) ($row['image_url'] ?? '')) !== '';
            return $hasContent ? $idx : $last;
        }, -1);
        $initialEntryBundleRows = min($maxBundleAdsPerType, max(1, $lastEntryBundleIndex + 1));
        $entryBundleCount = collect($displayEntryBundleAds)->filter(function ($row) {
            return ! empty($row['id'])
                || trim((string) ($row['image_url'] ?? '')) !== '';
        })->count();

        $lastGamingBundleIndex = collect($displayGamingBundleAds)->reduce(function ($last, $row, $idx) {
            $hasContent = ! empty($row['id'])
                || trim((string) ($row['image_url'] ?? '')) !== '';
            return $hasContent ? $idx : $last;
        }, -1);
        $initialGamingBundleRows = min($maxBundleAdsPerType, max(1, $lastGamingBundleIndex + 1));
        $gamingBundleCount = collect($displayGamingBundleAds)->filter(function ($row) {
            return ! empty($row['id'])
                || trim((string) ($row['image_url'] ?? '')) !== '';
        })->count();

        $lastFeaturedBuildIndex = collect($displayFeaturedBuilds)->reduce(function ($last, $row, $idx) {
            $hasGalleryImage = collect((array) ($row['gallery_images'] ?? []))
                ->contains(fn ($image) => trim((string) $image) !== '');
            $hasContent = ! empty($row['id'])
                || trim((string) ($row['title'] ?? '')) !== ''
                || trim((string) ($row['image_url'] ?? '')) !== ''
                || $hasGalleryImage;
            return $hasContent ? $idx : $last;
        }, -1);
        $initialFeaturedBuildRows = min($maxFeaturedBuilds, max(1, $lastFeaturedBuildIndex + 1));
        $featuredBuildCount = collect($displayFeaturedBuilds)->filter(function ($row) {
            $hasGalleryImage = collect((array) ($row['gallery_images'] ?? []))
                ->contains(fn ($image) => trim((string) $image) !== '');
            return ! empty($row['id'])
                || trim((string) ($row['title'] ?? '')) !== ''
                || trim((string) ($row['image_url'] ?? '')) !== ''
                || $hasGalleryImage;
        })->count();

        $lastReviewIndex = collect($displayReviews)->reduce(function ($last, $row, $idx) {
            $hasContent = ! empty($row['id'])
                || trim((string) ($row['title'] ?? '')) !== ''
                || trim((string) ($row['content'] ?? '')) !== ''
                || trim((string) ($row['author_name'] ?? '')) !== '';
            return $hasContent ? $idx : $last;
        }, -1);
        $initialReviewRows = min($maxReviews, max(1, $lastReviewIndex + 1));
        $reviewCount = collect($displayReviews)->filter(function ($row) {
            return ! empty($row['id'])
                || trim((string) ($row['title'] ?? '')) !== ''
                || trim((string) ($row['content'] ?? '')) !== ''
                || trim((string) ($row['author_name'] ?? '')) !== '';
        })->count();

        $errorKeys = array_keys($errors->getMessages());
        $hasSlideErrors = collect($errorKeys)->contains(fn ($key) => str_starts_with((string) $key, 'slides.'));
        $hasBrandErrors = collect($errorKeys)->contains(fn ($key) => str_starts_with((string) $key, 'brands.'));
        $hasBundleErrors = collect($errorKeys)->contains(function ($key) {
            $errorKey = (string) $key;
            return str_starts_with($errorKey, 'entry_bundle_ads.')
                || str_starts_with($errorKey, 'gaming_bundle_ads.');
        });
        $hasFeaturedBuildErrors = collect($errorKeys)->contains(fn ($key) => str_starts_with((string) $key, 'featured_builds.'));
        $hasReviewErrors = collect($errorKeys)->contains(fn ($key) => str_starts_with((string) $key, 'reviews.'));

        $initialContentTab = old('_content_tab');
        if (! in_array($initialContentTab, ['slides', 'brands', 'bundles', 'featured_builds', 'reviews'], true)) {
            $initialContentTab = $hasSlideErrors
                ? 'slides'
                : ($hasBrandErrors ? 'brands' : ($hasBundleErrors ? 'bundles' : ($hasFeaturedBuildErrors ? 'featured_builds' : ($hasReviewErrors ? 'reviews' : 'slides'))));
        }
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Website Content</h2>
                <p class="mt-1 text-sm text-gray-600">Edit pricelist carousel, featured brands, bundle ads, featured builds, and customer reviews.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="POST" action="{{ route('admin.content.update') }}" enctype="multipart/form-data" class="space-y-6"
                x-data="{
                    activeTab: @js($initialContentTab),
                    confirmDeleteOpen: false,
                    pendingDeleteCheckbox: null,
                    pendingDeleteLabel: '',
                    requestDeleteConfirm(event, label) {
                        const checkbox = event?.target;
                        if (!checkbox || !checkbox.checked) return;
                        this.pendingDeleteCheckbox = checkbox;
                        this.pendingDeleteLabel = String(label || 'this item');
                        checkbox.checked = false;
                        this.confirmDeleteOpen = true;
                    },
                    confirmDelete() {
                        if (this.pendingDeleteCheckbox) {
                            this.pendingDeleteCheckbox.checked = true;
                        }
                        this.closeDeleteConfirm();
                    },
                    closeDeleteConfirm() {
                        this.confirmDeleteOpen = false;
                        this.pendingDeleteCheckbox = null;
                        this.pendingDeleteLabel = '';
                    },
                }"
                @keydown.escape.window="closeDeleteConfirm()">
                @csrf
                @method('PUT')
                <input type="hidden" name="_content_tab" x-model="activeTab">

                <div class="rounded-xl bg-white p-2 shadow-sm ring-1 ring-black/10">
                    <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Website content sections">
                        <button type="button" role="tab"
                            @click="activeTab = 'slides'"
                            :aria-selected="activeTab === 'slides'"
                            class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium ring-1 transition"
                            :class="activeTab === 'slides' ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-700 ring-black/10 hover:bg-gray-50'">
                            Carousel Slides
                        </button>
                        <button type="button" role="tab"
                            @click="activeTab = 'brands'"
                            :aria-selected="activeTab === 'brands'"
                            class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium ring-1 transition"
                            :class="activeTab === 'brands' ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-700 ring-black/10 hover:bg-gray-50'">
                            Featured Brands
                        </button>
                        <button type="button" role="tab"
                            @click="activeTab = 'bundles'"
                            :aria-selected="activeTab === 'bundles'"
                            class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium ring-1 transition"
                            :class="activeTab === 'bundles' ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-700 ring-black/10 hover:bg-gray-50'">
                            Bundle Ads
                        </button>
                        <button type="button" role="tab"
                            @click="activeTab = 'featured_builds'"
                            :aria-selected="activeTab === 'featured_builds'"
                            class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium ring-1 transition"
                            :class="activeTab === 'featured_builds' ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-700 ring-black/10 hover:bg-gray-50'">
                            Featured Builds
                        </button>
                        <button type="button" role="tab"
                            @click="activeTab = 'reviews'"
                            :aria-selected="activeTab === 'reviews'"
                            class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium ring-1 transition"
                            :class="activeTab === 'reviews' ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-700 ring-black/10 hover:bg-gray-50'">
                            Reviews
                        </button>
                    </div>
                </div>

                <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10"
                    x-cloak
                    x-show="activeTab === 'slides'"
                    x-transition.opacity.duration.150ms
                    x-data="{ visibleRows: {{ $initialSlideRows }}, maxRows: {{ $maxSlides }} }">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Carousel Slides</h3>
                            <p class="mt-1 text-xs text-gray-600">
                                Ideal image size: <strong>1920 x 720 px</strong> (8:3 ratio). Minimum suggested: 1600 x 600 px.
                            </p>
                            <p class="text-xs text-gray-600">
                                Image-only slides. Use image URL or upload. Upload takes priority. Max file size: 5MB (JPG, PNG, WebP, GIF).
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800"
                                @click="if (visibleRows < maxRows) visibleRows++">
                                Add Slide
                            </button>
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-800 ring-1 ring-black/10 hover:bg-gray-200"
                                @click="if (visibleRows > 1) visibleRows--">
                                Hide last row
                            </button>
                            <div class="text-xs text-gray-600" x-text="`Showing ${visibleRows} of ${maxRows}`"></div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-600">
                        Tip: <span class="font-medium">Add Slide</span>/<span class="font-medium">Hide last row</span> only changes visible form rows.
                        Use <span class="font-medium text-rose-700">Delete on save</span> below to permanently remove a saved slide.
                    </p>

                    <div class="mt-4 space-y-4">
                        @foreach ($displaySlides as $i => $row)
                            @php
                                $rowId = trim((string) ($row['id'] ?? ''));
                                $currentImageSrc = $rowId !== '' ? ($slideImageSrcMap[$rowId] ?? null) : null;
                            @endphp

                            <div class="rounded-lg border border-black/10 p-4" x-show="visibleRows > {{ $i }}">
                                <fieldset :disabled="visibleRows <= {{ $i }}">
                                <input type="hidden" name="slides[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                                <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-6">
                                    <div class="lg:col-span-1">
                                        <label class="block text-xs font-medium text-gray-700">Sort</label>
                                        <input type="number" min="1" max="999" name="slides[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? ($i + 1) }}"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-6">
                                    <div class="lg:col-span-3">
                                        <label class="block text-xs font-medium text-gray-700">Image URL</label>
                                        <input type="url" name="slides[{{ $i }}][image_url]" value="{{ $row['image_url'] ?? '' }}"
                                            placeholder="https://example.com/banner.jpg"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("slides.$i.image_url")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700">Upload image</label>
                                        <input type="file" accept="image/*" name="slides[{{ $i }}][image_file]"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("slides.$i.image_file")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-1 flex items-end">
                                        @if ($currentImageSrc)
                                            <a href="{{ $currentImageSrc }}" target="_blank" rel="noopener noreferrer"
                                                class="text-xs font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                                Current image
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="slides[{{ $i }}][is_active]" value="0">
                                        <input type="checkbox" name="slides[{{ $i }}][is_active]" value="1"
                                            {{ ! empty($row['is_active']) ? 'checked' : '' }}
                                            class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                        <span>Active</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="slides[{{ $i }}][remove_image]" value="0">
                                        <input type="checkbox" name="slides[{{ $i }}][remove_image]" value="1"
                                            {{ ! empty($row['remove_image']) ? 'checked' : '' }}
                                            class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                        <span>Remove uploaded image file</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-rose-700">
                                        <input type="hidden" name="slides[{{ $i }}][_delete]" value="0">
                                        <input type="checkbox" name="slides[{{ $i }}][_delete]" value="1"
                                            {{ ! empty($row['_delete']) ? 'checked' : '' }}
                                            @change="requestDeleteConfirm($event, 'slide #{{ $i + 1 }}')"
                                            class="rounded border-black/30 text-rose-700 focus:ring-rose-500">
                                        <span>Delete on save</span>
                                    </label>
                                </div>
                                </fieldset>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10"
                    x-cloak
                    x-show="activeTab === 'brands'"
                    x-transition.opacity.duration.150ms
                    x-data="{ visibleRows: {{ $initialBrandRows }}, maxRows: {{ $maxBrands }} }">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Featured Brands</h3>
                            <p class="mt-1 text-xs text-gray-600">
                                Ideal logo size: <strong>600 x 240 px</strong> (5:2 ratio), transparent PNG preferred.
                            </p>
                            <p class="text-xs text-gray-600">
                                Use logo URL or upload. Upload takes priority. Max file size: 5MB.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800"
                                @click="if (visibleRows < maxRows) visibleRows++">
                                Add Brand
                            </button>
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-800 ring-1 ring-black/10 hover:bg-gray-200"
                                @click="if (visibleRows > 1) visibleRows--">
                                Hide last row
                            </button>
                            <div class="text-xs text-gray-600" x-text="`Showing ${visibleRows} of ${maxRows}`"></div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-600">
                        Tip: <span class="font-medium">Add Brand</span>/<span class="font-medium">Hide last row</span> only changes visible form rows.
                        Use <span class="font-medium text-rose-700">Delete on save</span> below to permanently remove a saved brand.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-4">
                        @foreach ($displayBrands as $i => $row)
                            @php
                                $rowId = trim((string) ($row['id'] ?? ''));
                                $currentLogoSrc = $rowId !== '' ? ($brandLogoSrcMap[$rowId] ?? null) : null;
                            @endphp

                            <div class="rounded-lg border border-black/10 p-4" x-show="visibleRows > {{ $i }}">
                                <fieldset :disabled="visibleRows <= {{ $i }}">
                                <input type="hidden" name="brands[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-8">
                                    <div class="lg:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700">Brand name</label>
                                        <input type="text" name="brands[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("brands.$i.name")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700">Logo URL</label>
                                        <input type="url" name="brands[{{ $i }}][logo_url]" value="{{ $row['logo_url'] ?? '' }}"
                                            placeholder="https://example.com/logo.png"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("brands.$i.logo_url")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700">Upload logo</label>
                                        <input type="file" accept="image/*" name="brands[{{ $i }}][logo_file]"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("brands.$i.logo_file")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Sort</label>
                                        <input type="number" min="1" max="999" name="brands[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? ($i + 1) }}"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    </div>

                                    <div class="flex items-end">
                                        @if ($currentLogoSrc)
                                            <a href="{{ $currentLogoSrc }}" target="_blank" rel="noopener noreferrer"
                                                class="text-xs font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                                Current logo
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="brands[{{ $i }}][is_active]" value="0">
                                        <input type="checkbox" name="brands[{{ $i }}][is_active]" value="1"
                                            {{ ! empty($row['is_active']) ? 'checked' : '' }}
                                            class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                        <span>Active</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="brands[{{ $i }}][remove_logo]" value="0">
                                        <input type="checkbox" name="brands[{{ $i }}][remove_logo]" value="1"
                                            {{ ! empty($row['remove_logo']) ? 'checked' : '' }}
                                            class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                        <span>Remove uploaded logo file</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-rose-700">
                                        <input type="hidden" name="brands[{{ $i }}][_delete]" value="0">
                                        <input type="checkbox" name="brands[{{ $i }}][_delete]" value="1"
                                            {{ ! empty($row['_delete']) ? 'checked' : '' }}
                                            @change="requestDeleteConfirm($event, 'brand #{{ $i + 1 }}')"
                                            class="rounded border-black/30 text-rose-700 focus:ring-rose-500">
                                        <span>Delete on save</span>
                                    </label>
                                </div>
                                </fieldset>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10"
                    x-cloak
                    x-show="activeTab === 'bundles'"
                    x-transition.opacity.duration.150ms>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Bundle Ads</h3>
                            <p class="mt-1 text-xs text-gray-600">
                                Home page ad cards for Entry Build and Gaming Build sections.
                            </p>
                            <p class="text-xs text-gray-600">
                                Use either image URL or upload image (JPG, PNG, WebP, GIF, max 5MB).
                            </p>
                        </div>
                        <div class="text-right text-xs text-gray-600">
                            <div>Up to {{ $maxBundleAdsPerType }} ads per bundle type</div>
                            <div class="mt-1">
                                Entry: <span class="font-semibold text-gray-900">{{ $entryBundleCount }}</span>
                                &nbsp;|&nbsp;
                                Gaming: <span class="font-semibold text-gray-900">{{ $gamingBundleCount }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="rounded-lg border border-black/10 p-4"
                            x-data="{ visibleRows: {{ $initialEntryBundleRows }}, maxRows: {{ $maxBundleAdsPerType }} }">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-sm font-semibold text-gray-900">Entry Build Ads</h4>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="inline-flex items-center rounded-md bg-gray-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-gray-800"
                                        @click="if (visibleRows < maxRows) visibleRows++">
                                        Add row
                                    </button>
                                    <button type="button"
                                        class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-800 ring-1 ring-black/10 hover:bg-gray-200"
                                        @click="if (visibleRows > 1) visibleRows--">
                                        Hide last row
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-600">
                                Tip: <span class="font-medium">Add row</span>/<span class="font-medium">Hide last row</span> only changes visible form rows.
                                Use <span class="font-medium text-rose-700">Delete on save</span> below to permanently remove a saved ad.
                            </p>
                            <div class="mt-3 space-y-3">
                                @foreach ($displayEntryBundleAds as $i => $row)
                                    @php
                                        $rowId = trim((string) ($row['id'] ?? ''));
                                        $currentImageSrc = $rowId !== '' ? ($bundleImageSrcMap[$rowId] ?? null) : null;
                                    @endphp
                                    <div class="rounded-md border border-black/10 p-3" x-show="visibleRows > {{ $i }}">
                                        <fieldset :disabled="visibleRows <= {{ $i }}">
                                        <input type="hidden" name="entry_bundle_ads[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-medium text-gray-700">Image URL</label>
                                                <input type="url" name="entry_bundle_ads[{{ $i }}][image_url]" value="{{ $row['image_url'] ?? '' }}"
                                                    placeholder="https://example.com/entry-ad.jpg"
                                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                                @error("entry_bundle_ads.$i.image_url")
                                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-medium text-gray-700">Upload image</label>
                                                <input type="file" accept="image/*" name="entry_bundle_ads[{{ $i }}][image_file]"
                                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                                @error("entry_bundle_ads.$i.image_file")
                                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="flex items-end lg:col-span-2">
                                                @if ($currentImageSrc)
                                                    <a href="{{ $currentImageSrc }}" target="_blank" rel="noopener noreferrer"
                                                        class="text-xs font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                                        Current image
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                                            <div class="w-20">
                                                <label class="block text-xs font-medium text-gray-700">Sort</label>
                                                <input type="number" min="1" max="999" name="entry_bundle_ads[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? ($i + 1) }}"
                                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            </div>
                                            <label class="inline-flex items-center gap-2">
                                                <input type="hidden" name="entry_bundle_ads[{{ $i }}][is_active]" value="0">
                                                <input type="checkbox" name="entry_bundle_ads[{{ $i }}][is_active]" value="1"
                                                    {{ ! empty($row['is_active']) ? 'checked' : '' }}
                                                    class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                                <span>Active</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2 text-rose-700">
                                                <input type="hidden" name="entry_bundle_ads[{{ $i }}][_delete]" value="0">
                                                <input type="checkbox" name="entry_bundle_ads[{{ $i }}][_delete]" value="1"
                                                    {{ ! empty($row['_delete']) ? 'checked' : '' }}
                                                    @change="requestDeleteConfirm($event, 'entry build ad #{{ $i + 1 }}')"
                                                    class="rounded border-black/30 text-rose-700 focus:ring-rose-500">
                                                <span>Delete on save</span>
                                            </label>
                                        </div>
                                        </fieldset>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-lg border border-black/10 p-4"
                            x-data="{ visibleRows: {{ $initialGamingBundleRows }}, maxRows: {{ $maxBundleAdsPerType }} }">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-sm font-semibold text-gray-900">Gaming Build Ads</h4>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="inline-flex items-center rounded-md bg-gray-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-gray-800"
                                        @click="if (visibleRows < maxRows) visibleRows++">
                                        Add row
                                    </button>
                                    <button type="button"
                                        class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-800 ring-1 ring-black/10 hover:bg-gray-200"
                                        @click="if (visibleRows > 1) visibleRows--">
                                        Hide last row
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-600">
                                Tip: <span class="font-medium">Add row</span>/<span class="font-medium">Hide last row</span> only changes visible form rows.
                                Use <span class="font-medium text-rose-700">Delete on save</span> below to permanently remove a saved ad.
                            </p>
                            <div class="mt-3 space-y-3">
                                @foreach ($displayGamingBundleAds as $i => $row)
                                    @php
                                        $rowId = trim((string) ($row['id'] ?? ''));
                                        $currentImageSrc = $rowId !== '' ? ($bundleImageSrcMap[$rowId] ?? null) : null;
                                    @endphp
                                    <div class="rounded-md border border-black/10 p-3" x-show="visibleRows > {{ $i }}">
                                        <fieldset :disabled="visibleRows <= {{ $i }}">
                                        <input type="hidden" name="gaming_bundle_ads[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-medium text-gray-700">Image URL</label>
                                                <input type="url" name="gaming_bundle_ads[{{ $i }}][image_url]" value="{{ $row['image_url'] ?? '' }}"
                                                    placeholder="https://example.com/gaming-ad.jpg"
                                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                                @error("gaming_bundle_ads.$i.image_url")
                                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-medium text-gray-700">Upload image</label>
                                                <input type="file" accept="image/*" name="gaming_bundle_ads[{{ $i }}][image_file]"
                                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                                @error("gaming_bundle_ads.$i.image_file")
                                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="flex items-end lg:col-span-2">
                                                @if ($currentImageSrc)
                                                    <a href="{{ $currentImageSrc }}" target="_blank" rel="noopener noreferrer"
                                                        class="text-xs font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                                        Current image
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                                            <div class="w-20">
                                                <label class="block text-xs font-medium text-gray-700">Sort</label>
                                                <input type="number" min="1" max="999" name="gaming_bundle_ads[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? ($i + 1) }}"
                                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            </div>
                                            <label class="inline-flex items-center gap-2">
                                                <input type="hidden" name="gaming_bundle_ads[{{ $i }}][is_active]" value="0">
                                                <input type="checkbox" name="gaming_bundle_ads[{{ $i }}][is_active]" value="1"
                                                    {{ ! empty($row['is_active']) ? 'checked' : '' }}
                                                    class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                                <span>Active</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2 text-rose-700">
                                                <input type="hidden" name="gaming_bundle_ads[{{ $i }}][_delete]" value="0">
                                                <input type="checkbox" name="gaming_bundle_ads[{{ $i }}][_delete]" value="1"
                                                    {{ ! empty($row['_delete']) ? 'checked' : '' }}
                                                    @change="requestDeleteConfirm($event, 'gaming build ad #{{ $i + 1 }}')"
                                                    class="rounded border-black/30 text-rose-700 focus:ring-rose-500">
                                                <span>Delete on save</span>
                                            </label>
                                        </div>
                                        </fieldset>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10"
                    x-cloak
                    x-show="activeTab === 'featured_builds'"
                    x-transition.opacity.duration.150ms
                    x-data="{ visibleRows: {{ $initialFeaturedBuildRows }}, maxRows: {{ $maxFeaturedBuilds }} }">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Featured Builds</h3>
                            <p class="mt-1 text-xs text-gray-600">
                                Use up to 5 uploaded images per build: 1 primary image plus 4 gallery images.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800"
                                @click="if (visibleRows < maxRows) visibleRows++">
                                Add Build
                            </button>
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-800 ring-1 ring-black/10 hover:bg-gray-200"
                                @click="if (visibleRows > 1) visibleRows--">
                                Hide last row
                            </button>
                            <div class="text-xs text-gray-600">
                                <span x-text="`Showing ${visibleRows} of ${maxRows}`"></span>
                                <span class="ml-2">Saved: <span class="font-semibold text-gray-900">{{ $featuredBuildCount }}</span></span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-600">
                        Tip: Use <span class="font-medium text-rose-700">Delete on save</span> to permanently remove a saved build.
                    </p>

                    <div class="mt-4 space-y-3">
                        @foreach ($displayFeaturedBuilds as $i => $row)
                            @php
                                $rowId = trim((string) ($row['id'] ?? ''));
                                $currentImageSrc = $rowId !== '' ? ($featuredBuildImageSrcMap[$rowId] ?? null) : null;
                                $rowGalleryImages = collect((array) ($row['gallery_images'] ?? []))
                                    ->map(fn ($image) => trim((string) $image))
                                    ->take(4)
                                    ->values()
                                    ->all();
                                while (count($rowGalleryImages) < 4) {
                                    $rowGalleryImages[] = '';
                                }
                            @endphp
                            <div class="rounded-md border border-black/10 p-4" x-show="visibleRows > {{ $i }}">
                                <fieldset :disabled="visibleRows <= {{ $i }}">
                                <input type="hidden" name="featured_builds[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                                <div class="grid grid-cols-1 gap-3 lg:grid-cols-8">
                                    <div class="lg:col-span-3">
                                        <label class="block text-xs font-medium text-gray-700">Build description</label>
                                        <input type="text" name="featured_builds[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}"
                                            placeholder="Example: Ryzen 7 + RTX build for 1440p gaming"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("featured_builds.$i.title")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-5">
                                        <label class="block text-xs font-medium text-gray-700">Upload primary image</label>
                                        <input type="file" accept="image/*" name="featured_builds[{{ $i }}][image_file]"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("featured_builds.$i.image_file")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                        @if ($currentImageSrc)
                                            <p class="mt-1 text-xs text-gray-700">
                                                Current upload:
                                                <a href="{{ $currentImageSrc }}" target="_blank" rel="noopener noreferrer"
                                                    class="font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                                    View primary image
                                                </a>
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
                                    @foreach ($rowGalleryImages as $galleryIndex => $galleryImageUrl)
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">
                                                Upload gallery image #{{ $galleryIndex + 2 }}
                                            </label>
                                            <input type="file" accept="image/*"
                                                name="featured_builds[{{ $i }}][gallery_files][{{ $galleryIndex }}]"
                                                class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            @error("featured_builds.$i.gallery_files.$galleryIndex")
                                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                            @enderror
                                            @if ($galleryImageUrl !== '')
                                                <p class="mt-1 text-xs text-gray-700">
                                                    Current upload:
                                                    <a href="{{ $galleryImageUrl }}" target="_blank" rel="noopener noreferrer"
                                                        class="font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                                        View gallery image #{{ $galleryIndex + 2 }}
                                                    </a>
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                                    <div class="w-20">
                                        <label class="block text-xs font-medium text-gray-700">Sort</label>
                                        <input type="number" min="1" max="999" name="featured_builds[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? ($i + 1) }}"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    </div>

                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="featured_builds[{{ $i }}][is_active]" value="0">
                                        <input type="checkbox" name="featured_builds[{{ $i }}][is_active]" value="1"
                                            {{ ! empty($row['is_active']) ? 'checked' : '' }}
                                            class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                        <span>Active</span>
                                    </label>

                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="featured_builds[{{ $i }}][remove_image]" value="0">
                                        <input type="checkbox" name="featured_builds[{{ $i }}][remove_image]" value="1"
                                            {{ ! empty($row['remove_image']) ? 'checked' : '' }}
                                            class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                        <span>Remove uploaded image file</span>
                                    </label>

                                    <label class="inline-flex items-center gap-2 text-rose-700">
                                        <input type="hidden" name="featured_builds[{{ $i }}][_delete]" value="0">
                                        <input type="checkbox" name="featured_builds[{{ $i }}][_delete]" value="1"
                                            {{ ! empty($row['_delete']) ? 'checked' : '' }}
                                            @change="requestDeleteConfirm($event, 'featured build #{{ $i + 1 }}')"
                                            class="rounded border-black/30 text-rose-700 focus:ring-rose-500">
                                        <span>Delete on save</span>
                                    </label>
                                </div>
                                </fieldset>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10"
                    x-cloak
                    x-show="activeTab === 'reviews'"
                    x-transition.opacity.duration.150ms
                    x-data="{ visibleRows: {{ $initialReviewRows }}, maxRows: {{ $maxReviews }} }">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Customer Reviews</h3>
                            <p class="mt-1 text-xs text-gray-600">
                                These testimonials appear above the footer on the landing page.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800"
                                @click="if (visibleRows < maxRows) visibleRows++">
                                Add Review
                            </button>
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-800 ring-1 ring-black/10 hover:bg-gray-200"
                                @click="if (visibleRows > 1) visibleRows--">
                                Hide last row
                            </button>
                            <div class="text-xs text-gray-600">
                                <span x-text="`Showing ${visibleRows} of ${maxRows}`"></span>
                                <span class="ml-2">Saved: <span class="font-semibold text-gray-900">{{ $reviewCount }}</span></span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-600">
                        Tip: Use <span class="font-medium text-rose-700">Delete on save</span> to permanently remove a saved review.
                    </p>

                    <div class="mt-4 space-y-3">
                        @foreach ($displayReviews as $i => $row)
                            <div class="rounded-md border border-black/10 p-4" x-show="visibleRows > {{ $i }}">
                                <fieldset :disabled="visibleRows <= {{ $i }}">
                                <input type="hidden" name="reviews[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                                <div class="grid grid-cols-1 gap-3 lg:grid-cols-8">
                                    <div class="lg:col-span-3">
                                        <label class="block text-xs font-medium text-gray-700">Review title</label>
                                        <input type="text" name="reviews[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}"
                                            placeholder="Product or quick review title"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("reviews.$i.title")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700">Reviewer name</label>
                                        <input type="text" name="reviews[{{ $i }}][author_name]" value="{{ $row['author_name'] ?? '' }}"
                                            placeholder="Juan Dela Cruz"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("reviews.$i.author_name")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="lg:col-span-1">
                                        <label class="block text-xs font-medium text-gray-700">Rating</label>
                                        <input type="number" min="1" max="5" name="reviews[{{ $i }}][rating]" value="{{ $row['rating'] ?? 5 }}"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @error("reviews.$i.rating")
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="lg:col-span-1">
                                        <label class="block text-xs font-medium text-gray-700">Sort</label>
                                        <input type="number" min="1" max="999" name="reviews[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? ($i + 1) }}"
                                            class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    </div>
                                    <div class="lg:col-span-1 flex items-end">
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input type="hidden" name="reviews[{{ $i }}][is_active]" value="0">
                                            <input type="checkbox" name="reviews[{{ $i }}][is_active]" value="1"
                                                {{ ! empty($row['is_active']) ? 'checked' : '' }}
                                                class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                            <span>Active</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-xs font-medium text-gray-700">Review text</label>
                                    <textarea name="reviews[{{ $i }}][content]" rows="3"
                                        placeholder="Write the customer feedback here..."
                                        class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ $row['content'] ?? '' }}</textarea>
                                    @error("reviews.$i.content")
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                                    <label class="inline-flex items-center gap-2 text-rose-700">
                                        <input type="hidden" name="reviews[{{ $i }}][_delete]" value="0">
                                        <input type="checkbox" name="reviews[{{ $i }}][_delete]" value="1"
                                            {{ ! empty($row['_delete']) ? 'checked' : '' }}
                                            @change="requestDeleteConfirm($event, 'review #{{ $i + 1 }}')"
                                            class="rounded border-black/30 text-rose-700 focus:ring-rose-500">
                                        <span>Delete on save</span>
                                    </label>
                                </div>
                                </fieldset>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div x-cloak x-show="confirmDeleteOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/45 p-4"
                    role="dialog" aria-modal="true" aria-label="Confirm delete on save"
                    @click.self="closeDeleteConfirm()">
                    <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl ring-1 ring-black/10">
                        <h4 class="text-base font-semibold text-gray-900">Confirm Delete on Save</h4>
                        <p class="mt-2 text-sm text-gray-700">
                            Mark <span class="font-semibold text-gray-900" x-text="pendingDeleteLabel || 'this item'"></span>
                            for deletion?
                        </p>
                        <p class="mt-1 text-xs text-gray-600">
                            This item will be permanently deleted only after you click <span class="font-medium">Save content</span>.
                        </p>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-gray-50"
                                @click="closeDeleteConfirm()">
                                Cancel
                            </button>
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700"
                                @click="confirmDelete()">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                        Save content
                    </button>
                    <a href="{{ route('pricelist') }}"
                        class="text-sm font-medium text-gray-700 hover:text-gray-900 underline underline-offset-4 hover:no-underline">
                        View pricelist
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
