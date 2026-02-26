<x-guest-layout>
    <div class="mb-4 flex justify-end">
        <button type="button"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
            onclick="window.location.href='{{ route('home') }}';">
            &lt; Back
        </button>
    </div>

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-600">Create Account</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">Register</h1>
        <p class="mt-2 text-sm text-gray-600">Fill in your details to create your account.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" class="text-sm font-semibold text-gray-800" />
                <x-text-input id="first_name"
                    class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                    type="text"
                    name="first_name"
                    :value="old('first_name')"
                    required
                    autofocus
                    autocomplete="given-name"
                    oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Last Name')" class="text-sm font-semibold text-gray-800" />
                <x-text-input id="last_name"
                    class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                    type="text"
                    name="last_name"
                    :value="old('last_name')"
                    required
                    autocomplete="family-name"
                    oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm font-semibold text-gray-800" />
            <x-text-input id="email"
                class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                type="email"
                name="email"
                :value="old('email')"
                required
                autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone -->
        <div>
            <x-input-label for="phone" :value="__('Phone')" class="text-sm font-semibold text-gray-800" />
            <x-text-input id="phone"
                class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                type="tel"
                name="phone"
                :value="old('phone')"
                required
                inputmode="numeric"
                maxlength="20"
                autocomplete="tel"
                oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-semibold text-gray-800" />

            <x-text-input id="password"
                class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                type="password"
                name="password"
                required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-sm font-semibold text-gray-800" />

            <x-text-input id="password_confirmation"
                class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <a class="text-sm font-medium text-gray-700 underline underline-offset-4 hover:text-gray-900 hover:no-underline rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="w-full justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm tracking-wide hover:bg-gray-800 focus:ring-orange-500 sm:w-auto">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
