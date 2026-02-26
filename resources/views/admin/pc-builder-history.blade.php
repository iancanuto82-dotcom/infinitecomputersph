<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Quotation History</h2>
                <p class="mt-1 text-sm text-gray-600">Saved quotations with date indicators.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.pc-builder') }}"
                    class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Back to PC Builder
                </a>
                <a href="{{ route(\App\Support\AdminAccess::preferredAdminRouteName(auth()->user()) ?? 'home') }}"
                    class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Back to dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10">
                <div class="space-y-3">
                    @forelse ($quotationHistory as $quotation)
                        <div class="rounded-lg bg-gray-50/80 px-4 py-3 ring-1 ring-inset ring-black/10">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gray-900">
                                        {{ $quotation->quotation_name ?: ($quotation->customer_name ?: 'Walk-in Customer') }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600">
                                        Customer: {{ $quotation->customer_name ?: 'Walk-in Customer' }}
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-2 py-0.5 ring-1 ring-black/10">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            {{ $quotation->created_at?->format('M d, Y h:i A') ?? '-' }}
                                        </span>
                                        <span>#{{ $quotation->id }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="tabular-nums text-sm font-semibold text-gray-900">
                                        &#8369;{{ number_format((float) $quotation->grand_total, 2) }}
                                    </div>
                                    <div class="text-[11px] text-gray-900/50">
                                        {{ $quotation->created_at?->diffForHumans() ?? '-' }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-600">
                                Items: {{ count($quotation->items ?? []) }}
                                @if ($quotation->customer_contact)
                                    &middot; Contact: {{ $quotation->customer_contact }}
                                @endif
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.pc-builder.quotations.preview', ['quotation' => $quotation]) }}"
                                    class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                    Preview
                                </a>
                                <a href="{{ route('admin.pc-builder', ['copy' => $quotation->id]) }}"
                                    class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                    Copy quotation
                                </a>

                                @if ($quotation->sale_id)
                                    <span class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm">
                                        Added to Sales
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('admin.pc-builder.quotations.add-to-sales', $quotation) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="deduct_stock" value="1">
                                        <button type="submit"
                                            class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-emerald-600/90 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                                            onclick="return confirm('Add this quotation to Sales and deduct stock?')">
                                            Add to Sales
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg bg-gray-50/80 px-4 py-3 text-sm text-gray-600 ring-1 ring-inset ring-black/10">
                            No saved quotations yet.
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $quotationHistory->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
