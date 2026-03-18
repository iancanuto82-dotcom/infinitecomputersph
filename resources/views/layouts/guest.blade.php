<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $appLogo = (string) config('app.logo_url', 'https://i.imgur.com/MCfboy4.png');
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Infinite Computers') }}</title>
        <link rel="icon" type="image/png" href="{{ $appLogo }}">
        <link rel="apple-touch-icon" href="{{ $appLogo }}">

        <link rel="dns-prefetch" href="//i.imgur.com">
        <link rel="preconnect" href="https://i.imgur.com" crossorigin>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#2b2b2b] px-4 py-10">
            <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-orange-500/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-orange-300/20 blur-3xl"></div>

            <div class="relative w-full max-w-md">
                <div x-data="{ logoFailed: false }" class="mb-5 text-center">
                    <a href="/" class="inline-flex flex-col items-center gap-2">
                        <img src="{{ $appLogo }}" alt="{{ config('app.name') }} logo"
                            class="app-logo-bordered h-24 w-24 object-contain sm:h-28 sm:w-28"
                            loading="lazy"
                            referrerpolicy="no-referrer"
                            x-show="!logoFailed"
                            x-on:error="logoFailed = true">
                        <span x-cloak x-show="logoFailed"
                            class="inline-flex h-24 w-24 items-center justify-center rounded-xl bg-gray-200 text-2xl font-semibold text-gray-700 sm:h-28 sm:w-28">
                            IC
                        </span>
                    </a>
                </div>

                <div class="overflow-hidden rounded-2xl border border-white/20 bg-white/95 p-6 shadow-2xl backdrop-blur sm:p-7">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
