<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">History Log</h2>
                <p class="mt-1 text-sm text-gray-600">Track who added, edited, deleted, and processed sales.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('admin.audit.index') }}"
                class="rounded-2xl bg-white p-4 sm:p-5 shadow-sm ring-1 ring-black/10">
                <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="min-w-0 lg:col-span-2">
                        <label for="search" class="sr-only">Search</label>
                        <input id="search" type="text" name="search" value="{{ $search }}"
                            placeholder="Search actor, target, or details..."
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label for="action" class="sr-only">Action</label>
                        <select id="action" name="action"
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">All actions</option>
                            @foreach ($actions as $availableAction)
                                <option value="{{ $availableAction }}" {{ $selectedAction === $availableAction ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', $availableAction) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="target_type" class="sr-only">Type</label>
                        <select id="target_type" name="target_type"
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">All modules</option>
                            @foreach ($targetTypes as $type)
                                <option value="{{ $type }}" {{ $selectedTargetType === $type ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', $type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="actor" class="sr-only">Actor</label>
                        <select id="actor" name="actor"
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">All users</option>
                            @foreach ($actors as $actor)
                                <option value="{{ $actor->id }}" {{ (int) ($selectedActorId ?? 0) === (int) $actor->id ? 'selected' : '' }}>
                                    {{ $actor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <button type="submit"
                        class="inline-flex h-[40px] items-center justify-center rounded-md bg-gray-900 px-4 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                        Apply
                    </button>
                    <a href="{{ route('admin.audit.index') }}"
                        class="inline-flex h-[40px] items-center justify-center rounded-md bg-white px-4 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50">
                        Reset
                    </a>
                </div>
            </form>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">When</th>
                            <th class="px-6 py-3">Who</th>
                            <th class="px-6 py-3">Action</th>
                            <th class="px-6 py-3">Target</th>
                            <th class="px-6 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50/90">
                                <td class="px-6 py-4 text-gray-900/80 whitespace-nowrap">
                                    {{ optional($log->created_at)->format('M d, Y h:i:s A') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $log->user->name ?? 'System' }}</div>
                                    <div class="text-xs text-gray-600">{{ $log->user->email ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-900/80">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </td>
                                <td class="px-6 py-4 text-gray-900/80">
                                    <div class="font-medium text-gray-900">
                                        {{ str_replace('_', ' ', $log->target_type) }}
                                        @if ($log->target_id)
                                            #{{ $log->target_id }}
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-600">{{ $log->target_name ?: '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-900/80">
                                    <div>{{ $log->description ?: '-' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-700">
                                    No history records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
