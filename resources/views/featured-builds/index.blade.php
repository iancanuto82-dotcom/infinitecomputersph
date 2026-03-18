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
                        selectImage(index) {
                            this.activeImage = Number(index) || 0;
                        },
                        prevImage() {
                            this.activeImage = (this.activeImage - 1 + this.images.length) % this.images.length;
                        },
                        nextImage() {
                            this.activeImage = (this.activeImage + 1) % this.images.length;
                        },
                    }">
                    <div class="theme-image-wrap relative overflow-hidden rounded-xl">
                        <div class="aspect-[16/10]">
                            <template x-if="hasImage(activeImage)">
                                <img :src="images[activeImage]"
                                    alt="{{ $build['title'] !== '' ? $build['title'] : 'Featured build image' }}"
                                    referrerpolicy="no-referrer" loading="lazy"
                                    class="h-full w-full object-cover">
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
                </article>
            @endforeach
        </div>
    </section>
@endsection
