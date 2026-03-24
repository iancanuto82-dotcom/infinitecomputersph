<x-app-layout>
    @php($canEditProducts = \App\Support\AdminAccess::hasPermission(auth()->user(), 'products.edit'))
    @php($canViewProductCostStock = \App\Support\AdminAccess::hasPermission(auth()->user(), 'products.cost_stock.view'))
    @php($tableColumnCount = ($canEditProducts ? 1 : 0) + 5 + ($canViewProductCostStock ? 2 : 0))
    @php($indexQuery = request()->only(['search', 'category', 'stock', 'page']))
    @php($allCategories = collect($categories ?? []))
    @php($mainCategoryOptions = $allCategories->filter(fn ($category) => ($category->parent_id ?? null) === null)->values())
    @php($subcategoriesByParent = $allCategories->filter(fn ($category) => ($category->parent_id ?? null) !== null)->groupBy(fn ($category) => (int) $category->parent_id))
    @php($mainCategoryIds = $mainCategoryOptions->pluck('id')->map(fn ($id) => (int) $id)->all())
    @php($orphanSubcategoryOptions = $allCategories->filter(fn ($category) => ($category->parent_id ?? null) !== null && ! in_array((int) $category->parent_id, $mainCategoryIds, true))->values())
    @php($stockToneClass = fn ($stock) => (int) $stock <= 0 ? 'text-rose-600 font-semibold' : ((int) $stock <= 3 ? 'text-amber-600 font-semibold' : 'text-emerald-600 font-semibold'))

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Products</h2>
                <p class="mt-1 text-sm text-gray-600">Manage your inventory items.</p>
            </div>

            @if ($canEditProducts)
                <div class="flex flex-col gap-2 sm:items-end">
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <form method="POST" action="{{ route('admin.products.import', $indexQuery) }}" enctype="multipart/form-data"
                            class="flex flex-wrap items-center gap-2 sm:flex-nowrap"
                            data-confirm-modal="true"
                            data-confirm-title="Import products"
                            data-confirm-message="Import this CSV/XLSX file? Matching products will be updated and new products will be created."
                            data-confirm-accept="Import">
                            @csrf
                            <div class="flex items-center gap-2 rounded-md bg-white px-2 py-1 ring-1 ring-inset ring-black/15">
                                <label for="product_import_file"
                                    class="inline-flex cursor-pointer items-center rounded-md bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-orange-100/60">
                                    Choose file
                                </label>
                                <input id="product_import_file" type="file" name="file" accept=".csv,.xlsx" class="sr-only" required>
                                <span id="product_import_file_name" class="max-w-[180px] truncate text-sm text-gray-600 sm:max-w-[220px]">No file selected</span>
                            </div>
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                Import CSV/XLSX
                            </button>
                        </form>

                        @if (!empty($latestImportRevertLog))
                            <form method="POST" action="{{ route('admin.products.import.revert', $indexQuery) }}"
                                data-confirm-modal="true"
                                data-confirm-title="Revert imported products"
                                data-confirm-message="Revert your latest imported product changes? This removes newly created products and restores previous values for updated products."
                                data-confirm-accept="Revert">
                                @csrf
                                <input type="hidden" name="audit_log_id" value="{{ (int) $latestImportRevertLog->id }}">
                                <button type="submit"
                                    class="inline-flex items-center rounded-md bg-rose-700 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                    Undo last import
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.products.create', $indexQuery) }}"
                            class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Add product
                        </a>
                    </div>
                    <p class="text-xs text-gray-600 sm:text-right">
                        Template: CATEGORY, Sub Category, Product Name, Cost per Unit, Price per Unit, Current Qty. in Stock.
                    </p>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8">
            @if ($errors->has('file'))
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-900 ring-1 ring-inset ring-rose-200">
                    {{ $errors->first('file') }}
                </div>
            @endif

            <form id="products-filter-form" method="GET" action="{{ route('admin.products.index') }}"
                class="mb-4 rounded-2xl bg-white p-4 sm:p-5 shadow-sm ring-1 ring-black/10">
                <div class="products-filter-row">
                    <div class="products-filter-search">
                        <label for="search" class="sr-only">Search</label>
                        <input id="search" type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search product or category..."
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label for="category" class="sr-only">Category</label>
                        <select id="category" name="category"
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">All categories</option>
                            @foreach ($mainCategoryOptions as $category)
                                <option value="{{ $category->id }}" {{ (int) ($categoryId ?? 0) === (int) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @foreach ($subcategoriesByParent->get((int) $category->id, collect()) as $subcategory)
                                    <option value="{{ $subcategory->id }}" {{ (int) ($categoryId ?? 0) === (int) $subcategory->id ? 'selected' : '' }}>-- {{ $subcategory->name }}</option>
                                @endforeach
                            @endforeach
                            @foreach ($orphanSubcategoryOptions as $orphanSubcategory)
                                <option value="{{ $orphanSubcategory->id }}" {{ (int) ($categoryId ?? 0) === (int) $orphanSubcategory->id ? 'selected' : '' }}>-- {{ $orphanSubcategory->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="stock" class="sr-only">Stock</label>
                        <select id="stock" name="stock"
                            class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="all" {{ ($stockFilter ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                            <option value="in_stock" {{ ($stockFilter ?? 'all') === 'in_stock' ? 'selected' : '' }}>In stock</option>
                            <option value="out_of_stock" {{ ($stockFilter ?? 'all') === 'out_of_stock' ? 'selected' : '' }}>Out of stock</option>
                        </select>
                    </div>

                </div>

                <div class="mt-3 border-t border-black/10 pt-3 text-xs text-gray-600">
                    Showing {{ number_format($products->total()) }} products
                </div>
            </form>

                <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10">
                @if ($canEditProducts)
                    <div class="border-b border-black/10 px-4 py-3 sm:px-6">
                        <form id="bulk-delete-products-form" action="{{ route('admin.products.bulk-destroy') }}" method="POST"
                            class="flex flex-wrap items-center gap-2"
                            data-confirm-modal="true"
                            data-confirm-title="Delete selected products"
                            data-confirm-message="Delete selected products? This action cannot be undone.">
                            @csrf
                            @method('DELETE')
                            <button id="bulk-delete-products-button" type="submit" disabled
                                class="inline-flex h-[38px] items-center justify-center rounded-md bg-rose-700 px-4 text-sm font-medium text-white shadow-sm hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40">
                                Delete selected
                            </button>
                            <span id="bulk-delete-products-count" class="text-xs text-gray-600">0 selected</span>
                        </form>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="products-table min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                            <tr>
                                @if ($canEditProducts)
                                    <th class="px-4 py-3">
                                        <label for="select_all_products" class="sr-only">Select all products</label>
                                        <input id="select_all_products" type="checkbox"
                                            class="h-4 w-4 rounded border-black/25 text-gray-900 focus:ring-orange-500">
                                    </th>
                                @endif
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3 text-right">Price</th>
                                @if ($canViewProductCostStock)
                                    <th class="px-6 py-3 text-right">Cost</th>
                                    <th class="px-6 py-3 text-right">Initial Stock</th>
                                @endif
                                <th class="px-6 py-3 text-right">Current Stock</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10">
                            @forelse($products as $product)
                                @php($costCalc = $costCalculations[(string) $product->id] ?? null)
                                <tr class="hover:bg-gray-50/90">
                                    @if ($canEditProducts)
                                        <td class="px-4 py-4 align-top">
                                            <label for="bulk_product_{{ $product->id }}" class="sr-only">Select {{ $product->name }}</label>
                                            <input id="bulk_product_{{ $product->id }}" type="checkbox" name="product_ids[]"
                                                value="{{ $product->id }}" form="bulk-delete-products-form"
                                                class="product-row-checkbox h-4 w-4 rounded border-black/25 text-gray-900 focus:ring-orange-500">
                                        </td>
                                    @endif
                                    <td class="px-6 py-4">
                                        <div class="space-y-1">
                                            <details class="group">
                                                <summary class="product-details-summary cursor-pointer font-medium text-gray-900">
                                                    <span class="underline-offset-4 group-open:underline">{{ $product->name }}</span>
                                                </summary>
                                                <div class="mt-2 rounded-md bg-gray-50/90 px-3 py-2 text-xs text-gray-700 ring-1 ring-inset ring-black/10">
                                                    @if ($canViewProductCostStock)
                                                        @if ($costCalc)
                                                            <div class="font-semibold text-gray-900">{{ $costCalc['title'] ?? 'Average cost calculation' }}</div>
                                                            <div class="mt-1">
                                                                Formula:
                                                                <span class="font-medium text-gray-900">{{ $costCalc['formula'] ?? '-' }}</span>
                                                            </div>
                                                            <div class="mt-1">
                                                                Old cost: <span class="font-medium text-gray-900">&#8369;{{ number_format((float) ($costCalc['old_cost'] ?? 0), 2) }}</span>
                                                                &middot; Incoming cost: <span class="font-medium text-gray-900">&#8369;{{ number_format((float) ($costCalc['incoming_cost'] ?? 0), 2) }}</span>
                                                                &middot; New average: <span class="font-medium text-gray-900">&#8369;{{ number_format((float) ($costCalc['result_cost'] ?? 0), 2) }}</span>
                                                            </div>
                                                            <div class="mt-1">
                                                                Stock: {{ (int) ($costCalc['old_stock'] ?? 0) }} + {{ (int) ($costCalc['incoming_stock'] ?? 0) }} = <span class="font-medium {{ $stockToneClass((int) ($costCalc['new_stock'] ?? 0)) }}">{{ (int) ($costCalc['new_stock'] ?? 0) }}</span>
                                                                @if (!empty($costCalc['at']))
                                                                    &middot; {{ $costCalc['at'] }}
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="font-semibold text-gray-900">No calculation yet</div>
                                                            <div class="mt-1">No stock-in cost calculation is stored for this product yet.</div>
                                                        @endif
                                                    @else
                                                        <div class="font-semibold text-gray-900">Restricted details</div>
                                                        <div class="mt-1">Cost and initial stock details require permission.</div>
                                                    @endif
                                                </div>
                                            </details>
                                        </div>
                                        <div class="mt-0.5 text-xs text-gray-600">
                                            Updated {{ optional($product->updated_at)->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900 tabular-nums">
                                        &#8369;{{ number_format((float) $product->price, 2) }}
                                    </td>
                                    @if ($canViewProductCostStock)
                                        <td class="px-6 py-4 text-right text-gray-900 tabular-nums">
                                            @if ($product->cost_price !== null)
                                                &#8369;{{ number_format((float) $product->cost_price, 2) }}
                                            @else
                                                <span class="text-gray-900/50">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right text-gray-900 tabular-nums">
                                            {{ number_format((int) $product->initial_stock) }}
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 text-right tabular-nums {{ $stockToneClass((int) $product->stock) }}">
                                        {{ number_format((int) $product->stock) }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-900/80">
                                        {{ $product->category?->parent ? $product->category->parent->name.' / '.$product->category->name : ($product->category->name ?? 'Uncategorized') }}
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        @if ($canEditProducts)
                                            <a href="{{ route('admin.products.edit', array_merge(['product' => $product], $indexQuery)) }}"
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
                            @empty
                                <tr>
                                    <td class="px-6 py-10 text-center text-sm text-gray-700" colspan="{{ $tableColumnCount }}">
                                        No products found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    <style>
        .products-filter-row {
            display: grid;
            gap: 0.5rem;
            grid-template-columns: 1fr;
            align-items: center;
        }

        .products-filter-search {
            min-width: 0;
        }

        .product-details-summary {
            list-style: none;
        }

        .product-details-summary::-webkit-details-marker {
            display: none;
        }

        .product-details-summary::marker {
            content: '';
        }

        .products-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: rgb(249 250 251);
            box-shadow: inset 0 -1px 0 rgba(15, 23, 42, 0.08);
        }

        body.admin-theme-dark .products-table thead th {
            background-color: #111827;
            box-shadow: inset 0 -1px 0 rgba(148, 163, 184, 0.22);
        }

        @media (min-width: 1024px) {
            .products-filter-row {
                grid-template-columns: minmax(0, 1.25fr) 15rem 11rem;
            }
        }
    </style>

    <script>
        (() => {
            const form = document.getElementById('products-filter-form');
            const categorySelect = document.getElementById('category');
            const stockSelect = document.getElementById('stock');

            if (!form || !categorySelect || !stockSelect) return;

            const submitFilters = () => {
                form.submit();
            };

            categorySelect.addEventListener('change', submitFilters);
            stockSelect.addEventListener('change', submitFilters);
        })();

        (() => {
            const input = document.getElementById('product_import_file');
            const label = document.getElementById('product_import_file_name');
            if (!input || !label) return;

            input.addEventListener('change', () => {
                const fileName = input.files && input.files[0] ? input.files[0].name : 'No file selected';
                label.textContent = fileName;
                label.title = fileName;
            });
        })();

        (() => {
            const form = document.getElementById('bulk-delete-products-form');
            const selectAll = document.getElementById('select_all_products');
            const count = document.getElementById('bulk-delete-products-count');
            const submitButton = document.getElementById('bulk-delete-products-button');

            if (!form || !selectAll || !count || !submitButton) return;

            const rowCheckboxes = Array.from(document.querySelectorAll('.product-row-checkbox'));

            const syncState = () => {
                const selectedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
                const totalCount = rowCheckboxes.length;

                count.textContent = `${selectedCount} selected`;
                submitButton.disabled = selectedCount === 0;
                selectAll.checked = totalCount > 0 && selectedCount === totalCount;
                selectAll.indeterminate = selectedCount > 0 && selectedCount < totalCount;
            };

            selectAll.addEventListener('change', () => {
                rowCheckboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
                syncState();
            });

            rowCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncState);
            });

            form.addEventListener('submit', (event) => {
                if (!rowCheckboxes.some((checkbox) => checkbox.checked)) {
                    event.preventDefault();
                }
            });

            syncState();
        })();
    </script>
</x-app-layout>
