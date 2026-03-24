<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $siteName = (string) config('app.name', 'Infinite Computers');
        $appLogo = (string) config('app.logo_url', 'https://i.imgur.com/MCfboy4.png');
        $pageTitle = trim((string) $__env->yieldContent('title'));
        $metaDescription = trim((string) $__env->yieldContent('meta_description'));
        $ogTitle = trim((string) $__env->yieldContent('og_title'));
        $ogDescription = trim((string) $__env->yieldContent('og_description'));
        $ogImage = trim((string) $__env->yieldContent('og_image'));
        $fullTitle = $pageTitle !== '' ? "{$pageTitle} | {$siteName}" : $siteName;
        $metaDescription = $metaDescription !== '' ? $metaDescription : 'Browse computer parts and pricing from Infinite Computers.';
        $ogTitle = $ogTitle !== '' ? $ogTitle : $fullTitle;
        $ogDescription = $ogDescription !== '' ? $ogDescription : $metaDescription;
        $ogImage = $ogImage !== '' ? $ogImage : $appLogo;
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ request()->fullUrl() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="canonical" href="{{ request()->fullUrl() }}">
    <link rel="dns-prefetch" href="//i.imgur.com">
    <link rel="preconnect" href="https://i.imgur.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ $appLogo }}">
    <link rel="apple-touch-icon" href="{{ $appLogo }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $isWidePublicPage = request()->routeIs('home') || request()->routeIs('pricelist');
    $isHomeRoute = request()->routeIs('home');
    $isPricelistRoute = request()->routeIs('pricelist*');
    $containerMaxWidth = $isWidePublicPage ? 'max-w-[92rem]' : 'max-w-7xl';
    $headerCategories = collect();

    try {
        $headerCategories = \Illuminate\Support\Facades\Cache::remember(
            \App\Support\PublicCatalogCache::HEADER_NAVIGATION_KEY,
            now()->addMinutes(30),
            function () {
                return \App\Models\Category::query()
                    ->select(['id', 'name', 'parent_id'])
                    ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                    ->orderBy('name')
                    ->get();
            }
        );
    } catch (\Throwable $e) {
        $headerCategories = collect();
    }

    $headerMainCategories = $headerCategories
        ->filter(fn ($category) => ($category->parent_id ?? null) === null)
        ->values();

    $headerSubcategoriesByParent = $headerCategories
        ->filter(fn ($category) => ($category->parent_id ?? null) !== null)
        ->groupBy(fn ($category) => (int) $category->parent_id);

    $headerFallbackProductsByCategory = collect();

    try {
        $headerFallbackProductsByCategory = \Illuminate\Support\Facades\Cache::remember(
            \App\Support\PublicCatalogCache::HEADER_FALLBACK_PRODUCTS_KEY,
            now()->addMinutes(5),
            function () use ($headerMainCategories, $headerSubcategoriesByParent) {
                $mainCategoryIdsWithoutSubcategories = $headerMainCategories
                    ->filter(fn ($category) => $headerSubcategoriesByParent->get((int) $category->id, collect())->isEmpty())
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->values();

                if ($mainCategoryIdsWithoutSubcategories->isEmpty()) {
                    return collect();
                }

                return \App\Models\Product::query()
                    ->select(['id', 'name', 'price', 'category_id'])
                    ->where('is_active', true)
                    ->whereIn('category_id', $mainCategoryIdsWithoutSubcategories->all())
                    ->orderBy('name')
                    ->get()
                    ->groupBy(fn ($product) => (int) $product->category_id)
                    ->map(fn ($products) => $products->take(8)->values());
            }
        );
    } catch (\Throwable $e) {
        $headerFallbackProductsByCategory = collect();
    }

    $headerInitialParentId = (string) (optional($headerMainCategories->first())->id ?? '');
@endphp
<body class="public-theme antialiased min-h-screen {{ $isHomeRoute ? 'home-theme' : '' }} {{ $isPricelistRoute ? 'pricelist-theme' : '' }}">
    <div class="min-h-screen flex flex-col" x-data="{ mapOpen: false, logoFailed: false }" x-on:keydown.escape.window="mapOpen = false">
        <nav class="sticky top-0 z-30">
            <div class="market-topbar">
                <div class="{{ $containerMaxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 items-center gap-3 py-3 md:grid-cols-[1fr_minmax(0,42rem)_1fr]">
                        <a href="{{ route('home') }}" class="hidden items-center gap-3 md:inline-flex md:justify-self-start">
                            <span class="inline-flex h-12 w-12 items-center justify-center overflow-hidden">
                                <img src="{{ $appLogo }}" alt="{{ config('app.name') }} logo"
                                    class="app-logo-bordered h-10 w-10 object-contain" loading="lazy" referrerpolicy="no-referrer"
                                    x-show="!logoFailed" x-on:error="logoFailed = true">
                                <span x-cloak x-show="logoFailed" class="text-base font-semibold text-white">I</span>
                            </span>
                            <span class="text-lg font-semibold tracking-tight uppercase text-white">{{ $siteName }}</span>
                        </a>

                        <form method="GET" action="{{ route('pricelist') }}" class="relative w-full">
                            <label for="top_search" class="sr-only">Search</label>
                            <input id="top_search" type="text" name="search" value="{{ request('search') }}"
                                placeholder="Search"
                                class="market-search h-11 w-full rounded-full px-4 pr-12 text-sm">
                            <button type="submit"
                                class="market-search-btn absolute right-3 top-1/2 -translate-y-1/2"
                                aria-label="Search products">
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M9 3.75a5.25 5.25 0 103.31 9.33l3.8 3.8a.75.75 0 101.06-1.06l-3.8-3.8A5.25 5.25 0 009 3.75zM5.25 9a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>

                        <div class="flex items-center gap-3 sm:gap-4 md:justify-self-end">
                            <button type="button"
                                class="market-icon-btn inline-flex items-center justify-center"
                                x-on:click="mapOpen = true"
                                aria-label="Location">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12 2.25a7.5 7.5 0 00-7.5 7.5c0 5.41 5.75 10.73 7.09 11.9a.75.75 0 00.98 0c1.34-1.17 7.09-6.49 7.09-11.9a7.5 7.5 0 00-7.5-7.5zm0 10.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            @auth
                                @php($isAdmin = \App\Support\AdminAccess::isAdmin(auth()->user()))
                                @php($adminRouteName = \App\Support\AdminAccess::preferredAdminRouteName(auth()->user()))
                                @php($dashboardHref = $isAdmin ? ($adminRouteName ? route($adminRouteName) : route('home')) : route('dashboard'))

                                <details class="relative hidden sm:block">
                                    <summary class="list-none cursor-pointer text-sm font-semibold text-white">
                                        Account
                                    </summary>
                                    <div class="theme-dropdown absolute right-0 mt-2 w-44 overflow-hidden rounded-md shadow-lg">
                                        <a href="{{ $dashboardHref }}" class="theme-dropdown-item block px-4 py-2 text-sm focus:outline-none">
                                            Dashboard
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="theme-dropdown-item block w-full px-4 py-2 text-left text-sm focus:outline-none">
                                                Log out
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            @else
                                {{-- Login button temporarily hidden --}}
                            @endauth
                        </div>
                    </div>
                </div>
            </div>

            <div class="market-menubar">
                <div class="{{ $containerMaxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="relative flex flex-wrap items-center justify-center gap-x-6 gap-y-2 py-3 text-sm font-medium uppercase tracking-wide">
                        <a href="{{ route('home') }}" class="market-menu-link {{ request()->routeIs('home') ? 'market-menu-active' : '' }}">Home</a>
                        <div class="market-menu-group market-menu-group--products"
                            x-data="{
                                productsOpen: false,
                                openTimer: null,
                                closeTimer: null,
                                supportsHover: window.matchMedia('(hover: hover) and (pointer: fine)').matches,
                                activeParent: '{{ $headerInitialParentId }}',
                                toggleProducts() {
                                    window.clearTimeout(this.openTimer);
                                    window.clearTimeout(this.closeTimer);
                                    this.productsOpen = !this.productsOpen;
                                    if (this.productsOpen && !this.activeParent) {
                                        this.activeParent = '{{ $headerInitialParentId }}';
                                    }
                                },
                                openProducts() {
                                    window.clearTimeout(this.closeTimer);
                                    if (this.productsOpen) return;

                                    window.clearTimeout(this.openTimer);
                                    this.openTimer = window.setTimeout(() => {
                                        this.productsOpen = true;
                                        if (!this.activeParent) this.activeParent = '{{ $headerInitialParentId }}';
                                    }, 55);
                                },
                                closeProducts(immediate = false) {
                                    window.clearTimeout(this.openTimer);

                                    if (immediate) {
                                        window.clearTimeout(this.closeTimer);
                                        this.productsOpen = false;
                                        return;
                                    }

                                    window.clearTimeout(this.closeTimer);
                                    this.closeTimer = window.setTimeout(() => {
                                        this.productsOpen = false;
                                    }, 140);
                                }
                            }"
                            x-on:mouseenter="if (supportsHover) openProducts()"
                            x-on:mouseleave="if (supportsHover) closeProducts()">
                            <a href="{{ route('pricelist') }}"
                                x-on:click.prevent="if (!supportsHover) toggleProducts()"
                                class="market-menu-link inline-flex items-center gap-1 {{ request()->routeIs('pricelist*') ? 'market-menu-active' : '' }}">
                                Products
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <div x-cloak
                                x-show="productsOpen"
                                x-transition:enter="transition ease-out duration-130"
                                x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.99]"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-110"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.99]"
                                x-on:mouseenter="if (supportsHover) openProducts()"
                                x-on:mouseleave="if (supportsHover) closeProducts()"
                                x-on:click.outside="closeProducts(true)"
                                class="market-mega-popover">
                                <div class="market-mega-shell">
                                    <div class="market-mega-main">
                                        <a href="{{ route('pricelist') }}" class="market-mega-main-link market-mega-main-link-home">
                                            All Products
                                        </a>
                                        @foreach ($headerMainCategories as $mainCategory)
                                            <a href="{{ route('pricelist', ['category' => $mainCategory->id]) }}"
                                                class="market-mega-main-link"
                                                x-on:mouseenter="activeParent = '{{ (int) $mainCategory->id }}'"
                                                x-on:focus="activeParent = '{{ (int) $mainCategory->id }}'"
                                                :class="activeParent === '{{ (int) $mainCategory->id }}' ? 'market-mega-main-link-active' : ''">
                                                <span>{{ $mainCategory->name }}</span>
                                                <span class="market-mega-main-arrow" aria-hidden="true">&rsaquo;</span>
                                            </a>
                                        @endforeach
                                    </div>

                                    <div class="market-mega-sub">
                                        @foreach ($headerMainCategories as $mainCategory)
                                            @php($subcategories = $headerSubcategoriesByParent->get((int) $mainCategory->id, collect()))
                                            <section x-show="activeParent === '{{ (int) $mainCategory->id }}'" class="market-mega-sub-panel">
                                                <div class="market-mega-sub-head">
                                                    <a href="{{ route('pricelist', ['category' => $mainCategory->id]) }}" class="market-mega-sub-title">
                                                        {{ $mainCategory->name }}
                                                    </a>
                                                    <a href="{{ route('pricelist', ['category' => $mainCategory->id]) }}" class="market-mega-sub-all">
                                                        View all
                                                    </a>
                                                </div>

                                                @if ($subcategories->isNotEmpty())
                                                    <div class="market-mega-sub-grid">
                                                        @foreach ($subcategories as $subcategory)
                                                            <a href="{{ route('pricelist', ['category' => $subcategory->id]) }}"
                                                                class="market-mega-sub-link">
                                                                {{ $subcategory->name }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    @php($fallbackProducts = $headerFallbackProductsByCategory->get((int) $mainCategory->id, collect()))
                                                    @if ($fallbackProducts->isNotEmpty())
                                                        <div class="market-mega-products-grid">
                                                            @foreach ($fallbackProducts as $product)
                                                                <a href="{{ route('pricelist.show', $product->id) }}" class="market-mega-product-link">
                                                                    <span class="market-mega-product-name">{{ $product->name }}</span>
                                                                    <span class="market-mega-product-price">&#8369;{{ number_format((float) $product->price, 2) }}</span>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="market-mega-empty">No products available in this category yet.</p>
                                                    @endif
                                                @endif
                                            </section>
                                        @endforeach
                                    </div>
                                </div>
                                <div x-show="'{{ $headerInitialParentId }}' === ''" class="market-mega-empty px-4 py-3">
                                    Categories are not available yet.
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('pricelist', ['tab' => 'diy_builder']) }}#pricelist-tabs" class="market-menu-link">Build a PC</a>
                        <a id="nav-featured-builds"
                            href="{{ route('featured-builds') }}"
                            class="market-menu-link {{ request()->routeIs('featured-builds') ? 'market-menu-active' : '' }}"
                            title="View featured build gallery">
                            Featured Builds
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div x-show="mapOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            role="dialog" aria-modal="true" aria-label="Store location map"
            x-on:click.self="mapOpen = false">
            <div class="absolute inset-0 bg-black/55"></div>
            <div class="relative w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/15">
                <div class="flex items-center justify-between border-b border-black/10 px-4 py-3 sm:px-5">
                    <h2 class="text-base font-semibold text-gray-900 sm:text-lg">Infinite Computers Location</h2>
                    <button type="button"
                        class="rounded-md border border-black/10 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                        x-on:click="mapOpen = false">
                        Close
                    </button>
                </div>
                <div class="aspect-[4/3] w-full bg-gray-100">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d478.3225546714244!2d120.59138588874772!3d16.446121469434033!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3391a37d2393bda5%3A0x7770179f1b7b6fd9!2sInfinite%20Computers!5e0!3m2!1sen!2sph!4v1770713490992!5m2!1sen!2sph"
                        class="h-full w-full" style="border:0;"
                        allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>

        <main class="public-main flex-1 {{ $containerMaxWidth }} mx-auto w-full px-4 sm:px-6 lg:px-8 {{ request()->routeIs('home') ? 'pt-0 pb-8' : 'py-8' }}">
            @yield('content')
        </main>

        <footer class="theme-footer">
            <div class="{{ $containerMaxWidth }} theme-footer-inner mx-auto px-4 py-10 sm:px-6 lg:px-8">
                <div class="theme-footer-grid">
                    <section class="space-y-4">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                            <span class="inline-flex h-12 w-12 items-center justify-center overflow-hidden">
                                <img src="{{ $appLogo }}" alt="{{ config('app.name') }} logo"
                                    class="app-logo-bordered h-10 w-10 object-contain" loading="lazy" referrerpolicy="no-referrer">
                            </span>
                            <span class="text-lg font-semibold tracking-tight text-white">{{ config('app.name') }}</span>
                        </a>
                        <p class="theme-footer-text max-w-md text-sm">
                            Transparent parts pricing, fast browsing, and reliable PC build options for every budget.
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('pricelist') }}" class="theme-footer-cta">
                                Shop Pricelist
                            </a>
                            <button type="button" class="theme-footer-outline" x-on:click="mapOpen = true">
                                View Store Map
                            </button>
                        </div>
                    </section>

                    <section>
                        <h3 class="theme-footer-title">Quick Links</h3>
                        <ul class="theme-footer-list mt-4">
                            <li><a href="{{ route('home') }}" class="theme-footer-link">Home</a></li>
                            <li><a href="{{ route('pricelist') }}" class="theme-footer-link">All Products</a></li>
                            <li><a href="{{ route('pricelist', ['tab' => 'diy_builder']) }}#pricelist-tabs" class="theme-footer-link">Build a PC</a></li>
                            <li><a href="{{ route('featured-builds') }}" class="theme-footer-link">Featured Builds</a></li>
                            <li><a href="{{ route('home') }}#featured-brands" class="theme-footer-link">Featured Brands</a></li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="theme-footer-title">Popular Searches</h3>
                        <ul class="theme-footer-list mt-4">
                            <li><a href="{{ route('pricelist', ['search' => 'processor']) }}" class="theme-footer-link">Processors</a></li>
                            <li><a href="{{ route('pricelist', ['search' => 'graphics card']) }}" class="theme-footer-link">Graphics Cards</a></li>
                            <li><a href="{{ route('pricelist', ['search' => 'motherboard']) }}" class="theme-footer-link">Motherboards</a></li>
                            <li><a href="{{ route('pricelist', ['search' => 'ssd']) }}" class="theme-footer-link">SSDs & Storage</a></li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="theme-footer-title">Contact</h3>
                        <ul class="theme-footer-contact mt-4">
                            <li>
                                <span class="theme-footer-label">Phone</span>
                                <a href="tel:+639993590894" class="theme-footer-link">+63 999 359 0894</a>
                            </li>
                            <li>
                                <span class="theme-footer-label">Address</span>
                                <a href="https://www.google.com/maps?q=Infinite+Computers+La+Trinidad+Benguet"
                                    target="_blank" rel="noopener noreferrer" class="theme-footer-link">
                                    1st Floor, JMF Bldg, FA 182, Km.5 Balili, La Trinidad, Benguet 2601
                                </a>
                            </li>
                        </ul>
                    </section>
                </div>

                <div class="theme-footer-bottom mt-8 pt-5 text-sm">
                    <div>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
                    <div class="theme-footer-bottom-meta">Built for fast, clear, and accurate PC shopping.</div>
                </div>
            </div>
        </footer>

        @stack('page-modals')
    </div>
</body>
</html>
