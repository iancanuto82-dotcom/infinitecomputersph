<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Staff Accounts</h2>
                <p class="mt-1 text-sm text-gray-600">Create and manage staff access for admin modules.</p>
            </div>

            <a href="{{ route('admin.staff.create') }}"
                class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                Add staff
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @php($permissionLabelMap = collect(\App\Support\AdminAccess::staffPermissionGroups())->pluck('permissions')->collapse()->all())

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Access</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10">
                        @forelse ($staffUsers as $staffUser)
                            @php($permissions = \App\Support\AdminAccess::normalizeStaffPermissions((array) ($staffUser->admin_permissions ?? [])))
                            @php($permissionLabels = collect($permissions)->map(fn ($permission) => $permissionLabelMap[$permission] ?? $permission)->all())
                            <tr class="hover:bg-gray-50/90">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $staffUser->name }}</td>
                                <td class="px-6 py-4 text-gray-900/80">{{ $staffUser->email }}</td>
                                <td class="px-6 py-4 text-gray-900/80">
                                    <div>{{ count($permissions) }} permission{{ count($permissions) === 1 ? '' : 's' }}</div>
                                    @if (count($permissions) > 0)
                                        <div class="mt-1 text-xs text-gray-600">{{ implode(', ', $permissionLabels) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.staff.edit', $staffUser) }}"
                                        class="text-sm font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                        Edit access
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-700">
                                    No staff accounts yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $staffUsers->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
