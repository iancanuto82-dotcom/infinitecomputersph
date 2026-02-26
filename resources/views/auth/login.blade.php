<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700" :status="session('status')" />

    <div class="mb-4 flex justify-end">
        <button type="button"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
            onclick="window.location.href='{{ route('home') }}';">
            &lt; Back
        </button>
    </div>

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-600">Welcome</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">Sign in to your account</h1>
        <p class="mt-2 text-sm text-gray-600">Enter your credentials to continue.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ showPassword: false }">
        @csrf

        <!-- Email / Username -->
        <div>
            <x-input-label for="email" :value="__('Email or Username')" class="text-sm font-semibold text-gray-800" />
            <x-text-input id="email"
                class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                type="text"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-semibold text-gray-800" />
            <div class="relative mt-1">
                <x-text-input id="password"
                    class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 pr-20 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password" />
                <button type="button"
                    class="absolute inset-y-0 right-2 my-1 rounded-md px-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                    x-on:click="showPassword = !showPassword"
                    x-text="showPassword ? 'Hide' : 'Show'"
                    aria-label="Toggle password visibility"></button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500"
                    name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-4">
                @if (Route::has('register'))
                    <a class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2" href="{{ route('register') }}">
                        {{ __('Sign up') }}
                    </a>
                @endif

                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-gray-700 underline underline-offset-4 hover:text-gray-900 hover:no-underline focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 rounded-md" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <div class="flex items-center sm:justify-end">
                <x-primary-button class="w-full justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm tracking-wide hover:bg-gray-800 focus:ring-orange-500 sm:w-auto">
                    {{ __('Log in') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
