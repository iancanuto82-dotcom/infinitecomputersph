<?php

namespace App\Http\Controllers;

use App\Models\BundleAd;
use App\Models\CarouselSlide;
use App\Models\Category;
use App\Models\FeaturedBrand;
use App\Models\Product;
use App\Support\PublicCatalogCache;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function landing()
    {
        $landingData = Cache::remember(PublicCatalogCache::LANDING_DATA_KEY, now()->addMinutes(10), function (): array {
            $carouselSlides = CarouselSlide::query()
                ->select(['id', 'image_path', 'image_url', 'sort_order'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $featuredBrands = FeaturedBrand::query()
                ->select(['id', 'name', 'logo_path', 'logo_url', 'sort_order'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $categories = Category::query()
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name']);

            $bundleAds = BundleAd::query()
                ->select(['id', 'bundle_type', 'image_url', 'link_url', 'sort_order'])
                ->where('is_active', true)
                ->orderBy('bundle_type')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return [
                'carouselSlides' => $carouselSlides,
                'featuredBrands' => $featuredBrands,
                'categories' => $categories,
                'entryBundleAds' => $bundleAds->where('bundle_type', 'entry')->values(),
                'gamingBundleAds' => $bundleAds->where('bundle_type', 'gaming')->values(),
            ];
        });

        return view('landing.index', $landingData);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->integer('category');
        $sort = (string) $request->query('sort', 'name_asc');
        $allowedSorts = ['name_asc', 'name_desc', 'price_asc', 'price_desc', 'latest'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name_asc';
        }

        $categories = Cache::remember(PublicCatalogCache::CATEGORIES_LIST_KEY, now()->addMinutes(30), function () {
            return Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']);
        });

        $productsQuery = Product::query()
            ->select(['id', 'name', 'description', 'image_path', 'image_url', 'price', 'stock', 'category_id'])
            ->with('category:id,name')
            ->where('is_active', true);

        if ($search !== '') {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $categoryIds = $this->selectedCategoryIds($categories, $categoryId);

            $productsQuery->whereIn('category_id', $categoryIds);
        }

        $productsQuery = match ($sort) {
            'name_desc' => $productsQuery->orderByDesc('name'),
            'price_asc' => $productsQuery->orderBy('price'),
            'price_desc' => $productsQuery->orderByDesc('price'),
            'latest' => $productsQuery->latest('id'),
            default => $productsQuery->orderBy('name'),
        };

        $products = $productsQuery
            ->paginate(12)
            ->withQueryString();

        $builderCategories = Cache::remember(PublicCatalogCache::BUILDER_CATEGORIES_KEY, now()->addMinutes(5), function () use ($categories): array {
            $builderProducts = Product::query()
                ->select(['id', 'name', 'image_path', 'image_url', 'price', 'stock', 'category_id'])
                ->with('category:id,name,parent_id')
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->get();

            $builderSections = [
                ['key' => 'processor', 'name' => 'Processor', 'match' => ['processor', 'cpu', 'ryzen', 'intel', 'athlon', 'pentium', 'celeron', 'core i', 'xeon'], 'exclude' => ['cooler', 'fan', 'aio', 'heatsink', 'motherboard', 'mobo', 'chipset', 'socket', 'lga', 'am4', 'am5', 'h610', 'h510', 'h410', 'h310', 'b450', 'b550', 'b660', 'b760', 'a320', 'a520', 'x570', 'z690', 'z790']],
                ['key' => 'cpu_cooler', 'name' => 'CPU Cooler', 'match' => ['cpu cooler', 'aio', 'liquid cooler', 'radiator', 'air cooler', 'cooling'], 'exclude' => ['case fan', 'fan hub', 'keyboard', 'mouse', 'ram', 'memory', 'ddr', '3200mhz', '3600mhz', 'cl16', 'cl18', 'cl22', 'extension cable', 'cable set', 'laptop cooler', 'mini laptop cooler', 'laptop', 'notebook']],
                ['key' => 'motherboard', 'name' => 'Motherboard', 'match' => ['motherboard', 'mobo', 'mainboard', 'h610', 'h510', 'h410', 'h310', 'b450', 'b550', 'b650', 'b660', 'b760', 'a320', 'a520', 'x570', 'z690', 'z790'], 'exclude' => ['processor', 'cpu', 'ryzen', 'intel core', 'athlon', 'pentium', 'celeron', 'xeon', 'cooler', 'aio', 'liquid cooler', 'fan', 'heatsink', 'ram', 'memory', 'ssd', 'hdd', 'nvme', 'powersupply', 'power supply', 'psu', 'case', 'chassis', 'extension cable', 'cable set']],
                ['key' => 'desktop_ram', 'name' => 'RAM', 'match' => ['desktop ram', 'ram', 'memory', 'ddr'], 'exclude' => ['laptop', 'notebook', 'sodimm', 'so-dimm', 'so dimm', 'processor', 'cpu', 'ryzen', 'intel', 'athlon', 'pentium', 'celeron', 'xeon', 'motherboard', 'mobo', 'h610', 'h510', 'h410', 'h310', 'b450', 'b550', 'b650', 'b660', 'b760', 'a320', 'a520', 'x570', 'z690', 'z790', 'cooler', 'aio', 'liquid', 'radiator', 'fan', 'powersupply', 'power supply', 'psu', 'graphics', 'gpu', 'vga', 'ssd', 'hdd', 'nvme', 'm.2', 'case', 'chassis', 'atx', 'm-atx', 'matx', 'itx', 'e-atx', 'extension cable', 'cable set']],
                ['key' => 'graphics_card', 'name' => 'Graphics Card', 'match' => ['graphics card', 'gpu', 'video card', 'vga'], 'exclude' => ['adapter', 'cable', 'converter']],
                ['key' => 'storage', 'name' => 'Storage', 'match' => ['storage', 'ssd', 'hdd', 'nvme', 'm.2'], 'exclude' => ['caddy', 'enclosure', 'adapter', 'converter', 'cable', 'dock', 'tray', 'case']],
                ['key' => 'power_supply', 'name' => 'Power Supply', 'match' => ['power supply', 'powersupply', 'psu'], 'exclude' => ['cable', 'extension', 'adapter', 'case with psu', 'w/ psu', 'w/psu']],
                ['key' => 'fans', 'name' => 'Fans', 'match' => ['fan', 'fans', 'rgb fan'], 'exclude' => ['cpu cooler', 'aio', 'liquid', 'fan hub', 'fan controller', 'case with fan', 'w/ fan', 'm-atx case', 'matx case', 'case w/psu', 'case w/ psu', 'case with psu', 'w/psu+fans', 'keyboard', 'mechanical keyboard', 'fantech']],
                ['key' => 'case', 'name' => 'Case', 'match' => ['case', 'chassis'], 'exclude' => ['case fan', 'case with psu', 'w/ psu', 'w/psu', 'case with fan', 'w/ fan']],
                ['key' => 'keyboard', 'name' => 'Keyboard', 'match' => ['keyboard', 'keyb'], 'exclude' => []],
                ['key' => 'mouse', 'name' => 'Mouse', 'match' => ['mouse'], 'exclude' => []],
            ];

            return collect($builderSections)
                ->map(function (array $section) use ($builderProducts, $categories): array {
                    $sectionCategoryIds = $this->builderSectionCategoryIds($categories, (string) ($section['key'] ?? ''));
                    $usesSubcategoryGrouping = in_array((string) ($section['key'] ?? ''), ['processor', 'motherboard', 'desktop_ram'], true);

                    $products = $builderProducts
                        ->filter(function (Product $product) use ($section, $sectionCategoryIds): bool {
                            $categoryName = Str::lower((string) optional($product->category)->name);
                            $productName = Str::lower((string) $product->name);
                            $haystack = $categoryName.' '.$productName;

                            if (count($sectionCategoryIds) > 0) {
                                if (! in_array((int) $product->category_id, $sectionCategoryIds, true)) {
                                    return false;
                                }
                            }

                            if (($section['key'] ?? '') === 'desktop_ram') {
                                $ramCategoryNeedles = ['ram', 'memory', 'ddr'];
                                $categoryLooksLikeRam = collect($ramCategoryNeedles)->contains(
                                    fn (string $needle): bool => str_contains($categoryName, $needle)
                                );

                                $ramNameNeedles = ['ram', 'memory', 'ddr', 'pc3', 'pc4', 'dimm', 'udimm', 'gbx2', 'dual stick', 'mhz', 'rgb', 'cl', 'xmp'];
                                $nameLooksLikeRam = collect($ramNameNeedles)->contains(
                                    fn (string $needle): bool => str_contains($productName, $needle)
                                );

                                if (! $categoryLooksLikeRam && ! $nameLooksLikeRam) {
                                    return false;
                                }

                                $cpuModelNeedles = ['amd r3', 'amd r5', 'amd r7', 'amd r9', 'intel i3', 'intel i5', 'intel i7', 'intel i9', 'ryzen', 'athlon', 'pentium', 'celeron', 'xeon'];
                                $nameLooksLikeCpu = collect($cpuModelNeedles)->contains(
                                    fn (string $needle): bool => str_contains($productName, $needle)
                                );

                                if ($nameLooksLikeCpu) {
                                    return false;
                                }
                            }

                            $matches = collect($section['match'])->contains(
                                fn (string $needle): bool => str_contains($haystack, Str::lower($needle))
                            );

                            if (! $matches) {
                                return false;
                            }

                            $excluded = collect($section['exclude'])->contains(
                                fn (string $needle): bool => str_contains($haystack, Str::lower($needle))
                            );

                            return ! $excluded;
                        })
                        ->map(function (Product $product): array {
                            return [
                                'id' => (int) $product->id,
                                'name' => (string) $product->name,
                                'price' => (float) $product->price,
                                'image' => $product->image_src,
                                'subcategory' => (string) optional($product->category)->name,
                            ];
                        })
                        ->values()
                        ->all();

                    $productGroups = $usesSubcategoryGrouping
                        ? collect($products)
                            ->groupBy(fn (array $product): string => trim((string) ($product['subcategory'] ?? '')) !== ''
                                ? trim((string) $product['subcategory'])
                                : 'General')
                            ->map(fn (Collection $groupProducts, string $label): array => [
                                'label' => $label,
                                'products' => $groupProducts
                                    ->sortBy(fn (array $product): string => Str::lower((string) ($product['name'] ?? '')))
                                    ->values()
                                    ->all(),
                            ])
                            ->sortBy(fn (array $group): string => Str::lower((string) ($group['label'] ?? '')))
                            ->values()
                            ->all()
                        : [];

                    return [
                        'id' => (string) $section['key'],
                        'name' => (string) $section['name'],
                        'products' => $products,
                        'groups' => $productGroups,
                    ];
                })
                ->filter(fn (array $section): bool => count($section['products']) > 0)
                ->values()
                ->all();
        });

        return view('products.index', compact(
            'products',
            'categories',
            'search',
            'categoryId',
            'sort',
            'builderCategories'
        ));
    }

    private function isProcessorMainCategoryName(string $name): bool
    {
        $normalized = Str::lower(trim($name));
        if ($normalized === '') {
            return false;
        }

        return $normalized === 'processor' || $normalized === 'cpu';
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Category> $categories
     * @return array<int, int>
     */
    private function selectedCategoryIds($categories, int $categoryId): array
    {
        $selected = $categories->first(fn ($category) => (int) $category->id === (int) $categoryId);
        if (! $selected) {
            return [$categoryId];
        }

        $ids = [(int) $selected->id];

        if ($selected->parent_id === null) {
            $childIds = $categories
                ->filter(fn ($category) => (int) ($category->parent_id ?? 0) === (int) $selected->id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            if (count($childIds) > 0) {
                $ids = array_merge($ids, $childIds);
            } elseif ($this->isProcessorMainCategoryName((string) $selected->name)) {
                $ids = $this->processorCategoryIds($categories);
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Category> $categories
     * @return array<int, int>
     */
    private function processorCategoryIds($categories): array
    {
        $mainProcessorIds = $categories
            ->filter(fn ($category): bool => $this->isProcessorMainCategoryName((string) ($category->name ?? '')))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if (count($mainProcessorIds) > 0) {
            return $categories
                ->filter(function ($category) use ($mainProcessorIds): bool {
                    $id = (int) ($category->id ?? 0);
                    $parentId = (int) ($category->parent_id ?? 0);
                    return in_array($id, $mainProcessorIds, true) || in_array($parentId, $mainProcessorIds, true);
                })
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $processorSubcategoryNeedles = [
            'amd',
            'intel',
            'ryzen',
            'athlon',
            'pentium',
            'celeron',
            'core',
            'xeon',
        ];

        return $categories
            ->filter(function ($category) use ($processorSubcategoryNeedles): bool {
                $name = Str::lower(trim((string) $category->name));
                if ($name === '') {
                    return false;
                }

                if ($this->isProcessorMainCategoryName($name)) {
                    return true;
                }

                return collect($processorSubcategoryNeedles)->contains(
                    fn (string $needle): bool => str_contains($name, $needle)
                );
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Category> $categories
     * @return array<int, int>
     */
    private function builderSectionCategoryIds($categories, string $sectionKey): array
    {
        $needlesBySection = [
            'processor' => ['processor', 'cpu'],
            'motherboard' => ['motherboard', 'mobo', 'mainboard'],
            'desktop_ram' => ['ram', 'memory', 'desktop ram'],
        ];

        $needles = $needlesBySection[$sectionKey] ?? [];
        if (count($needles) === 0) {
            return [];
        }

        $mainCategoryIds = $categories
            ->filter(function ($category) use ($needles): bool {
                if (($category->parent_id ?? null) !== null) {
                    return false;
                }

                $name = Str::lower(trim((string) ($category->name ?? '')));
                if ($name === '') {
                    return false;
                }

                return collect($needles)->contains(
                    fn (string $needle): bool => str_contains($name, Str::lower($needle))
                );
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if (count($mainCategoryIds) === 0) {
            return [];
        }

        return $categories
            ->filter(function ($category) use ($mainCategoryIds): bool {
                $id = (int) ($category->id ?? 0);
                $parentId = (int) ($category->parent_id ?? 0);

                return in_array($id, $mainCategoryIds, true) || in_array($parentId, $mainCategoryIds, true);
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function show(Request $request, Product $product)
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->loadMissing('category:id,name');

        $relatedProducts = Product::query()
            ->select(['id', 'name', 'image_path', 'image_url', 'price', 'category_id'])
            ->with('category:id,name')
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when(
                $product->category_id !== null,
                fn ($query) => $query->where('category_id', $product->category_id)
            )
            ->orderBy('name')
            ->limit(4)
            ->get();

        $backQuery = $this->pricelistBackQuery($request);

        return view('products.show', compact('product', 'relatedProducts', 'backQuery'));
    }

    /**
     * @return array<string, string>
     */
    private function pricelistBackQuery(Request $request): array
    {
        $query = [];

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query['search'] = $search;
        }

        $category = trim((string) $request->query('category', ''));
        if ($category !== '') {
            $query['category'] = $category;
        }

        $page = trim((string) $request->query('page', ''));
        if ($page !== '') {
            $query['page'] = $page;
        }

        $tab = trim((string) $request->query('tab', ''));
        if ($tab !== '') {
            $query['tab'] = $tab;
        }

        $sort = trim((string) $request->query('sort', ''));
        if ($sort !== '') {
            $query['sort'] = $sort;
        }

        return $query;
    }
}
