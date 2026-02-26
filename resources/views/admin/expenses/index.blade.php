<x-app-layout>
    @php($canEditSales = \App\Support\AdminAccess::hasPermission(auth()->user(), 'sales.edit'))

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gray-900 text-white flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4m16 0l-1.2 12a2 2 0 01-2 1.8H7.2a2 2 0 01-2-1.8L4 7m4-3h8a2 2 0 012 2v1H6V6a2 2 0 012-2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">Expenses</h2>
                    <p class="mt-1 text-sm text-gray-600">Record and review business expenses.</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-600">Total Expenses</div>
                        <div class="mt-2 text-2xl sm:text-3xl font-semibold text-rose-700 tabular-nums">&#8369;{{ number_format((float) $totalAmount, 2) }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center ring-1 ring-inset ring-rose-100">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4m16 0l-1.2 12a2 2 0 01-2 1.8H7.2a2 2 0 01-2-1.8L4 7m4-3h8a2 2 0 012 2v1H6V6a2 2 0 012-2z" />
                        </svg>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-600">Current Month</div>
                        <div class="mt-2 text-2xl sm:text-3xl font-semibold text-gray-900 tabular-nums">&#8369;{{ number_format((float) $currentMonthAmount, 2) }}</div>
                        <div class="mt-1 text-xs text-gray-600">{{ now()->format('F Y') }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center ring-1 ring-inset ring-cyan-100">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M6 7h12a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V9a2 2 0 012-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            @if ($canEditSales)
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                    <h3 class="text-base font-semibold text-gray-900">Add Expense</h3>
                    <p class="mt-1 text-sm text-gray-600">Write a new expense record.</p>

                    <form method="POST" action="{{ route('admin.expenses.store') }}" class="mt-4 grid grid-cols-1 lg:grid-cols-6 gap-3">
                        @csrf
                        <div>
                            <label for="spent_at" class="block text-sm font-medium text-gray-900">Date & time</label>
                            <input id="spent_at" name="spent_at" type="datetime-local"
                                value="{{ old('spent_at', now()->format('Y-m-d\\TH:i')) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div class="lg:col-span-2">
                            <label for="title" class="block text-sm font-medium text-gray-900">Title</label>
                            <input id="title" name="title" type="text" required value="{{ old('title') }}"
                                placeholder="Electric bill, delivery fee, rent..."
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-900">Category</label>
                            <input id="category" name="category" type="text" value="{{ old('category') }}"
                                placeholder="Utilities"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-900">Amount</label>
                            <input id="amount" name="amount" type="number" min="0.01" step="0.01" required value="{{ old('amount') }}"
                                placeholder="0.00"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div class="lg:col-span-6">
                            <label for="notes" class="block text-sm font-medium text-gray-900">Notes (optional)</label>
                            <input id="notes" name="notes" type="text" value="{{ old('notes') }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div class="lg:col-span-6">
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                Save Expense
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                <form method="GET" class="expense-filter-form">
                    <div class="expense-filter-field">
                        <label for="month" class="block text-sm font-medium text-gray-900">Month Filter</label>
                        <select id="month" name="month"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="all" {{ $selectedMonthNumber ? '' : 'selected' }}>All Months</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) $selectedMonthNumber === $m ? 'selected' : '' }}>
                                    {{ \Illuminate\Support\Carbon::createFromDate(2020, $m, 1)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="expense-filter-field">
                        <label for="year" class="block text-sm font-medium text-gray-900">Year Filter</label>
                        <select id="year" name="year"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="all" {{ $selectedYear ? '' : 'selected' }}>All Years</option>
                            @foreach ($years as $y)
                                <option value="{{ $y }}" {{ (int) $selectedYear === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="expense-filter-field">
                        <label for="q" class="block text-sm font-medium text-gray-900">Search</label>
                        <div class="expense-search-wrap">
                            <input id="q" name="q" type="text" value="{{ $search }}"
                                placeholder="Title, category, notes"
                                class="block h-10 w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <button type="submit"
                                class="expense-search-btn inline-flex items-center justify-center rounded-md bg-zinc-900 px-4 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                Search
                            </button>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="expense-filter-btn inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-600/90 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                            Apply Filters
                        </button>
                    </div>
                    <div>
                        <a href="{{ route('admin.expenses') }}"
                            class="expense-filter-btn inline-flex w-full items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-5 border-b border-black/10">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Expense Records</h3>
                        <p class="mt-1 text-sm text-gray-600">All recorded expenses.</p>
                    </div>
                </div>

                @if ($expenses->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <div class="text-sm text-gray-700">No expense records found.</div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Title</th>
                                    <th class="px-6 py-3">Category</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3">Notes</th>
                                    <th class="px-6 py-3">Recorded By</th>
                                    @if ($canEditSales)
                                        <th class="px-6 py-3 text-right">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10">
                                @foreach ($expenses as $expense)
                                    <tr class="even:bg-zinc-50 hover:bg-gray-50/90">
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-700">
                                            {{ optional($expense->spent_at)->format('M j, Y h:i A') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-3 font-medium text-gray-900">{{ $expense->title }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ $expense->category ?: '-' }}</td>
                                        <td class="px-6 py-3 text-right whitespace-nowrap font-semibold text-rose-700 tabular-nums">
                                            &#8369;{{ number_format((float) $expense->amount, 2) }}
                                        </td>
                                        <td class="px-6 py-3 text-gray-700">{{ $expense->notes ?: '-' }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ $expense->creator?->name ?: 'System' }}</td>
                                        @if ($canEditSales)
                                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                                <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}"
                                                    onsubmit="return confirm('Delete this expense record?');"
                                                    class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex h-9 items-center justify-center rounded-md bg-white px-3 text-xs font-semibold text-rose-700 shadow-sm ring-1 ring-inset ring-rose-600/30 hover:bg-rose-50">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-black/10">
                        {{ $expenses->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .expense-filter-form {
            display: grid;
            gap: 0.75rem;
            align-items: end;
            grid-template-columns: 1fr;
        }

        .expense-filter-field {
            min-width: 0;
        }

        .expense-filter-btn {
            height: 2.5rem;
        }

        .expense-search-wrap {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .expense-search-btn {
            height: 2.5rem;
            white-space: nowrap;
        }

        @media (min-width: 1024px) {
            .expense-filter-form {
                grid-template-columns: minmax(0, 1.25fr) minmax(0, 0.9fr) minmax(0, 1.2fr) 9.5rem 8.5rem;
            }

            .expense-search-wrap .expense-search-btn {
                min-width: 5.75rem;
            }
        }
    </style>
</x-app-layout>
