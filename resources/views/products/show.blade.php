@extends('layouts.public')

@section('title', $product->name)
@php($seoDescription = \Illuminate\Support\Str::limit(trim((string) ($product->description ?: $product->name.' at Infinite Computers.')), 155))
@php($inquiryMessage = rawurlencode('Hi! I am interested in '.$product->name.'. Is this available?'))
@section('meta_description', $seoDescription)
@section('og_type', 'product')
@section('og_title', $product->name)
@section('og_description', $seoDescription)
@section('og_image', $product->image_src ?: 'https://i.imgur.com/x0GIl1C.png')

@section('content')
    <section class="theme-dark-section relative overflow-hidden rounded-3xl p-6 shadow-xl sm:p-8">
        <div class="pointer-events-none absolute -top-16 right-0 h-56 w-56 rounded-full bg-orange-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-12 h-56 w-56 rounded-full bg-orange-300/20 blur-3xl"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('pricelist', $backQuery) }}"
                    class="inline-flex items-center text-sm font-medium text-white/85 hover:text-white">
                    &lt; Back to Pricelist
                </a>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ $product->name }}</h1>
                <p class="mt-2 text-sm text-white/85">
                    {{ $product->category?->parent ? $product->category->parent->name.' / '.$product->category->name : ($product->category->name ?? 'Uncategorized') }}
                </p>
            </div>

            <div class="theme-panel inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-gray-900">
                {{ $product->stock > 0 ? 'In stock' : 'Out of stock' }}
            </div>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
        <article class="theme-card overflow-hidden rounded-3xl shadow-sm ring-1 ring-black/10">
            @if ($product->image_src)
                <div class="theme-image-wrap bg-gray-50/70">
                    <img src="{{ $product->image_src }}" alt="{{ $product->name }}" class="h-72 w-full object-cover sm:h-[420px]">
                </div>
            @else
                <div class="theme-image-placeholder flex h-72 w-full items-center justify-center text-sm font-medium sm:h-[420px]">
                    No product image
                </div>
            @endif
        </article>

        <aside class="space-y-4">
            <div class="theme-card rounded-3xl p-5 shadow-sm ring-1 ring-black/10 sm:p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Product Snapshot</h3>

                <div class="mt-4 border-b border-black/10 pb-4">
                    <div class="text-3xl font-semibold tabular-nums text-gray-900">
                        &#8369;{{ number_format((float) $product->price, 2) }}
                    </div>
                    <p class="theme-muted mt-1 text-xs">
                        Final pricing may vary by promos and availability.
                    </p>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="theme-muted">Availability</dt>
                        <dd class="font-semibold text-gray-900">{{ $product->stock > 0 ? 'In stock' : 'Out of stock' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="theme-muted">Category</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $product->category?->parent ? $product->category->parent->name.' / '.$product->category->name : ($product->category->name ?? 'Uncategorized') }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="theme-muted">SKU</dt>
                        <dd class="font-medium text-gray-900">#{{ (int) $product->id }}</dd>
                    </div>
                </dl>

                <div class="mt-5 space-y-2">
                    <a href="tel:+639993590894"
                        class="theme-cta inline-flex w-full items-center justify-center rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2">
                        Call Store
                    </a>
                    <a href="sms:+639993590894?body={{ $inquiryMessage }}"
                        class="inline-flex w-full items-center justify-center rounded-md border border-black/15 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2">
                        Text Inquiry
                    </a>
                </div>

                <a href="{{ route('pricelist', $backQuery) }}"
                    class="theme-cta mt-6 inline-flex w-full items-center justify-center rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2">
                    Back to All Products
                </a>
            </div>
        </aside>
    </section>

    <section class="theme-card mt-6 overflow-hidden rounded-3xl shadow-sm ring-1 ring-black/10">
        <div class="border-b border-black/10 px-3 py-2 sm:px-4 sm:py-3">
            <div class="inline-flex items-center rounded-lg bg-gray-100/80 p-1">
                <span class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm">
                    Description
                </span>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <h2 class="text-base font-semibold text-gray-900 sm:text-lg">Full Description</h2>
            <p class="theme-muted mt-3 whitespace-pre-line text-sm leading-7 sm:text-base">
                {{ $product->description ?: 'No description available for this product yet.' }}
            </p>
        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-xl font-semibold tracking-tight text-gray-900">Related Products</h2>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($relatedProducts as $related)
                    <a href="{{ route('pricelist.show', array_merge(['product' => $related], $backQuery)) }}"
                        class="theme-card theme-card-hover group block rounded-2xl p-4 shadow-sm">
                        @if ($related->image_src)
                            <div class="theme-image-wrap mb-3 overflow-hidden rounded-xl bg-gray-50/70">
                                <img src="{{ $related->image_src }}" alt="{{ $related->name }}" class="h-36 w-full object-cover">
                            </div>
                        @endif

                        <div class="text-sm font-semibold text-gray-900">{{ $related->name }}</div>
                        <div class="theme-muted mt-1 text-xs">
                            {{ $related->category?->parent ? $related->category->parent->name.' / '.$related->category->name : ($related->category->name ?? 'Uncategorized') }}
                        </div>
                        <div class="mt-3 text-base font-semibold tabular-nums text-gray-900">&#8369;{{ number_format((float) $related->price, 2) }}</div>
                        <div class="theme-muted mt-2 text-xs group-hover:text-gray-800">View product -></div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
