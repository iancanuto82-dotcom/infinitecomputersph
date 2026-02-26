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
                'image_url' => (string) ($ad->image_url ?? ''),
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
                'image_url' => (string) ($ad->image_url ?? ''),
                'link_url' => (string) ($ad->link_url ?? ''),
            ])
            ->values();

        if ($gamingBundleCards->isEmpty()) {
            $gamingBundleCards = collect([
                ['image_url' => '', 'link_url' => ''],
                ['image_url' => '', 'link_url' => ''],
            ]);
        }
    @endphp

    <section class="theme-dark-section relative mx-auto w-full overflow-hidden rounded-3xl shadow-xl"
        x-data="{
            slides: @js($carouselItems),
            active: 0,
            timer: null,
            start() {
                this.stop();
                this.timer = setInterval(() => this.next(), 4500);
            },
            stop() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },
            next() {
                this.active = (this.active + 1) % this.slides.length;
            },
            prev() {
                this.active = (this.active - 1 + this.slides.length) % this.slides.length;
            },
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()">
        <div class="pointer-events-none absolute -top-24 right-0 h-64 w-64 rounded-full bg-orange-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 left-10 h-56 w-56 rounded-full bg-orange-300/20 blur-3xl"></div>

        <div class="relative w-full overflow-hidden aspect-[10/3]">
            <template x-for="(slide, index) in slides" :key="index">
                <article x-show="active === index" x-transition.opacity.duration.500ms class="absolute inset-0">
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-700 to-gray-900"></div>
                    <template x-if="slide.image">
                        <div class="absolute inset-0">
                            <img :src="slide.image" alt=""
                                referrerpolicy="no-referrer"
                                onerror="this.style.display='none';"
                                class="absolute inset-0 h-full w-full scale-110 object-cover object-center blur-xl"
                                aria-hidden="true">
                            <div class="absolute inset-0 bg-black/20"></div>
                            <img :src="slide.image" :alt="`Banner slide ${index + 1}`"
                                referrerpolicy="no-referrer"
                                onerror="this.style.display='none';"
                                class="absolute inset-0 h-full w-full object-contain object-center">
                        </div>
                    </template>
                </article>
            </template>

            <button type="button"
                class="theme-carousel-arrow absolute left-3 top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                aria-label="Previous banner"
                @click="prev()">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                </svg>
            </button>

            <button type="button"
                class="theme-carousel-arrow absolute right-3 top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                aria-label="Next banner"
                @click="next()">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                </svg>
            </button>

            <div class="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2">
                <template x-for="(slide, index) in slides" :key="`dot_${index}`">
                    <button type="button"
                        class="h-2.5 rounded-full transition-all"
                        :class="active === index ? 'w-8 bg-orange-400' : 'w-2.5 bg-white/55 hover:bg-white/80'"
                        :aria-label="`Go to banner ${index + 1}`"
                        @click="active = index"></button>
                </template>
            </div>
        </div>
    </section>

    <div class="flex flex-col">
    <section class="mt-8 order-2"
        x-data="{
            scrollLeft() {
                this.$refs.track.scrollBy({ left: -320, behavior: 'smooth' });
            },
            scrollRight() {
                this.$refs.track.scrollBy({ left: 320, behavior: 'smooth' });
            },
        }">
        <div class="theme-panel relative rounded-3xl p-4 shadow-sm sm:p-5">
            <button type="button"
                class="theme-carousel-arrow absolute left-2 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                aria-label="Scroll categories left"
                @click="scrollLeft()">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                </svg>
            </button>

            <button type="button"
                class="theme-carousel-arrow absolute right-2 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
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
                            <div class="theme-muted px-3 pb-4 text-center text-sm font-semibold uppercase leading-tight tracking-tight">
                                {{ $category['name'] }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8 order-1 grid grid-cols-1 gap-5 lg:grid-cols-2"
        x-data="{
            bundleModal: {
                open: false,
                image: '',
                title: '',
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
        @keydown.escape.window="closeBundleModal()">
        <div class="theme-dark-section relative overflow-hidden rounded-3xl p-5 shadow-xl sm:p-6"
            x-data="{
                bundleLeft() {
                    this.$refs.bundleTrack.scrollBy({ left: -360, behavior: 'smooth' });
                },
                bundleRight() {
                    this.$refs.bundleTrack.scrollBy({ left: 360, behavior: 'smooth' });
                },
            }">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(251,146,60,0.22),transparent_42%)]"></div>
            <div class="relative">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-3xl font-semibold tracking-tight text-white">ENTRY BUILD</h3>
                </div>

                <button type="button"
                    class="theme-carousel-arrow absolute -left-1 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                    aria-label="Scroll entry bundles left"
                    @click="bundleLeft()">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <button type="button"
                    class="theme-carousel-arrow absolute -right-1 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                    aria-label="Scroll entry bundles right"
                    @click="bundleRight()">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-ref="bundleTrack"
                    class="overflow-x-auto scroll-smooth sm:mx-8 [&::-webkit-scrollbar]:hidden"
                    style="-ms-overflow-style: none; scrollbar-width: none;">
                    <div class="flex min-w-max gap-4 py-1">
                        @foreach ($entryBundleCards as $bundle)
                            <button type="button"
                                class="theme-card theme-card-hover w-72 shrink-0 overflow-hidden rounded-2xl text-left shadow-lg"
                                @click="openBundleModal(@js((string) ($bundle['image_url'] ?? '')), 'Entry Build')">
                                <div class="theme-image-wrap theme-image-placeholder relative h-[340px]">
                                    @if (! empty($bundle['image_url']))
                                        <img src="{{ $bundle['image_url'] }}" alt="Entry build ad"
                                            referrerpolicy="no-referrer" loading="lazy"
                                            onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');"
                                            class="h-full w-full object-cover">
                                        <div class="theme-muted hidden h-full w-full items-center justify-center text-xs font-semibold uppercase tracking-wide">
                                            Add bundle image URL
                                        </div>
                                    @else
                                        <div class="theme-muted flex h-full w-full items-center justify-center text-xs font-semibold uppercase tracking-wide">
                                            Add bundle image URL
                                        </div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="theme-dark-section relative overflow-hidden rounded-3xl p-5 shadow-xl sm:p-6"
            x-data="{
                bundleLeft() {
                    this.$refs.bundleTrack.scrollBy({ left: -360, behavior: 'smooth' });
                },
                bundleRight() {
                    this.$refs.bundleTrack.scrollBy({ left: 360, behavior: 'smooth' });
                },
            }">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_80%_15%,rgba(251,146,60,0.22),transparent_45%)]"></div>
            <div class="relative">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-3xl font-semibold tracking-tight text-white">GAMING BUILD</h3>
                </div>

                <button type="button"
                    class="theme-carousel-arrow absolute -left-1 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                    aria-label="Scroll gaming bundles left"
                    @click="bundleLeft()">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <button type="button"
                    class="theme-carousel-arrow absolute -right-1 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full shadow-sm sm:inline-flex"
                    aria-label="Scroll gaming bundles right"
                    @click="bundleRight()">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-ref="bundleTrack"
                    class="overflow-x-auto scroll-smooth sm:mx-8 [&::-webkit-scrollbar]:hidden"
                    style="-ms-overflow-style: none; scrollbar-width: none;">
                    <div class="flex min-w-max gap-4 py-1">
                        @foreach ($gamingBundleCards as $bundle)
                            <button type="button"
                                class="theme-card theme-card-hover w-72 shrink-0 overflow-hidden rounded-2xl text-left shadow-lg"
                                @click="openBundleModal(@js((string) ($bundle['image_url'] ?? '')), 'Gaming Build')">
                                <div class="theme-image-wrap theme-image-placeholder relative h-[340px]">
                                    @if (! empty($bundle['image_url']))
                                        <img src="{{ $bundle['image_url'] }}" alt="Gaming build ad"
                                            referrerpolicy="no-referrer" loading="lazy"
                                            onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');"
                                            class="h-full w-full object-cover">
                                        <div class="theme-muted hidden h-full w-full items-center justify-center text-xs font-semibold uppercase tracking-wide">
                                            Add bundle image URL
                                        </div>
                                    @else
                                        <div class="theme-muted flex h-full w-full items-center justify-center text-xs font-semibold uppercase tracking-wide">
                                            Add bundle image URL
                                        </div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
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
    </div>

    <section id="featured-brands" class="theme-dark-section relative mt-8 overflow-hidden rounded-3xl p-5 shadow-xl sm:p-7">
        <div class="pointer-events-none absolute -top-20 left-1/2 h-56 w-56 -translate-x-1/2 rounded-full bg-orange-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 right-10 h-56 w-56 rounded-full bg-orange-500/20 blur-3xl"></div>

        <div class="relative">
            <div class="text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Featured Brands</h2>
                <p class="theme-soft-text mt-2 text-sm sm:text-xl">
                    We carry components from the world's leading manufacturers
                </p>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                @foreach ($brandItems as $brand)
                    <div class="theme-card theme-card-hover group relative flex min-h-[80px] items-center justify-center overflow-hidden rounded-2xl px-3 py-3 text-center shadow-lg sm:min-h-[88px]">
                        @if (! empty($brand['logo']))
                            <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }} logo"
                                class="mx-auto h-8 w-full max-w-full object-contain transition duration-300 ease-out group-hover:scale-105 sm:h-10">
                        @else
                            <span class="block w-full truncate text-lg font-black uppercase tracking-tight text-gray-950 transition duration-300 ease-out group-hover:scale-105 sm:text-xl">
                                {{ $brand['name'] }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
