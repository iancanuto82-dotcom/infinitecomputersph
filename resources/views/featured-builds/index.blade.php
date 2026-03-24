@extends('layouts.public')

@section('title', 'Featured Builds')
@section('meta_description', 'Gallery of completed PC builds by Infinite Computers.')

@section('content')
    @php
        $featuredBuildGalleryItems = collect($featuredBuildItems ?? [])
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
            ->values();

        if ($featuredBuildGalleryItems->isEmpty()) {
            $featuredBuildGalleryItems = collect([
                ['title' => 'Featured Build 1', 'images' => ['', '', '', '', '']],
                ['title' => 'Featured Build 2', 'images' => ['', '', '', '', '']],
                ['title' => 'Featured Build 3', 'images' => ['', '', '', '', '']],
            ]);
        }
    @endphp

    <section class="theme-panel relative z-20 rounded-2xl p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">Featured Builds</h1>
            <p class="theme-muted text-sm">
                Gallery of completed PCs built by Infinite Computers.
            </p>
        </div>
    </section>

    <section class="mt-6">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @foreach ($featuredBuildGalleryItems as $build)
                <article class="theme-card overflow-hidden rounded-2xl p-4 shadow-sm"
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
                                    @click="$refs.imageDialog.showModal(); document.body.classList.add('overflow-hidden')">
                                    <img :src="images[activeImage]"
                                        alt="{{ $build['title'] !== '' ? $build['title'] : 'Featured build image' }}"
                                        referrerpolicy="no-referrer" loading="lazy"
                                        class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]">
                                    <span class="absolute bottom-3 right-3 inline-flex items-center rounded-full bg-black/60 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-white">
                                        Click to enlarge
                                    </span>
                                    <span class="sr-only">Open enlarged image</span>
                                </button>
                            </template>
                            <template x-if="!hasImage(activeImage)">
                                <div class="theme-image-placeholder flex h-full w-full items-center justify-center px-4 text-center text-[11px] font-medium uppercase tracking-wide">
                                    Add featured build image
                                </div>
                            </template>
                        </div>
                        <button type="button"
                            class="absolute left-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 bg-black/35 text-white"
                            aria-label="Previous build image"
                            @click="prevImage()">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button type="button"
                            class="absolute right-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 bg-black/35 text-white"
                            aria-label="Next build image"
                            @click="nextImage()">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.22 15.78a.75.75 0 010-1.06L11.94 10 7.22 5.28a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-5 gap-2">
                        <template x-for="(image, idx) in images" :key="idx">
                            <button type="button"
                                class="theme-image-wrap relative overflow-hidden rounded-lg border border-black/10"
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
                                        <div class="theme-image-placeholder flex h-full w-full items-center justify-center text-[10px] font-semibold uppercase tracking-wide">
                                            Empty
                                        </div>
                                    </template>
                                </div>
                            </button>
                        </template>
                    </div>

                    <div class="theme-muted mt-3 flex h-12 items-center justify-center rounded-xl border border-slate-200/70 bg-white px-3 text-center text-sm font-semibold uppercase leading-tight tracking-tight">
                        {{ $build['title'] !== '' ? $build['title'] : 'Featured Build' }}
                    </div>

                    <dialog x-ref="imageDialog"
                        class="featured-build-dialog"
                        @click="if ($event.target === $refs.imageDialog) $refs.imageDialog.close()"
                        @keydown.left.window="if ($refs.imageDialog.open && hasMultipleImages()) prevImage()"
                        @keydown.right.window="if ($refs.imageDialog.open && hasMultipleImages()) nextImage()"
                        @close="document.body.classList.remove('overflow-hidden')"
                        @cancel="document.body.classList.remove('overflow-hidden')">
                        <div class="featured-build-dialog__shell">
                            <button type="button"
                                class="featured-build-dialog__close"
                                @click="$refs.imageDialog.close()">
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
    </section>
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
