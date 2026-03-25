@extends('layouts.public')

@section('title', 'Home')

@section('content')
    @php
        $fallbackSlides = [
            [
                'image' => null,
            ],
            [
                'image' => null,
            ],
        ];

        $carouselItems = collect($carouselSlides ?? [])
            ->map(fn ($slide) => [
                'image' => $slide->image_src,
            ])
            ->values();

        if ($carouselItems->isEmpty()) {
            $carouselItems = collect($fallbackSlides);
        }

        $fallbackBrands = [
            ['name' => 'Intel', 'logo' => null],
            ['name' => 'AMD', 'logo' => null],
            ['name' => 'NVIDIA', 'logo' => null],
            ['name' => 'ASUS', 'logo' => null],
            ['name' => 'MSI', 'logo' => null],
            ['name' => 'GIGABYTE', 'logo' => null],
        ];

        $brandItems = collect($featuredBrands ?? [])
            ->map(fn ($brand) => [
                'name' => (string) $brand->name,
                'logo' => $brand->logo_src,
            ])
            ->values();

        if ($brandItems->isEmpty()) {
            $brandItems = collect($fallbackBrands);
        }

        $brandBaseCount = max(1, $brandItems->count());
        $brandMinTrackItems = 12;
        $brandRepeatCount = (int) ceil($brandMinTrackItems / $brandBaseCount);
        $brandMarqueeItems = collect(range(1, max(1, $brandRepeatCount)))
            ->flatMap(fn () => $brandItems)
            ->values();

        $categoryImageMatchers = [
            'cpu-cooler' => ['cpu cooler', 'aio', 'heatsink'],
            'processor' => ['processor'],
            'motherboard' => ['motherboard', 'mobo'],
            'graphics-card' => ['graphics card', 'gpu', 'vga'],
            'memory' => ['ram', 'memory'],
            'ssd' => ['ssd', 'storage', 'drive'],
            'power-supply' => ['power', 'psu'],
            'pc-case' => ['case', 'chassis'],
            'fans' => ['fan'],
            'monitor' => ['monitor'],
            'keyboard' => ['keyboard', 'mouse'],
            'ups' => ['ups'],
            'extension' => ['extension'],
            'avr-ups-extension' => ['avr', 'ups', 'extension'],
            'peripheral' => ['peripheral', 'peripherals'],
            'printer' => ['printer'],
            'cctv' => ['cctv'],
            'chair-table' => ['chair', 'table'],
            'headset' => ['headset'],
            'networking' => ['networking', 'network', 'router', 'switch', 'netwoking'],
            'speaker' => ['speaker'],
            'webcam' => ['webcam', 'camera'],
            'accessories' => ['accessories', 'accessory', 'webcam', 'speaker', 'headset'],
        ];

        // Paste your image URLs here (absolute URLs or CDN links).
        $categoryImageUrlMap = [
            'processor' => 'https://imgur.com/GBqz87n.png',
            'motherboard' => 'https://imgur.com/8xswM1N.png',
            'graphics-card' => 'https://imgur.com/84Hzg8Y.png',
            'memory' => 'https://imgur.com/l92DvDH.png',
            'ssd' => 'https://imgur.com/6kengzd.png',
            'power-supply' => 'https://imgur.com/Ig1uWvT.png',
            'pc-case' => 'https://imgur.com/VzQHzxf.png',
            'cpu-cooler' => 'https://i.imgur.com/CgQvITn.png',
            'fans' => 'https://imgur.com/ZaEykqs.png',
            'monitor' => 'https://imgur.com/Kisq09v.png',
            'keyboard' => 'https://imgur.com/zesht0W.png',
            'ups' => 'https://imgur.com/lnSNUUR.png',
            'extension' => 'https://i.imgur.com/kmK76dn.png',
            'peripheral' => 'https://i.imgur.com/oVorA3G.png',
            'printer' => 'https://i.imgur.com/WcV4JfK.png',
            'cctv' => 'https://imgur.com/6DdrxMK.png',
            'chair-table' => 'https://imgur.com/BuT4i0l.png',
            'headset' => 'https://imgur.com/bcNRJjN.png',
            'networking' => 'https://imgur.com/55DEtwu.png',
            'speaker' => 'https://imgur.com/vVUGxEu.png',
            'webcam' => 'https://i.imgur.com/vXyAV8x.png',
            'accessories' => '',
        ];

        $mainCategoryPriority = [
            ['processor', 'cpu'],
            ['motherboard', 'mobo'],
            ['graphics card', 'gpu', 'vga'],
            ['ram', 'memory'],
            ['ssd', 'storage', 'drive'],
            ['power', 'psu'],
            ['case', 'chassis'],
            ['cpu cooler', 'aio', 'heatsink'],
            ['fan'],
            ['monitor'],
            ['keyboard', 'mouse'],
            ['peripheral'],
            ['printer'],
            ['ups'],
            ['extension'],
            ['avr', 'ups', 'extension'],
            ['cctv'],
            ['chair', 'table'],
            ['headset'],
            ['networking', 'network'],
            ['speaker'],
            ['webcam'],
        ];

        $hiddenCategoryNeedles = [
            'laptop',
            'laptops',
            'flashdrive',
            'flash drive',
            'flashdrive/cards',
            'flash drive/cards',
            'accessories',
            'accessory',
            'laptop accessories',
            'laptop accessory',
            'printer parts',
            'printer part',
            'printer/ink',
            'printer ink',
        ];

        $categoryCards = collect($categories ?? [])
            ->map(function ($category) use ($categoryImageMatchers, $categoryImageUrlMap) {
                $name = (string) $category->name;
                $normalized = \Illuminate\Support\Str::lower($name);
                $imageKey = 'accessories';

                foreach ($categoryImageMatchers as $key => $needles) {
                    $matched = collect($needles)->contains(fn ($needle) => str_contains($normalized, $needle));
                    if ($matched) {
                        $imageKey = $key;
                        break;
                    }
                }

                return [
                    'id' => (int) $category->id,
                    'name' => $name,
                    'image_url' => trim((string) ($categoryImageUrlMap[$imageKey] ?? '')),
                ];
            })
            ->reject(function (array $category) use ($hiddenCategoryNeedles) {
                $normalized = \Illuminate\Support\Str::lower(trim($category['name']));

                return collect($hiddenCategoryNeedles)->contains(
                    fn ($needle) => str_contains($normalized, $needle)
                );
            })
            ->sortBy(function (array $category) use ($mainCategoryPriority) {
                $normalized = \Illuminate\Support\Str::lower($category['name']);
                $index = collect($mainCategoryPriority)->search(function (array $needles) use ($normalized) {
                    return collect($needles)->contains(fn ($needle) => str_contains($normalized, $needle));
                });
                $priority = $index === false ? 999 : $index;

                return [$priority, $normalized];
            })
            ->values();

        $entryBundleCards = collect($entryBundleAds ?? [])
            ->map(fn ($ad) => [
                'image_url' => (string) ($ad->image_src ?? $ad->image_url ?? ''),
                'link_url' => (string) ($ad->link_url ?? ''),
            ])
            ->values();

        if ($entryBundleCards->isEmpty()) {
            $entryBundleCards = collect([
                ['image_url' => '', 'link_url' => ''],
                ['image_url' => '', 'link_url' => ''],
            ]);
        }

        $gamingBundleCards = collect($gamingBundleAds ?? [])
            ->map(fn ($ad) => [
                'image_url' => (string) ($ad->image_src ?? $ad->image_url ?? ''),
                'link_url' => (string) ($ad->link_url ?? ''),
            ])
            ->values();

        if ($gamingBundleCards->isEmpty()) {
            $gamingBundleCards = collect([
                ['image_url' => '', 'link_url' => ''],
                ['image_url' => '', 'link_url' => ''],
            ]);
        }

        $fallbackFeaturedBuilds = [
            ['title' => 'Featured Build 1', 'images' => ['', '', '', '', '']],
            ['title' => 'Featured Build 2', 'images' => ['', '', '', '', '']],
            ['title' => 'Featured Build 3', 'images' => ['', '', '', '', '']],
            ['title' => 'Featured Build 4', 'images' => ['', '', '', '', '']],
        ];

        $featuredBuildItems = collect($featuredBuilds ?? [])
            ->map(function ($build) {
                $images = collect([]);
                $primaryImage = trim((string) ($build->image_src ?? $build->image_url ?? ''));

                if ($primaryImage !== '') {
                    $images->push($primaryImage);
                }

                $galleryImages = collect((array) ($build->gallery_image_src_list ?? []))
                    ->map(fn ($image) => trim((string) $image))
                    ->filter(fn (string $image) => $image !== '');

                foreach ($galleryImages as $image) {
                    if (! $images->contains($image)) {
                        $images->push($image);
                    }
                }

                $images = $images->take(5)->values();
                while ($images->count() < 5) {
                    $images->push('');
                }

                return [
                    'title' => trim((string) ($build->title ?? '')),
                    'images' => $images->all(),
                ];
            })
            ->filter(function (array $build) {
                $hasImage = collect($build['images'] ?? [])
                    ->contains(fn ($image) => trim((string) $image) !== '');

                return $build['title'] !== '' || $hasImage;
            })
            ->values();

        if ($featuredBuildItems->isEmpty()) {
            $featuredBuildItems = collect($fallbackFeaturedBuilds);
        }

        $fallbackReviews = [
            [
                'title' => 'Fast and reliable service',
                'content' => 'Great pricing and smooth transaction from inquiry to delivery.',
                'author_name' => 'Infinite Customer',
                'rating' => 5,
            ],
            [
                'title' => 'Excellent build quality',
                'content' => 'The unit arrived clean, tested, and ready to use. Highly recommended.',
                'author_name' => 'Satisfied Buyer',
                'rating' => 5,
            ],
            [
                'title' => 'Helpful support team',
                'content' => 'They answered all my questions quickly and suggested the right parts.',
                'author_name' => 'PC Enthusiast',
                'rating' => 5,
            ],
            [
                'title' => 'Good value for money',
                'content' => 'Performance is solid for the budget and after-sales support is responsive.',
                'author_name' => 'Verified Customer',
                'rating' => 5,
            ],
        ];

        $reviewItems = collect($reviews ?? [])
            ->map(fn ($review) => [
                'title' => trim((string) ($review->title ?? '')),
                'content' => trim((string) ($review->content ?? '')),
                'author_name' => trim((string) ($review->author_name ?? '')),
                'rating' => (int) ($review->rating ?? 5),
            ])
            ->values();

        if ($reviewItems->isEmpty()) {
            $reviewItems = collect($fallbackReviews);
        }
    @endphp

    <section class="theme-dark-section full-bleed-hero relative overflow-hidden shadow-xl"
        x-data="{
            slides: @js($carouselItems),
            active: 0,
            timer: null,
            delayMs: 10000,
            start() {
                this.stop();
                if (this.slides.length < 2) return;
                this.timer = setInterval(() => this.advance(), this.delayMs);
            },
            stop() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },
            restart() {
                this.start();
            },
            advance() {
                if (this.slides.length < 2) return;
                this.active = (this.active + 1) % this.slides.length;
            },
            next() {
                this.advance();
                this.restart();
            },
            prev() {
                if (this.slides.length < 2) return;
                this.active = (this.active - 1 + this.slides.length) % this.slides.length;
                this.restart();
            },
            goTo(index) {
                if (!this.slides.length) return;
                const max = this.slides.length - 1;
                this.active = Math.max(0, Math.min(max, Number(index) || 0));
                this.restart();
            },
        }"
        x-init="start()">
        <div class="home-hero-orb-primary pointer-events-none absolute -top-24 right-0 h-64 w-64 rounded-full blur-3xl"></div>
        <div class="home-hero-orb-secondary pointer-events-none absolute -bottom-20 left-10 h-56 w-56 rounded-full blur-3xl"></div>

        <div class="relative w-full overflow-hidden h-[260px] sm:h-[340px] lg:h-auto lg:aspect-[2560/720]">
            <template x-for="(slide, index) in slides" :key="index">
                <article x-show="active === index" x-transition.opacity.duration.500ms class="absolute inset-0">
                    <div class="home-hero-base absolute inset-0"></div>
                    <template x-if="slide.image">
                        <div class="absolute inset-0">
                            <img :src="slide.image" :alt="`Banner slide ${index + 1}`"
                                :loading="index === 0 ? 'eager' : 'lazy'"
                                :fetchpriority="index === 0 ? 'high' : 'auto'"
                                decoding="async"
                                referrerpolicy="no-referrer"
                                onerror="this.style.display='none';"
                                class="absolute inset-0 h-full w-full object-cover object-center home-hero-slide-image">
                        </div>
                    </template>
                </article>
            </template>

            <div class="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2">
                <template x-for="(slide, index) in slides" :key="`dot_${index}`">
                    <button type="button"
                        class="h-2.5 rounded-full transition-all"
                        :class="active === index ? 'w-8 home-carousel-dot-active' : 'w-2.5 home-carousel-dot-idle'"
                        :aria-label="`Go to banner ${index + 1}`"
                        @click="goTo(index)"></button>
                </template>
            </div>
        </div>
    </section>

    <section id="featured-brands" class="featured-brands-strip full-bleed-hero relative overflow-hidden py-4 sm:py-5">
        <div class="featured-brands-marquee">
            <div class="featured-brands-track featured-brands-track--logos">
                @foreach ($brandMarqueeItems as $brand)
                    <div class="featured-brand-logo-item">
                        @if (! empty($brand['logo']))
                            <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }} logo"
                                class="h-8 w-auto max-w-none object-contain sm:h-10">
                        @else
                            <span class="featured-brand-logo-text">
                                {{ $brand['name'] }}
                            </span>
                        @endif
                    </div>
                @endforeach

                @foreach ($brandMarqueeItems as $brand)
                    <div aria-hidden="true" class="featured-brand-logo-item">
                        @if (! empty($brand['logo']))
                            <img src="{{ $brand['logo'] }}" alt=""
                                class="h-8 w-auto max-w-none object-contain sm:h-10">
                        @else
                            <span class="featured-brand-logo-text">
                                {{ $brand['name'] }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="flex flex-col">
    <section class="relative mx-auto mt-8 order-2 w-full max-w-[1600px] px-4 sm:px-6 lg:px-8"
        x-data="{
            scrollLeft() {
                this.$refs.track.scrollBy({ left: -320, behavior: 'smooth' });
            },
            scrollRight() {
                this.$refs.track.scrollBy({ left: 320, behavior: 'smooth' });
            },
        }">
        <div class="home-row-carousel relative">
            <button type="button"
                class="theme-carousel-arrow home-row-arrow home-row-arrow--left absolute top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                aria-label="Scroll categories left"
                @click="scrollLeft()">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                </svg>
            </button>

            <button type="button"
                class="theme-carousel-arrow home-row-arrow home-row-arrow--right absolute top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                aria-label="Scroll categories right"
                @click="scrollRight()">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-ref="track"
                class="overflow-x-auto scroll-smooth sm:mx-8 [&::-webkit-scrollbar]:hidden"
                style="-ms-overflow-style: none; scrollbar-width: none;">
                <div class="flex min-w-max gap-4">
                    @foreach ($categoryCards as $category)
                        <a href="{{ route('pricelist', ['category' => $category['id']]) }}"
                            class="theme-card theme-card-hover group w-40 shrink-0 overflow-hidden rounded-2xl shadow-sm sm:w-44">
                            <div class="theme-image-wrap relative flex h-32 items-center justify-center px-4 pt-4">
                                @if ($category['image_url'] !== '')
                                    <img src="{{ $category['image_url'] }}" alt="{{ $category['name'] }}"
                                        referrerpolicy="no-referrer" loading="lazy"
                                        onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');"
                                        class="h-20 w-auto object-contain drop-shadow-[0_12px_8px_rgba(17,24,39,0.22)] transition duration-300 ease-out group-hover:scale-105">
                                    <div class="theme-image-placeholder hidden h-20 w-full items-center justify-center rounded-lg text-[11px] font-medium uppercase tracking-wide">
                                        Add image URL
                                    </div>
                                @else
                                    <div class="theme-image-placeholder flex h-20 w-full items-center justify-center rounded-lg text-[11px] font-medium uppercase tracking-wide">
                                        Add image URL
                                    </div>
                                @endif
                            </div>
                            <div class="theme-muted flex h-12 items-center justify-center border-t border-slate-200/70 bg-white px-3 text-center text-sm font-semibold uppercase leading-tight tracking-tight">
                                {{ $category['name'] }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="bundle-builds" class="mx-auto mt-8 order-1 w-full max-w-[1600px] px-4 sm:px-6 lg:px-8"
        x-data="{
            activeBuild: 'entry',
            entryBundleCards: @js($entryBundleCards->values()),
            gamingBundleCards: @js($gamingBundleCards->values()),
            entryIndex: 0,
            gamingIndex: 0,
            entryIsTransitioning: false,
            gamingIsTransitioning: false,
            fadeDurationMs: 260,
            fadeStartOpacity: 0.68,
            bundleModal: {
                open: false,
                image: '',
                title: '',
            },
            initializeBundles() {
                this.entryBundleCards = this.ensureBundleCards(this.entryBundleCards);
                this.gamingBundleCards = this.ensureBundleCards(this.gamingBundleCards);
            },
            ensureBundleCards(cards) {
                if (!Array.isArray(cards) || cards.length === 0) {
                    return [{ image_url: '', link_url: '' }];
                }
                return cards.map((card) => ({
                    image_url: String(card?.image_url || ''),
                    link_url: String(card?.link_url || ''),
                }));
            },
            indexKey(type) {
                return type === 'entry' ? 'entryIndex' : 'gamingIndex';
            },
            transitionLockKey(type) {
                return type === 'entry' ? 'entryIsTransitioning' : 'gamingIsTransitioning';
            },
            trackRefKey(type) {
                return type === 'entry' ? 'entryTrack' : 'gamingTrack';
            },
            bundleCards(type) {
                return type === 'entry' ? this.entryBundleCards : this.gamingBundleCards;
            },
            transitionToBundle(type, nextIndex) {
                const cards = this.bundleCards(type);
                if (!cards.length) return;
                const key = this.indexKey(type);
                const lockKey = this.transitionLockKey(type);
                if (cards.length < 2) {
                    this[key] = 0;
                    return;
                }
                const maxIndex = cards.length - 1;
                const safeIndex = Math.max(0, Math.min(maxIndex, Number(nextIndex) || 0));
                const currentIndex = this.normalizeIndex(type);
                if (safeIndex === currentIndex || this[lockKey]) return;

                const track = this.$refs[this.trackRefKey(type)];
                this[lockKey] = true;

                if (!track) {
                    this[key] = safeIndex;
                    this[lockKey] = false;
                    return;
                }

                track.style.transition = `opacity ${this.fadeDurationMs}ms ease-in-out`;
                track.style.opacity = String(this.fadeStartOpacity);

                setTimeout(() => {
                    this[key] = safeIndex;
                    requestAnimationFrame(() => {
                        track.style.opacity = '1';
                        setTimeout(() => {
                            track.style.transition = '';
                            this[lockKey] = false;
                        }, this.fadeDurationMs);
                    });
                }, this.fadeDurationMs);
            },
            normalizeIndex(type) {
                const cards = this.bundleCards(type);
                const key = this.indexKey(type);
                if (!cards.length) {
                    this[key] = 0;
                    return 0;
                }
                if (this[key] >= cards.length) this[key] = 0;
                if (this[key] < 0) this[key] = cards.length - 1;
                return this[key];
            },
            currentBundle(type) {
                const cards = this.bundleCards(type);
                if (!cards.length) {
                    return { image_url: '', link_url: '' };
                }
                return cards[this.normalizeIndex(type)] || cards[0];
            },
            setBundle(type, index) {
                const cards = this.bundleCards(type);
                if (!cards.length) return;
                const maxIndex = cards.length - 1;
                const nextIndex = Math.max(0, Math.min(maxIndex, Number(index) || 0));
                this.transitionToBundle(type, nextIndex);
            },
            nextBundle(type) {
                const cards = this.bundleCards(type);
                if (cards.length < 2) return;
                const nextIndex = (this.normalizeIndex(type) + 1) % cards.length;
                this.transitionToBundle(type, nextIndex);
            },
            prevBundle(type) {
                const cards = this.bundleCards(type);
                if (cards.length < 2) return;
                const nextIndex = (this.normalizeIndex(type) - 1 + cards.length) % cards.length;
                this.transitionToBundle(type, nextIndex);
            },
            bundleAt(type, offset = 0) {
                const cards = this.bundleCards(type);
                if (!cards.length) {
                    return { image_url: '', link_url: '' };
                }
                const current = this.normalizeIndex(type);
                const nextIndex = (current + Number(offset || 0) + cards.length) % cards.length;
                return cards[nextIndex] || cards[0];
            },
            bundleImage(type, offset = 0) {
                return String(this.bundleAt(type, offset).image_url || '');
            },
            bundleHasImage(type, offset = 0) {
                return this.bundleImage(type, offset) !== '';
            },
            formatCounter(value) {
                return String(Math.max(0, Number(value || 0))).padStart(2, '0');
            },
            counterCurrent(type) {
                return this.formatCounter(this.normalizeIndex(type) + 1);
            },
            counterTotal(type) {
                return this.formatCounter(this.bundleCards(type).length || 0);
            },
            openBundleModal(imageUrl, title) {
                this.bundleModal.image = String(imageUrl || '');
                this.bundleModal.title = String(title || 'Build');
                this.bundleModal.open = true;
                document.body.classList.add('overflow-hidden');
            },
            closeBundleModal() {
                this.bundleModal.open = false;
                document.body.classList.remove('overflow-hidden');
            },
        }"
        x-init="initializeBundles()"
        @keydown.escape.window="closeBundleModal()">
        <div class="relative">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <span
                        class="home-build-pill inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em]"
                        x-text="activeBuild === 'entry' ? 'Starter Tier' : 'Performance Tier'"></span>
                    <h3 class="home-build-title mt-2 text-3xl font-semibold tracking-tight"
                        x-text="activeBuild === 'entry' ? 'ENTRY BUILD' : 'GAMING BUILD'"></h3>
                    <p class="home-build-subtitle mt-1 text-sm font-medium"
                        x-text="activeBuild === 'entry'
                            ? 'Practical picks for office, school, and daily work.'
                            : 'High-refresh, graphics-ready setups for serious play.'"></p>
                </div>

                <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                    <div class="home-build-toggle-wrap inline-flex rounded-full p-1 shadow-sm">
                        <button type="button"
                            class="rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] transition"
                            :class="activeBuild === 'entry' ? 'home-build-toggle-active shadow-sm' : 'home-build-toggle-idle'"
                            @click="activeBuild = 'entry'">
                            Entry
                        </button>
                        <button type="button"
                            class="rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] transition"
                            :class="activeBuild === 'gaming' ? 'home-build-toggle-active shadow-sm' : 'home-build-toggle-idle'"
                            @click="activeBuild = 'gaming'">
                            Gaming
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="activeBuild === 'entry'">
                <section class="home-coverflow-shell">
                    <div class="home-coverflow-stage">
                        <button type="button"
                            class="home-coverflow-arrow home-coverflow-arrow--left"
                            :disabled="entryBundleCards.length < 2"
                            aria-label="Previous entry bundle"
                            @click="prevBundle('entry')">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-ref="entryTrack" class="home-coverflow-track"
                            :class="entryBundleCards.length < 2 ? 'home-coverflow-track--single' : ''">
                            <button type="button"
                                x-show="entryBundleCards.length > 1"
                                class="home-coverflow-card home-coverflow-card--side home-coverflow-card--left"
                                @click="prevBundle('entry')">
                                <div class="home-bundle-media home-bundle-media-size theme-image-wrap relative"
                                    :class="bundleHasImage('entry', -1) ? 'home-bundle-media--filled' : ''">
                                    <template x-if="bundleHasImage('entry', -1)">
                                        <img :src="bundleImage('entry', -1)" alt="Entry previous bundle"
                                            referrerpolicy="no-referrer" loading="lazy"
                                            class="home-bundle-img h-full w-full object-cover object-center">
                                    </template>
                                    <template x-if="!bundleHasImage('entry', -1)">
                                        <div class="flex h-full w-full items-center justify-center border border-dashed border-white/25 bg-white/10 px-2 text-center">
                                            <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-white/80">Upload image</span>
                                        </div>
                                    </template>
                                </div>
                            </button>

                            <button type="button"
                                class="home-coverflow-card home-coverflow-card--center"
                                @click="openBundleModal(bundleImage('entry', 0), 'Entry Build')">
                                <div class="home-bundle-media home-bundle-media-size theme-image-wrap relative"
                                    :class="bundleHasImage('entry', 0) ? 'home-bundle-media--filled' : ''">
                                    <template x-if="bundleHasImage('entry', 0)">
                                        <img :src="bundleImage('entry', 0)" alt="Entry featured bundle"
                                            referrerpolicy="no-referrer" loading="lazy"
                                            class="home-bundle-img h-full w-full object-cover object-center">
                                    </template>
                                    <template x-if="!bundleHasImage('entry', 0)">
                                        <div class="flex h-full w-full flex-col items-center justify-center gap-2 border border-dashed border-white/25 bg-white/10 px-4 text-center">
                                            <span class="rounded-full border border-white/20 bg-white/15 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-white/80">Featured Card</span>
                                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-white/80">Upload bundle image URL</span>
                                        </div>
                                    </template>
                                </div>
                            </button>

                            <button type="button"
                                x-show="entryBundleCards.length > 1"
                                class="home-coverflow-card home-coverflow-card--side home-coverflow-card--right"
                                @click="nextBundle('entry')">
                                <div class="home-bundle-media home-bundle-media-size theme-image-wrap relative"
                                    :class="bundleHasImage('entry', 1) ? 'home-bundle-media--filled' : ''">
                                    <template x-if="bundleHasImage('entry', 1)">
                                        <img :src="bundleImage('entry', 1)" alt="Entry next bundle"
                                            referrerpolicy="no-referrer" loading="lazy"
                                            class="home-bundle-img h-full w-full object-cover object-center">
                                    </template>
                                    <template x-if="!bundleHasImage('entry', 1)">
                                        <div class="flex h-full w-full items-center justify-center border border-dashed border-white/25 bg-white/10 px-2 text-center">
                                            <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-white/80">Upload image</span>
                                        </div>
                                    </template>
                                </div>
                            </button>
                        </div>

                        <button type="button"
                            class="home-coverflow-arrow home-coverflow-arrow--right"
                            :disabled="entryBundleCards.length < 2"
                            aria-label="Next entry bundle"
                            @click="nextBundle('entry')">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <div class="home-coverflow-dots">
                        <template x-for="(bundle, idx) in entryBundleCards" :key="`entry_dot_${idx}`">
                            <button type="button"
                                class="home-coverflow-dot"
                                :class="idx === entryIndex ? 'home-coverflow-dot--active' : ''"
                                :aria-label="`Go to entry bundle ${idx + 1}`"
                                @click="setBundle('entry', idx)"></button>
                        </template>
                    </div>
                </section>
            </div>

            <div x-show="activeBuild === 'gaming'">
                <section class="home-coverflow-shell">
                    <div class="home-coverflow-stage">
                        <button type="button"
                            class="home-coverflow-arrow home-coverflow-arrow--left"
                            :disabled="gamingBundleCards.length < 2"
                            aria-label="Previous gaming bundle"
                            @click="prevBundle('gaming')">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-ref="gamingTrack" class="home-coverflow-track"
                            :class="gamingBundleCards.length < 2 ? 'home-coverflow-track--single' : ''">
                            <button type="button"
                                x-show="gamingBundleCards.length > 1"
                                class="home-coverflow-card home-coverflow-card--side home-coverflow-card--left"
                                @click="prevBundle('gaming')">
                                <div class="home-bundle-media home-bundle-media-size theme-image-wrap relative"
                                    :class="bundleHasImage('gaming', -1) ? 'home-bundle-media--filled' : ''">
                                    <template x-if="bundleHasImage('gaming', -1)">
                                        <img :src="bundleImage('gaming', -1)" alt="Gaming previous bundle"
                                            referrerpolicy="no-referrer" loading="lazy"
                                            class="home-bundle-img h-full w-full object-cover object-center">
                                    </template>
                                    <template x-if="!bundleHasImage('gaming', -1)">
                                        <div class="flex h-full w-full items-center justify-center border border-dashed border-white/25 bg-white/10 px-2 text-center">
                                            <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-white/80">Upload image</span>
                                        </div>
                                    </template>
                                </div>
                            </button>

                            <button type="button"
                                class="home-coverflow-card home-coverflow-card--center"
                                @click="openBundleModal(bundleImage('gaming', 0), 'Gaming Build')">
                                <div class="home-bundle-media home-bundle-media-size theme-image-wrap relative"
                                    :class="bundleHasImage('gaming', 0) ? 'home-bundle-media--filled' : ''">
                                    <template x-if="bundleHasImage('gaming', 0)">
                                        <img :src="bundleImage('gaming', 0)" alt="Gaming featured bundle"
                                            referrerpolicy="no-referrer" loading="lazy"
                                            class="home-bundle-img h-full w-full object-cover object-center">
                                    </template>
                                    <template x-if="!bundleHasImage('gaming', 0)">
                                        <div class="flex h-full w-full flex-col items-center justify-center gap-2 border border-dashed border-white/25 bg-white/10 px-4 text-center">
                                            <span class="rounded-full border border-white/20 bg-white/15 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-white/80">Featured Card</span>
                                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-white/80">Upload bundle image URL</span>
                                        </div>
                                    </template>
                                </div>
                            </button>

                            <button type="button"
                                x-show="gamingBundleCards.length > 1"
                                class="home-coverflow-card home-coverflow-card--side home-coverflow-card--right"
                                @click="nextBundle('gaming')">
                                <div class="home-bundle-media home-bundle-media-size theme-image-wrap relative"
                                    :class="bundleHasImage('gaming', 1) ? 'home-bundle-media--filled' : ''">
                                    <template x-if="bundleHasImage('gaming', 1)">
                                        <img :src="bundleImage('gaming', 1)" alt="Gaming next bundle"
                                            referrerpolicy="no-referrer" loading="lazy"
                                            class="home-bundle-img h-full w-full object-cover object-center">
                                    </template>
                                    <template x-if="!bundleHasImage('gaming', 1)">
                                        <div class="flex h-full w-full items-center justify-center border border-dashed border-white/25 bg-white/10 px-2 text-center">
                                            <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-white/80">Upload image</span>
                                        </div>
                                    </template>
                                </div>
                            </button>
                        </div>

                        <button type="button"
                            class="home-coverflow-arrow home-coverflow-arrow--right"
                            :disabled="gamingBundleCards.length < 2"
                            aria-label="Next gaming bundle"
                            @click="nextBundle('gaming')">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <div class="home-coverflow-dots">
                        <template x-for="(bundle, idx) in gamingBundleCards" :key="`gaming_dot_${idx}`">
                            <button type="button"
                                class="home-coverflow-dot"
                                :class="idx === gamingIndex ? 'home-coverflow-dot--active' : ''"
                                :aria-label="`Go to gaming bundle ${idx + 1}`"
                                @click="setBundle('gaming', idx)"></button>
                        </template>
                    </div>
                </section>
            </div>
        </div>

        <template x-teleport="body">
            <div x-cloak x-show="bundleModal.open"
                class="fixed inset-0 z-[90] flex items-center justify-center p-4 sm:p-6"
                role="dialog" aria-modal="true" aria-label="Build card preview"
                @click.self="closeBundleModal()">
                <div class="absolute inset-0 bg-black/45 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/15">
                    <div class="flex items-center justify-between border-b border-black/10 px-4 py-3 sm:px-5">
                        <h2 class="text-base font-semibold text-gray-900 sm:text-lg" x-text="bundleModal.title || 'Build Preview'"></h2>
                        <button type="button"
                            class="rounded-md border border-black/10 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                            @click="closeBundleModal()">
                            Close
                        </button>
                    </div>
                    <div class="max-h-[80vh] overflow-auto bg-gray-100 p-2 sm:p-3">
                        <template x-if="bundleModal.image">
                            <img :src="bundleModal.image" alt="Build preview image"
                                referrerpolicy="no-referrer"
                                class="mx-auto h-auto max-h-[72vh] w-auto max-w-full rounded-xl object-contain">
                        </template>
                        <template x-if="!bundleModal.image">
                            <div class="theme-muted flex h-[52vh] items-center justify-center rounded-xl border border-dashed border-black/20 bg-white text-sm font-medium uppercase tracking-wide">
                                Add bundle image URL
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </section>

    <section id="featured-builds" class="relative mx-auto mt-8 order-2 w-full max-w-[1600px] px-4 sm:px-6 lg:px-8"
        x-data="{
            scrollLeft() {
                this.$refs.featuredBuildTrack.scrollBy({ left: -420, behavior: 'smooth' });
            },
            scrollRight() {
                this.$refs.featuredBuildTrack.scrollBy({ left: 420, behavior: 'smooth' });
            },
        }">
        <div class="relative">
            <h3 class="home-reviews-title text-xl font-semibold uppercase tracking-tight sm:mx-8 sm:text-2xl">
                Featured Builds
            </h3>
            <p class="home-build-subtitle mt-1 text-sm font-medium sm:mx-8">
                Gallery of completed PCs built by Infinite Computers.
            </p>

            <div class="home-row-carousel relative mt-4">
                <button type="button"
                    class="theme-carousel-arrow home-row-arrow home-row-arrow--left absolute top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                    aria-label="Scroll featured builds left"
                    @click="scrollLeft()">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <button type="button"
                    class="theme-carousel-arrow home-row-arrow home-row-arrow--right absolute top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                    aria-label="Scroll featured builds right"
                    @click="scrollRight()">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-ref="featuredBuildTrack"
                    class="overflow-x-auto scroll-smooth sm:mx-8 [&::-webkit-scrollbar]:hidden"
                    style="-ms-overflow-style: none; scrollbar-width: none;">
                    <div class="flex min-w-max gap-4">
                        @foreach ($featuredBuildItems as $build)
                            <article class="theme-card w-[20rem] shrink-0 overflow-hidden rounded-2xl p-3 shadow-sm sm:w-[23rem] lg:w-[24rem]"
                                x-data="{
                                    images: @js($build['images']),
                                    activeImage: 0,
                                    hasImage(index) {
                                        return String(this.images[index] || '').trim() !== '';
                                    },
                                    hasMultipleImages() {
                                        return this.images.filter((image) => String(image || '').trim() !== '').length > 1;
                                    },
                                    findNextImageIndex(step) {
                                        if (!this.hasMultipleImages()) {
                                            return this.activeImage;
                                        }

                                        let nextIndex = this.activeImage;

                                        for (let i = 0; i < this.images.length; i += 1) {
                                            nextIndex = (nextIndex + step + this.images.length) % this.images.length;

                                            if (this.hasImage(nextIndex)) {
                                                return nextIndex;
                                            }
                                        }

                                        return this.activeImage;
                                    },
                                    selectImage(index) {
                                        this.activeImage = Number(index) || 0;
                                    },
                                    prevImage() {
                                        this.activeImage = this.findNextImageIndex(-1);
                                    },
                                    nextImage() {
                                        this.activeImage = this.findNextImageIndex(1);
                                    },
                                }">
                                <div class="theme-image-wrap relative overflow-hidden rounded-xl">
                                    <div class="aspect-[16/10]">
                                        <template x-if="hasImage(activeImage)">
                                            <button type="button"
                                                class="group relative block h-full w-full cursor-zoom-in"
                                                @click="$refs.homeFeaturedBuildDialog.showModal(); document.body.classList.add('overflow-hidden')">
                                                <img :src="images[activeImage]"
                                                    alt="{{ $build['title'] !== '' ? $build['title'] : 'Featured build image' }}"
                                                    referrerpolicy="no-referrer" loading="lazy"
                                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]">
                                                <span class="absolute bottom-3 right-3 inline-flex items-center rounded-full bg-black/60 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-white">
                                                    Click to enlarge
                                                </span>
                                            </button>
                                        </template>
                                        <template x-if="!hasImage(activeImage)">
                                            <div class="theme-image-placeholder flex h-full w-full items-center justify-center px-4 text-center text-[11px] font-medium uppercase tracking-wide">
                                                Add featured build image
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button"
                                        class="absolute left-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 bg-black/35 text-white"
                                        aria-label="Previous build image"
                                        @click="prevImage()">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button type="button"
                                        class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 bg-black/35 text-white"
                                        aria-label="Next build image"
                                        @click="nextImage()">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-2 grid grid-cols-5 gap-1.5">
                                    <template x-for="(image, idx) in images" :key="idx">
                                        <button type="button"
                                            class="theme-image-wrap relative overflow-hidden rounded-md border border-black/10"
                                            :class="activeImage === idx ? 'ring-2 ring-orange-500/70' : ''"
                                            @click="selectImage(idx)">
                                            <div class="aspect-[4/3]">
                                                <template x-if="hasImage(idx)">
                                                    <img :src="image"
                                                        alt="{{ $build['title'] !== '' ? $build['title'] : 'Featured build thumbnail' }}"
                                                        referrerpolicy="no-referrer" loading="lazy"
                                                        class="h-full w-full object-cover">
                                                </template>
                                                <template x-if="!hasImage(idx)">
                                                    <div class="theme-image-placeholder flex h-full w-full items-center justify-center text-[9px] font-semibold uppercase tracking-wide">
                                                        Empty
                                                    </div>
                                                </template>
                                            </div>
                                        </button>
                                    </template>
                                </div>

                                <div class="theme-muted mt-2 flex h-12 items-center justify-center rounded-xl border border-slate-200/70 bg-white px-3 text-center text-sm font-semibold uppercase leading-tight tracking-tight">
                                    {{ $build['title'] !== '' ? $build['title'] : 'Featured Build' }}
                                </div>

                                <dialog x-ref="homeFeaturedBuildDialog"
                                    class="featured-build-dialog"
                                    @click="if ($event.target === $refs.homeFeaturedBuildDialog) $refs.homeFeaturedBuildDialog.close()"
                                    @keydown.left.window="if ($refs.homeFeaturedBuildDialog.open && hasMultipleImages()) prevImage()"
                                    @keydown.right.window="if ($refs.homeFeaturedBuildDialog.open && hasMultipleImages()) nextImage()"
                                    @close="document.body.classList.remove('overflow-hidden')"
                                    @cancel="document.body.classList.remove('overflow-hidden')">
                                    <div class="featured-build-dialog__shell">
                                        <button type="button"
                                            class="featured-build-dialog__close"
                                            @click="$refs.homeFeaturedBuildDialog.close()">
                                            Close
                                        </button>

                                        <button type="button"
                                            class="featured-build-dialog__nav featured-build-dialog__nav--prev"
                                            x-show="hasMultipleImages()"
                                            @click="prevImage()"
                                            aria-label="Previous enlarged image">
                                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <img :src="images[activeImage]"
                                            alt="{{ $build['title'] !== '' ? $build['title'] : 'Featured build image' }}"
                                            referrerpolicy="no-referrer"
                                            class="featured-build-dialog__image">

                                        <button type="button"
                                            class="featured-build-dialog__nav featured-build-dialog__nav--next"
                                            x-show="hasMultipleImages()"
                                            @click="nextImage()"
                                            aria-label="Next enlarged image">
                                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div class="featured-build-dialog__title">
                                            {{ $build['title'] !== '' ? $build['title'] : 'Featured Build' }}
                                        </div>
                                    </div>
                                </dialog>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative mx-auto mt-8 order-3 w-full max-w-[1600px] px-4 sm:px-6 lg:px-8"
        x-data="{
            scrollLeft() {
                this.$refs.reviewTrack.scrollBy({ left: -380, behavior: 'smooth' });
            },
            scrollRight() {
                this.$refs.reviewTrack.scrollBy({ left: 380, behavior: 'smooth' });
            },
        }">
        <div class="relative">
            <h3 class="home-reviews-title text-xl font-semibold uppercase tracking-tight sm:mx-8 sm:text-2xl">
                Reviews
            </h3>

            <div class="home-row-carousel relative mt-4">
                <button type="button"
                    class="theme-carousel-arrow home-row-arrow home-row-arrow--left absolute top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                    aria-label="Scroll reviews left"
                    @click="scrollLeft()">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <button type="button"
                    class="theme-carousel-arrow home-row-arrow home-row-arrow--right absolute top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                    aria-label="Scroll reviews right"
                    @click="scrollRight()">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-ref="reviewTrack"
                    class="overflow-x-auto scroll-smooth sm:mx-8 [&::-webkit-scrollbar]:hidden"
                    style="-ms-overflow-style: none; scrollbar-width: none;">
                    <div class="flex min-w-max gap-4">
                        @foreach ($reviewItems as $review)
                            @php
                                $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                            @endphp
                            <article class="home-review-card w-[18rem] shrink-0 rounded-2xl p-5 text-center shadow-sm sm:w-[20rem] lg:w-[21rem]">
                                <div class="home-review-stars mb-3 flex items-center justify-center gap-1 text-lg leading-none">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <span class="{{ $star <= $rating ? '' : 'opacity-25' }}">&#9733;</span>
                                    @endfor
                                </div>
                                <h4 class="home-review-headline text-base font-semibold leading-snug">
                                    {{ $review['title'] !== '' ? $review['title'] : 'Customer Feedback' }}
                                </h4>
                                <p class="home-review-text mt-2 text-sm leading-relaxed">
                                    {{ $review['content'] !== '' ? $review['content'] : 'Great service and smooth transaction.' }}
                                </p>
                                <div class="home-review-author mt-5 text-sm font-medium">
                                    {{ $review['author_name'] !== '' ? $review['author_name'] : 'Anonymous' }}
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>

@endsection

@push('page-modals')
    <style>
        .featured-build-dialog {
            width: 100vw;
            max-width: none;
            height: 100vh;
            max-height: none;
            margin: 0;
            padding: 1rem;
            border: 0;
            background: transparent;
            overflow: hidden;
        }

        .featured-build-dialog::backdrop {
            background: rgba(2, 6, 23, 0.82);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .featured-build-dialog[open] {
            display: grid;
            place-items: center;
        }

        .featured-build-dialog__shell {
            position: relative;
            display: grid;
            place-items: center;
            width: 100%;
            height: 100%;
        }

        .featured-build-dialog__image {
            max-width: min(96vw, 1400px);
            max-height: min(84vh, calc(100vh - 7rem));
            width: auto;
            height: auto;
            border-radius: 1rem;
            object-fit: contain;
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.45);
        }

        .featured-build-dialog__close {
            position: absolute;
            top: 0;
            right: 0;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(15, 23, 42, 0.72);
            color: #fff;
            padding: 0.6rem 1rem;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .featured-build-dialog__title {
            position: absolute;
            left: 50%;
            bottom: 1rem;
            transform: translateX(-50%);
            border-radius: 9999px;
            background: rgba(15, 23, 42, 0.72);
            color: #fff;
            padding: 0.55rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            text-align: center;
        }

        .featured-build-dialog__nav {
            position: absolute;
            top: 50%;
            display: inline-flex;
            height: 3rem;
            width: 3rem;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            background: rgba(15, 23, 42, 0.72);
            color: #fff;
        }

        .featured-build-dialog__nav--prev {
            left: 0;
        }

        .featured-build-dialog__nav--next {
            right: 0;
        }

        @media (min-width: 640px) {
            .featured-build-dialog {
                padding: 1.5rem;
            }

            .featured-build-dialog__image {
                max-height: min(86vh, calc(100vh - 8rem));
            }

            .featured-build-dialog__nav--prev {
                left: 1rem;
            }

            .featured-build-dialog__nav--next {
                right: 1rem;
            }
        }
    </style>
@endpush
