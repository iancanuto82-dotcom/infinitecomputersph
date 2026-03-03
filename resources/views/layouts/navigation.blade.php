@php
    $user = auth()->user();
    $appLogo = (string) config('app.logo_url', 'https://i.imgur.com/MCfboy4.png');
    $isAdminUser = \App\Support\AdminAccess::isAdmin($user);
    $isAdminArea = request()->routeIs('admin.*') || request()->is('admin*');
    $shouldUseSideNav = $isAdminUser && ($isAdminArea || request()->routeIs('dashboard'));
    $containerMaxWidth = $shouldUseSideNav ? 'max-w-[92rem]' : 'max-w-7xl';

    $adminLinks = [];

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'dashboard.view')) {
        $adminLinks[] = ['href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'label' => __('Dashboard')];
    }

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'sales.view')) {
        $adminLinks[] = ['href' => route('admin.sales'), 'active' => request()->routeIs('admin.sales*'), 'label' => __('Sales')];
        $adminLinks[] = ['href' => route('admin.expenses'), 'active' => request()->routeIs('admin.expenses*'), 'label' => __('Expenses')];
        $adminLinks[] = ['href' => route('admin.replacements.index'), 'active' => request()->routeIs('admin.replacements.index'), 'label' => __('Replacements')];
        $adminLinks[] = ['href' => route('admin.replacements.inventory'), 'active' => request()->routeIs('admin.replacements.inventory'), 'label' => __('RMA Inventory')];
    }

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'sales.edit')) {
        $adminLinks[] = ['href' => route('admin.sales.create'), 'active' => request()->routeIs('admin.sales.create'), 'label' => __('Create Sale')];
    }

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'pc_builder.view')) {
        $adminLinks[] = ['href' => route('admin.pc-builder'), 'active' => request()->routeIs('admin.pc-builder*'), 'label' => __('PC Builder')];
    }

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'products.view')) {
        $adminLinks[] = ['href' => route('admin.products.index'), 'active' => request()->routeIs('admin.products.*'), 'label' => __('Products')];
    }

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'categories.view')) {
        $adminLinks[] = ['href' => route('admin.categories.index'), 'active' => request()->routeIs('admin.categories.*'), 'label' => __('Categories')];
    }

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'content.view')) {
        $adminLinks[] = ['href' => route('admin.content.edit'), 'active' => request()->routeIs('admin.content.*'), 'label' => __('Content')];
    }

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'audit.view')) {
        $adminLinks[] = ['href' => route('admin.audit.index'), 'active' => request()->routeIs('admin.audit.*'), 'label' => __('History')];
    }

    if ($isAdminUser && \App\Support\AdminAccess::hasPermission($user, 'users.manage')) {
        $adminLinks[] = ['href' => route('admin.staff.index'), 'active' => request()->routeIs('admin.staff.*'), 'label' => __('Staff')];
    }

    $dashboardHref = $isAdminUser && count($adminLinks) > 0 ? $adminLinks[0]['href'] : route('pricelist');
    $dashboardActive = $isAdminUser && count($adminLinks) > 0 ? $adminLinks[0]['active'] : request()->routeIs('pricelist');
    $dashboardLabel = $isAdminUser && count($adminLinks) > 0 ? $adminLinks[0]['label'] : __('Price List');
    $secondaryAdminLinks = $isAdminUser ? array_slice($adminLinks, 1) : [];
@endphp

@if ($shouldUseSideNav)
    @php
        $allAdminLinks = array_merge([['href' => $dashboardHref, 'active' => $dashboardActive, 'label' => $dashboardLabel]], $secondaryAdminLinks);
        $iconMap = [
            'Dashboard' => 'dashboard',
            'Sales' => 'sales',
            'Expenses' => 'expenses',
            'Replacements' => 'replacements',
            'RMA Inventory' => 'rma_inventory',
            'Create Sale' => 'create_sale',
            'PC Builder' => 'pc_builder',
            'Products' => 'products',
            'Categories' => 'categories',
            'Content' => 'content',
            'History' => 'history',
            'Staff' => 'staff',
        ];
    @endphp
    <nav x-data="{
            logoFailed: false,
            isTouch: window.matchMedia('(hover: none), (pointer: coarse)').matches,
            collapsed: true,
            expandSidebar() {
                if (!this.isTouch) this.collapsed = false;
            },
            collapseSidebar() {
                if (!this.isTouch) this.collapsed = true;
            }
        }"
        x-init="collapsed = isTouch ? false : true"
        @mouseenter="expandSidebar()"
        @mouseleave="collapseSidebar()"
        class="sticky top-0 self-start relative h-screen shrink-0 overflow-hidden border-r border-slate-200 bg-white transition-[width] duration-300 ease-out"
        :class="collapsed ? 'w-[4.75rem]' : 'w-[16.5rem]'">
        <aside class="flex h-full flex-col bg-white text-slate-700 transition-all duration-200">
            <div class="border-b border-slate-200 py-4" :class="collapsed ? 'px-2' : 'px-3'">
                <a href="{{ $dashboardHref }}" class="flex items-center gap-3 rounded-xl bg-slate-50 ring-1 ring-slate-200" :class="collapsed ? 'w-full justify-center px-2 py-2' : 'px-2.5 py-2.5'">
                    <img src="{{ $appLogo }}" alt="{{ config('app.name') }} logo"
                        class="h-9 w-9 shrink-0 object-contain"
                        loading="lazy"
                        referrerpolicy="no-referrer"
                        x-show="!logoFailed"
                        x-on:error="logoFailed = true">
                    <span x-cloak x-show="logoFailed"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-200 text-sm font-semibold text-slate-700">
                        IC
                    </span>
                    <div x-show="!collapsed" x-cloak
                        x-transition:enter="transition ease-out duration-200 delay-75"
                        x-transition:enter-start="opacity-0 -translate-x-2"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-2"
                        class="min-w-0">
                        <div class="truncate text-[0.68rem] uppercase tracking-[0.14em] text-slate-500">Admin Panel</div>
                        <div class="truncate text-sm font-semibold text-slate-900">{{ config('app.name') }}</div>
                    </div>
                </a>
            </div>

            <div class="flex-1 overflow-hidden px-3 py-4">
                <p x-show="!collapsed" x-cloak
                    x-transition:enter="transition ease-out duration-200 delay-75"
                    x-transition:enter-start="opacity-0 -translate-x-2"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-2"
                    class="mb-2 px-2 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-400">Main</p>

                <div class="space-y-1.5">
                    @foreach ($allAdminLinks as $link)
                        @php
                            $icon = $iconMap[$link['label']] ?? 'default';
                        @endphp
                        <a href="{{ $link['href'] }}"
                            class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 {{ $link['active'] ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}"
                            :class="collapsed ? 'justify-center px-2' : ''">
                            <span class="inline-flex h-5 w-5 items-center justify-center">
                                @switch($icon)
                                    @case('dashboard')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 13h6v7H4zM14 4h6v16h-6zM4 4h6v5H4zM14 13h6v7h-6z" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('sales')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 1v22M17 5.5a4.5 4.5 0 00-9 0c0 5 9 2.5 9 8a4.5 4.5 0 01-9 0" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('expenses')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 7H4m16 0l-1.2 12a2 2 0 01-2 1.8H7.2a2 2 0 01-2-1.8L4 7m4-3h8a2 2 0 012 2v1H6V6a2 2 0 012-2z" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('replacements')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 7V3m8 4V3M4 11h16M5 6h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zm3 7l2 2 4-4" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('rma_inventory')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h16M4 12h16M4 17h16M9 4v16m6-16v16" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('create_sale')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('pc_builder')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h16v9H4zM8 19h8M9 15v4M15 15v4" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('products')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('categories')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('content')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 4h12l2 2v14H4V4h2zm2 5h8M8 13h8M8 17h5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('history')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12a9 9 0 109-9 9.2 9.2 0 00-6.36 2.64M3 4v5h5M12 7v6l4 2" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @case('staff')
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @break
                                    @default
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8" stroke-width="1.7"/></svg>
                                @endswitch
                            </span>
                            <span x-show="!collapsed" x-cloak
                                x-transition:enter="transition ease-out duration-200 delay-75"
                                x-transition:enter-start="opacity-0 -translate-x-2"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-x-0"
                                x-transition:leave-end="opacity-0 -translate-x-2"
                                class="truncate">{{ $link['label'] }}</span>
                            <span x-show="collapsed" x-cloak
                                class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 opacity-0 shadow-md transition group-hover:opacity-100">
                                {{ $link['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-slate-200 px-3 py-3">
                @auth
                    <div x-show="!collapsed" x-cloak
                        x-transition:enter="transition ease-out duration-200 delay-75"
                        x-transition:enter-start="opacity-0 -translate-x-2"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-2"
                        class="mb-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">
                        <div class="truncate text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                @endauth

                <a href="{{ route('profile.edit') }}"
                    class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900"
                    :class="collapsed ? 'justify-center px-2' : ''">
                    <span class="inline-flex h-5 w-5 items-center justify-center">
                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zM4 22a8 8 0 0116 0" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span x-show="!collapsed" x-cloak
                        x-transition:enter="transition ease-out duration-200 delay-75"
                        x-transition:enter-start="opacity-0 -translate-x-2"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-2">{{ __('Profile') }}</span>
                    <span x-show="collapsed" x-cloak
                        class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 opacity-0 shadow-md transition group-hover:opacity-100">
                        {{ __('Profile') }}
                    </span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="group relative mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rose-600 transition-all duration-200 hover:bg-rose-50 hover:text-rose-700"
                        :class="collapsed ? 'justify-center px-2' : ''">
                        <span class="inline-flex h-5 w-5 items-center justify-center">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span x-show="!collapsed" x-cloak
                            x-transition:enter="transition ease-out duration-200 delay-75"
                            x-transition:enter-start="opacity-0 -translate-x-2"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-x-0"
                            x-transition:leave-end="opacity-0 -translate-x-2">{{ __('Log Out') }}</span>
                        <span x-show="collapsed" x-cloak
                            class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 opacity-0 shadow-md transition group-hover:opacity-100">
                            {{ __('Log Out') }}
                        </span>
                    </button>
                </form>
            </div>
        </aside>
    </nav>
@else
    <nav x-data="{ open: false, logoFailed: false }" class="bg-white border-b border-gray-100">
        <div class="{{ $containerMaxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="shrink-0 flex items-center">
                        <a href="{{ $dashboardHref }}" class="inline-flex items-center justify-center rounded-md p-1">
                            <img src="{{ $appLogo }}" alt="{{ config('app.name') }} logo"
                                class="h-9 w-9 object-contain"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                                x-show="!logoFailed"
                                x-on:error="logoFailed = true">
                            <span x-cloak x-show="logoFailed"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-gray-100 text-sm font-semibold text-gray-700">
                                IC
                            </span>
                        </a>
                    </div>

                    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                        <x-nav-link :href="$dashboardHref" :active="$dashboardActive">
                            {{ $dashboardLabel }}
                        </x-nav-link>

                        @foreach ($secondaryAdminLinks as $link)
                            <x-nav-link :href="$link['href']" :active="$link['active']">
                                {{ $link['label'] }}
                            </x-nav-link>
                        @endforeach
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                @auth
                                    <div>{{ Auth::user()->name }}</div>
                                @endauth

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <div class="-me-2 flex items-center sm:hidden">
                    <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
            <div class="pt-2 pb-3 space-y-1">
                <x-responsive-nav-link :href="$dashboardHref" :active="$dashboardActive">
                    {{ $dashboardLabel }}
                </x-responsive-nav-link>

                @foreach ($secondaryAdminLinks as $link)
                    <x-responsive-nav-link :href="$link['href']" :active="$link['active']">
                        {{ $link['label'] }}
                    </x-responsive-nav-link>
                @endforeach
            </div>

            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    @auth<div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>@endauth
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        </div>
    </nav>
@endif
