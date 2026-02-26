<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Edit staff account</h2>
                <p class="mt-1 text-sm text-gray-600">Update login details and module access.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                <form action="{{ route('admin.staff.update', $staffUser) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-900">Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $staffUser->name) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-900">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $staffUser->email) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-900">New password (optional)</label>
                            <input id="password" type="password" name="password"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-900">Confirm new password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-black/10">
                        <h3 class="text-sm font-semibold text-gray-900">Module access</h3>
                        <p class="mt-1 text-xs text-gray-600">Select what this staff member can view and edit.</p>

                        @php($checkedPermissions = old('permissions', $selectedPermissions))
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($permissionGroups as $group)
                                <div class="rounded-md bg-white px-3 py-3 ring-1 ring-inset ring-black/10">
                                    <div class="text-sm font-semibold text-gray-900">{{ $group['label'] }}</div>
                                    <div class="mt-2 space-y-2">
                                        @foreach ($group['permissions'] as $permissionKey => $permissionLabel)
                                            <label class="flex items-start gap-2 text-sm text-gray-900">
                                                <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}"
                                                    {{ in_array($permissionKey, (array) $checkedPermissions, true) ? 'checked' : '' }}
                                                    class="mt-0.5 rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                                <span>{{ $permissionLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <x-input-error class="mt-3" :messages="$errors->get('permissions')" />
                        <x-input-error class="mt-1" :messages="$errors->get('permissions.*')" />
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Update staff
                        </button>
                        <a href="{{ route('admin.staff.index') }}"
                            class="text-sm font-medium text-gray-700 hover:text-gray-900 underline underline-offset-4 hover:no-underline">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
