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
    @php
        $user = auth()->user();
        $isAdminArea = request()->routeIs('admin.*') || request()->is('admin*');
        $isAdminUser = \App\Support\AdminAccess::isAdmin($user);
        $useSidebarNav = $isAdminUser && ($isAdminArea || request()->routeIs('dashboard'));
        $isDarkThemeEnabled = $isAdminUser && (string) ($user?->theme_preference ?? 'light') === 'dark';
        $containerMaxWidth = $useSidebarNav ? 'max-w-[92rem]' : 'max-w-7xl';
        $adminToastType = null;
        $adminToastMessage = null;

        if ($useSidebarNav) {
            if (session()->has('error')) {
                $adminToastType = 'error';
                $adminToastMessage = (string) session('error');
            } elseif (session()->has('warning')) {
                $adminToastType = 'warning';
                $adminToastMessage = (string) session('warning');
            } elseif (session()->has('status')) {
                $adminToastType = 'success';
                $adminToastMessage = (string) session('status');
            } elseif (session()->has('success')) {
                $adminToastType = 'success';
                $adminToastMessage = (string) session('success');
            }
        }
    @endphp
    @php
        $isDarkThemeEnabled = (bool) ($isDarkThemeEnabled ?? false);
        $useSidebarNav = (bool) ($useSidebarNav ?? false);
        $containerMaxWidth = (string) ($containerMaxWidth ?? 'max-w-7xl');
        $adminToastType = $adminToastType ?? null;
        $adminToastMessage = (string) ($adminToastMessage ?? '');
    @endphp

    <body class="font-sans antialiased{{ $isDarkThemeEnabled ? ' admin-theme-dark' : '' }}">
        <div class="{{ $useSidebarNav ? ($isDarkThemeEnabled ? 'min-h-screen bg-slate-950 text-slate-100 flex' : 'min-h-screen bg-gray-50 text-gray-900 flex') : 'min-h-screen bg-gray-100' }}">
            @include('layouts.navigation')

            <div class="{{ $useSidebarNav ? 'flex-1 min-w-0 overflow-x-hidden' : '' }}">
                <!-- Page Heading -->
                @isset($header)
                    <header class="{{ $isDarkThemeEnabled ? 'bg-slate-900 shadow shadow-black/30' : 'bg-white shadow' }}">
                        <div class="{{ $containerMaxWidth }} mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @if ($adminToastType && $adminToastMessage !== '')
            <div id="admin-toast"
                class="admin-toast admin-toast--{{ $adminToastType }}"
                role="status"
                aria-live="polite">
                <div class="admin-toast__content">
                    <span class="admin-toast__badge">{{ strtoupper($adminToastType) }}</span>
                    <p class="admin-toast__message">{{ $adminToastMessage }}</p>
                </div>
                <button type="button" class="admin-toast__close" data-admin-toast-close aria-label="Dismiss notification">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div id="app-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl ring-1 ring-black/10">
                <h3 id="app-confirm-title" class="text-base font-semibold text-gray-900">Confirm action</h3>
                <p id="app-confirm-message" class="mt-2 text-sm text-gray-600">Are you sure you want to continue?</p>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button id="app-confirm-cancel" type="button"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button id="app-confirm-accept" type="button"
                        class="inline-flex items-center rounded-md bg-rose-700 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <script>
            (() => {
                const toast = document.getElementById('admin-toast');
                const toastClose = toast ? toast.querySelector('[data-admin-toast-close]') : null;
                let toastTimer = null;

                if (toast) {
                    const dismissToast = () => {
                        toast.classList.add('admin-toast--closing');
                        window.setTimeout(() => {
                            toast.remove();
                        }, 220);
                    };

                    const startToastTimer = () => {
                        toastTimer = window.setTimeout(dismissToast, 4800);
                    };

                    const stopToastTimer = () => {
                        if (toastTimer) {
                            window.clearTimeout(toastTimer);
                            toastTimer = null;
                        }
                    };

                    toastClose?.addEventListener('click', () => {
                        stopToastTimer();
                        dismissToast();
                    });
                    toast.addEventListener('mouseenter', stopToastTimer);
                    toast.addEventListener('mouseleave', startToastTimer);

                    startToastTimer();
                }
            })();

            (() => {
                const modal = document.getElementById('app-confirm-modal');
                const titleEl = document.getElementById('app-confirm-title');
                const messageEl = document.getElementById('app-confirm-message');
                const cancelBtn = document.getElementById('app-confirm-cancel');
                const acceptBtn = document.getElementById('app-confirm-accept');

                if (!modal || !titleEl || !messageEl || !cancelBtn || !acceptBtn) return;

                let pendingForm = null;
                const defaultAcceptLabel = (acceptBtn.textContent || '').trim() || 'Confirm';

                const closeModal = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    pendingForm = null;
                };

                const openModal = (form) => {
                    pendingForm = form;
                    titleEl.textContent = form.dataset.confirmTitle || 'Confirm action';
                    messageEl.textContent = form.dataset.confirmMessage || 'Are you sure you want to continue?';
                    acceptBtn.textContent = form.dataset.confirmAccept || defaultAcceptLabel;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    acceptBtn.focus();
                };

                document.addEventListener('submit', (event) => {
                    const form = event.target;
                    if (!(form instanceof HTMLFormElement)) return;
                    if (form.dataset.confirmModal !== 'true') return;

                    if (form.dataset.confirmAccepted === 'true') {
                        form.dataset.confirmAccepted = 'false';
                        return;
                    }

                    event.preventDefault();
                    openModal(form);
                });

                cancelBtn.addEventListener('click', closeModal);

                acceptBtn.addEventListener('click', () => {
                    if (!pendingForm) {
                        closeModal();
                        return;
                    }

                    pendingForm.dataset.confirmAccepted = 'true';
                    pendingForm.requestSubmit();
                    closeModal();
                });

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });
            })();
        </script>
    </body>
</html>
