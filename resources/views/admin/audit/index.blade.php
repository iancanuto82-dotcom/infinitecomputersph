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
                x-data="{
                    timer: null,
                    queueSubmit() {
                        window.clearTimeout(this.timer);
                        this.timer = window.setTimeout(() => this.$el.requestSubmit(), 350);
                    }
                }"
                class="rounded-2xl bg-white p-4 sm:p-5 shadow-sm ring-1 ring-black/10">
                <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="min-w-0 lg:col-span-2">
                        <label for="search" class="sr-only">Search</label>
                        <input id="search" type="text" name="search" value="{{ $search }}"
                            placeholder="Search actor, target, or details..."
                            @input="queueSubmit()"
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label for="date" class="sr-only">Date</label>
                        <input id="date" type="date" name="date" value="{{ $selectedDate ?? '' }}"
                            @change="$el.form.requestSubmit()"
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label for="action" class="sr-only">Action</label>
                        <select id="action" name="action"
                            @change="$el.form.requestSubmit()"
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
                            @change="$el.form.requestSubmit()"
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
                            @change="$el.form.requestSubmit()"
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

                <div class="mt-3 flex items-center justify-between gap-2">
                    <p class="text-xs text-gray-600">Filters auto-apply while you type/select.</p>
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
                            <th class="px-6 py-3">Revert</th>
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
                                    @if (!empty($log->change_lines))
                                        <div class="mt-2 rounded-md bg-gray-50 px-3 py-2 ring-1 ring-black/10">
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">What changed</div>
                                            <ul class="mt-1 space-y-1 text-xs text-gray-700">
                                                @foreach ($log->change_lines as $line)
                                                    <li>{{ $line }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 align-top whitespace-nowrap">
                                    @if ($log->has_revert_action)
                                        @if ($log->can_revert)
                                            <form method="POST" action="{{ route('admin.audit.revert', $log) }}"
                                                onsubmit="return confirm('Revert this history action?');">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex h-[34px] items-center justify-center rounded-md bg-white px-3 text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-black/15 hover:bg-orange-50">
                                                    Revert
                                                </button>
                                            </form>
                                        @elseif ($log->is_reverted)
                                            <span class="inline-flex h-[34px] items-center rounded-md bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                                Reverted
                                            </span>
                                        @else
                                            <div class="max-w-[12rem] text-xs text-gray-500">
                                                {{ $log->revert_reason ?: 'Not available.' }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-700">
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
