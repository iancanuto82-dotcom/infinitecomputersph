<x-app-layout>
    @php($canViewHistory = \App\Support\AdminAccess::hasPermission(auth()->user(), 'audit.view'))
    @php($canEditProducts = \App\Support\AdminAccess::hasPermission(auth()->user(), 'products.edit'))
    @php($canViewProductCostStock = \App\Support\AdminAccess::hasPermission(auth()->user(), 'products.cost_stock.view'))

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Admin Dashboard</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ number_format($activeProducts) }} active products. Low stock threshold: &le; {{ (int) $lowStockThreshold }}.
                </p>
            </div>

        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('admin.products.index') }}"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <div class="text-sm font-medium text-gray-600">Products</div>
                    <div class="mt-3 text-3xl font-semibold text-gray-900 tabular-nums">{{ number_format($totalProducts) }}</div>
                    <div class="mt-1 text-xs text-gray-600">{{ number_format($activeProducts) }} active</div>
                </a>

                <a href="{{ route('admin.categories.index') }}"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <div class="text-sm font-medium text-gray-600">Categories</div>
                    <div class="mt-3 text-3xl font-semibold text-gray-900 tabular-nums">{{ number_format($categoriesCount) }}</div>
                    <div class="mt-1 text-xs text-gray-600">Manage grouping</div>
                </a>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10">
                    <div class="text-sm font-medium text-gray-600">Stock units</div>
                    <div class="mt-3 text-3xl font-semibold text-gray-900 tabular-nums">{{ number_format($totalStockUnits) }}</div>
                    <div class="mt-1 text-xs text-gray-600">Across all products</div>
                </div>

                <a href="{{ route('admin.products.index') }}"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <div class="text-sm font-medium text-gray-600">Low stock</div>
                    <div class="mt-3 text-3xl font-semibold text-gray-900 tabular-nums">{{ number_format($lowStockProducts) }}</div>
                    <div class="mt-1 text-xs text-gray-600">&le; {{ (int) $lowStockThreshold }} units</div>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 rounded-xl bg-white shadow-sm ring-1 ring-black/10">
                    <div class="flex items-center justify-between px-6 py-5 border-b border-black/10">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Recent updates</h3>
                            <p class="mt-1 text-sm text-gray-600">Latest products you edited.</p>
                        </div>
                        <a href="{{ route('admin.products.index') }}"
                            class="text-sm font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                            View all
                        </a>
                    </div>

                    @if ($inventoryProducts->isEmpty())
                        <div class="px-6 py-10 text-center">
                            <div class="text-sm text-gray-700">No products yet.</div>
                            @if ($canEditProducts)
                                <div class="mt-3">
                                    <a href="{{ route('admin.products.create') }}"
                                        class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                        Add your first product
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Product</th>
                                        <th class="px-6 py-3">Category</th>
                                        <th class="px-6 py-3 text-right">Price</th>
                                        @if ($canViewProductCostStock)
                                            <th class="px-6 py-3 text-right">Cost</th>
                                        @endif
                                        <th class="px-6 py-3 text-right">Stock</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-black/10">
                                    @foreach ($inventoryProducts as $product)
                                        <tr class="hover:bg-gray-50/90">
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                                <div class="mt-0.5 text-xs text-gray-600">
                                                    Updated {{ optional($product->updated_at)->diffForHumans() }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-gray-900/80">
                                                {{ $product->category->name ?? 'Uncategorized' }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-gray-900 tabular-nums">
                                                &#8369;{{ number_format((float) $product->price, 2) }}
                                            </td>
                                            @if ($canViewProductCostStock)
                                                <td class="px-6 py-4 text-right text-gray-900 tabular-nums">
                                                    @if ($product->cost_price !== null)
                                                        &#8369;{{ number_format((float) $product->cost_price, 2) }}
                                                    @else
                                                        <span class="text-gray-900/50">&mdash;</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="px-6 py-4 text-right tabular-nums {{ $product->stock <= $lowStockThreshold ? 'text-gray-900 font-semibold' : 'text-gray-900' }}">
                                                {{ number_format((int) $product->stock) }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-black/15">
                                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                @if ($canEditProducts)
                                                    <a href="{{ route('admin.products.edit', $product) }}"
                                                        class="text-sm font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                                        Edit
                                                    </a>
                                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline"
                                                        data-confirm-modal="true"
                                                        data-confirm-title="Delete product"
                                                        data-confirm-message="Delete this product? This action cannot be undone.">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="ms-3 text-sm font-medium text-rose-700 hover:text-rose-600">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-sm font-medium text-gray-400 cursor-not-allowed" aria-disabled="true">
                                                        Edit
                                                    </span>
                                                    <span class="ms-3 text-sm font-medium text-rose-300 cursor-not-allowed" aria-disabled="true">
                                                        Delete
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Low stock alerts</h3>
                        <span class="text-sm font-medium text-gray-600">{{ number_format($lowStockProducts) }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">Products at &le; {{ (int) $lowStockThreshold }} units.</p>

                    <div class="mt-4 space-y-3">
                        @forelse ($lowStockItems as $product)
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-black/10">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                    <div class="mt-0.5 text-xs text-gray-600">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format((int) $product->stock) }}</div>
                                    @if ($canEditProducts)
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                            class="text-sm font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                            Edit
                                        </a>
                                    @else
                                        <span class="text-sm font-medium text-gray-400 cursor-not-allowed" aria-disabled="true">
                                            Edit
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700 ring-1 ring-inset ring-black/10">
                                No low stock alerts right now.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('admin.products.index') }}"
                            class="inline-flex items-center rounded-md bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900 ring-1 ring-inset ring-black/10 hover:bg-orange-100/40">
                            Manage products
                        </a>
                        <a href="{{ route('admin.categories.index') }}"
                            class="inline-flex items-center rounded-md bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900 ring-1 ring-inset ring-black/10 hover:bg-orange-100/40">
                            Manage categories
                        </a>
                        @if ($canViewHistory)
                            <a href="{{ route('admin.audit.index') }}"
                                class="inline-flex items-center rounded-md bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900 ring-1 ring-inset ring-black/10 hover:bg-orange-100/40">
                                View history
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
