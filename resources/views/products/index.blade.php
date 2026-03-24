@extends('layouts.public')

@section('title', 'Pricelist')
@section('meta_description', 'Browse computer parts by category, compare prices, and view product details at Infinite Computers.')

@section('content')
    @php
        $diyBuilderCategories = collect($builderCategories ?? [])->values();
        $isDiyBuilderPage = request('tab') === 'diy_builder';
        $initialTab = $isDiyBuilderPage ? 'diy_builder' : 'price_list';
        $allCategories = collect($categories ?? []);
        $mainCategoryOptions = $allCategories
            ->filter(fn ($category) => ($category->parent_id ?? null) === null)
            ->values();
        $subcategoriesByParent = $allCategories
            ->filter(fn ($category) => ($category->parent_id ?? null) !== null)
            ->groupBy(fn ($category) => (int) $category->parent_id);
        $selectedCategoryLabel = optional($allCategories->first(fn ($category) => (int) $category->id === (int) $categoryId))->name ?? 'All categories';
    @endphp

    <div class="theme-panel relative z-20 rounded-2xl p-6 shadow-sm sm:p-8">
        @if (! $isDiyBuilderPage)
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">Price List</h1>
                    <p class="theme-muted mt-2 text-sm">
                        Browse products and filter by category.
                    </p>
                </div>

                <form method="GET" x-data="{}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    @if (!empty($search))
                        <input type="hidden" name="search" value="{{ $search }}">
                    @endif

                    <div class="relative w-full sm:w-64 lg:w-72"
                        x-data="{
                            open: false,
                            activeSubParent: '',
                            selectedCategory: @js((string) ($categoryId ?: '')),
                            selectedLabel: @js((string) $selectedCategoryLabel),
                            selectCategory(id, label) {
                                const nextCategory = String(id || '');
                                this.selectedCategory = nextCategory;
                                this.selectedLabel = String(label || 'All categories');
                                if (this.$refs.categoryInput) {
                                    this.$refs.categoryInput.value = nextCategory;
                                }
                                this.open = false;
                                this.activeSubParent = '';
                                this.$nextTick(() => this.$root.closest('form')?.requestSubmit());
                            },
                        }"
                        :class="open ? 'z-[120]' : ''"
                        @keydown.escape.window="open = false; activeSubParent = ''">
                        <label for="category_filter_hidden" class="sr-only">Category</label>
                        <input id="category_filter_hidden" x-ref="categoryInput" type="hidden" name="category" x-model="selectedCategory">

                        <button type="button"
                            class="theme-input flex w-full items-center justify-between rounded-md px-3 py-2 text-sm shadow-sm"
                            aria-haspopup="menu"
                            :aria-expanded="open ? 'true' : 'false'"
                            @click="open = !open; if (!open) activeSubParent = '';">
                            <span class="truncate" x-text="selectedLabel"></span>
                            <svg class="h-4 w-4 shrink-0 text-gray-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-cloak x-show="open"
                            class="theme-card absolute left-0 right-0 z-[300] mt-1 max-h-[70vh] overflow-y-auto rounded-md py-1 shadow-lg"
                            role="menu"
                            @click.outside="open = false; activeSubParent = ''">
                            <button type="button"
                                class="block w-full px-3 py-2 text-left text-sm text-gray-900 hover:bg-orange-100/60"
                                @mouseenter="activeSubParent = ''"
                                @focus="activeSubParent = ''"
                                @click="selectCategory('', 'All categories')">
                                All categories
                            </button>

                            @foreach ($mainCategoryOptions as $category)
                                @php($subcategories = $subcategoriesByParent->get((int) $category->id, collect()))
                                @if ($subcategories->isNotEmpty())
                                    <div class="relative"
                                        @mouseleave="if (!window.matchMedia('(hover: none)').matches) activeSubParent = ''">
                                        <button type="button"
                                            class="flex w-full items-center justify-between px-3 py-2 text-left text-sm text-gray-900 hover:bg-orange-100/60"
                                            @mouseenter="activeSubParent = '{{ (int) $category->id }}'"
                                            @focus="activeSubParent = '{{ (int) $category->id }}'"
                                            @click.prevent="if (window.matchMedia('(hover: none)').matches) { activeSubParent = activeSubParent === '{{ (int) $category->id }}' ? '' : '{{ (int) $category->id }}'; } else { selectCategory($el.dataset.categoryId, $el.dataset.categoryLabel); }"
                                            data-category-id="{{ $category->id }}"
                                            data-category-label="{{ $category->name }}">
                                            <span>{{ $category->name }}</span>
                                            <svg class="h-4 w-4 shrink-0 text-gray-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 011.06 0l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 11-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div x-cloak x-show="activeSubParent === '{{ (int) $category->id }}'"
                                            class="theme-card mx-2 mb-2 rounded-md border border-black/10 bg-white py-1 shadow-sm">
                                            <button type="button"
                                                class="block w-full px-4 py-2 text-left text-sm font-medium text-gray-900 hover:bg-orange-100/60"
                                                data-category-id="{{ $category->id }}"
                                                data-category-label="{{ $category->name }}"
                                                @click="selectCategory($el.dataset.categoryId, $el.dataset.categoryLabel)">
                                                All {{ $category->name }}
                                            </button>
                                            @foreach ($subcategories as $subcategory)
                                                <button type="button"
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-900 hover:bg-orange-100/60"
                                                    data-category-id="{{ $subcategory->id }}"
                                                    data-category-label="{{ $subcategory->name }}"
                                                    @click="selectCategory($el.dataset.categoryId, $el.dataset.categoryLabel)">
                                                    {{ $subcategory->name }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <button type="button"
                                        class="block w-full px-3 py-2 text-left text-sm text-gray-900 hover:bg-orange-100/60"
                                        @mouseenter="activeSubParent = ''"
                                        data-category-id="{{ $category->id }}"
                                        data-category-label="{{ $category->name }}"
                                        @click="selectCategory($el.dataset.categoryId, $el.dataset.categoryLabel)">
                                        {{ $category->name }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="w-full sm:w-56">
                        <label for="sort" class="sr-only">Sort</label>
                        <select id="sort" name="sort"
                            @change="$el.form.requestSubmit()"
                            class="theme-input block w-full rounded-md px-3 py-2 text-sm shadow-sm">
                            <option value="name_asc" {{ ($sort ?? 'name_asc') === 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
                            <option value="name_desc" {{ ($sort ?? 'name_asc') === 'name_desc' ? 'selected' : '' }}>Name: Z-A</option>
                            <option value="price_asc" {{ ($sort ?? 'name_asc') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ ($sort ?? 'name_asc') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="latest" {{ ($sort ?? 'name_asc') === 'latest' ? 'selected' : '' }}>Newest</option>
                        </select>
                    </div>
                </form>
            </div>
        @else
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">DIY PC Builder</h1>
                <p class="theme-muted mt-2 text-sm">
                    Build your setup by picking one item per category and get an instant estimated total.
                </p>
            </div>
        @endif
    </div>

    <section id="pricelist-tabs" class="relative z-10 mt-6" x-data="{ activeTab: @js($initialTab) }">
        @unless ($isDiyBuilderPage)
            <div class="theme-panel flex flex-wrap items-center justify-between gap-3 rounded-2xl p-4 shadow-sm">
                <div class="inline-flex items-center rounded-lg bg-white/55 p-1">
                    <button type="button"
                        class="rounded-md px-3 py-2 text-sm font-medium transition"
                        :class="activeTab === 'price_list' ? 'theme-nav-link-active shadow-sm' : 'theme-nav-link'"
                        @click="activeTab = 'price_list'">
                        Price List
                    </button>
                    <button type="button"
                        class="rounded-md px-3 py-2 text-sm font-medium transition"
                        :class="activeTab === 'diy_builder' ? 'theme-nav-link-active shadow-sm' : 'theme-nav-link'"
                        @click="activeTab = 'diy_builder'">
                        DIY PC Builder
                    </button>
                </div>
            </div>
        @endunless

        <div class="mt-4" x-show="activeTab === 'diy_builder'" x-transition>
            <section class="theme-card rounded-2xl p-6 shadow-sm sm:p-8"
                x-data="{
                    categories: @js($diyBuilderCategories),
                    selected: {},
                    qty: {},
                    extraStorageRows: [],
                    nextStorageUid: 1,
                    init() {
                        this.categories.forEach((category) => {
                            this.selected[category.id] = '';
                            this.qty[category.id] = 1;
                        });
                        this.extraStorageRows = [];
                        this.nextStorageUid = 1;
                        this.enforceDdrCompatibility();
                    },
                    selectedProduct(categoryId) {
                        const category = this.categories.find((entry) => String(entry.id) === String(categoryId));
                        if (!category) return null;
                        const productId = String(this.selected[categoryId] || '');
                        if (!productId) return null;
                        return category.products.find((product) => String(product.id) === productId) || null;
                    },
                    normalizeQty(categoryId) {
                        const next = parseInt(this.qty[categoryId], 10);
                        if (!Number.isFinite(next) || next < 1) {
                            this.qty[categoryId] = 1;
                            return;
                        }
                        this.qty[categoryId] = next;
                    },
                    lineTotal(categoryId) {
                        const product = this.selectedProduct(categoryId);
                        if (!product) return 0;
                        const quantity = Number(this.qty[categoryId] || 1);
                        return Number(product.price || 0) * Math.max(1, quantity);
                    },
                    get pickedItems() {
                        const baseItems = this.categories
                            .map((category) => {
                                const product = this.selectedProduct(category.id);
                                if (!product) return null;
                                const quantity = Math.max(1, Number(this.qty[category.id] || 1));
                                const unitPrice = Number(product.price || 0);
                                return {
                                    category: category.name,
                                    name: product.name,
                                    qty: quantity,
                                    unitPrice,
                                    total: quantity * unitPrice,
                                };
                            })
                            .filter(Boolean);

                        const extraStorageItems = this.extraStorageRows
                            .map((row) => {
                                const product = this.selectedStorageRowProduct(row);
                                if (!product) return null;
                                const quantity = Math.max(1, Number(row.qty || 1));
                                const unitPrice = Number(product.price || 0);
                                return {
                                    category: this.storageCategory()?.name || 'Storage',
                                    name: product.name,
                                    qty: quantity,
                                    unitPrice,
                                    total: quantity * unitPrice,
                                };
                            })
                            .filter(Boolean);

                        return [...baseItems, ...extraStorageItems];
                    },
                    get subtotal() {
                        return this.pickedItems.reduce((sum, item) => sum + Number(item.total || 0), 0);
                    },
                    clearAll() {
                        this.categories.forEach((category) => {
                            this.selected[category.id] = '';
                            this.qty[category.id] = 1;
                        });
                        this.extraStorageRows = [];
                        this.nextStorageUid = 1;
                    },
                    storageCategory() {
                        return this.categories.find((entry) => String(entry.id) === 'storage') || null;
                    },
                    selectedStorageRowProduct(row) {
                        const category = this.storageCategory();
                        if (!category) return null;
                        const productId = String(row?.product_id || '');
                        if (!productId) return null;
                        return category.products.find((product) => String(product.id) === productId) || null;
                    },
                    normalizeStorageQty(row) {
                        if (!row) return;
                        const next = parseInt(row.qty, 10);
                        if (!Number.isFinite(next) || next < 1) {
                            row.qty = 1;
                            return;
                        }
                        row.qty = next;
                    },
                    storageRowLineTotal(row) {
                        const product = this.selectedStorageRowProduct(row);
                        if (!product) return 0;
                        const quantity = Math.max(1, Number(row?.qty || 1));
                        return Number(product.price || 0) * quantity;
                    },
                    addStorageRow() {
                        this.extraStorageRows.push({
                            uid: this.nextStorageUid++,
                            product_id: '',
                            qty: 1,
                        });
                    },
                    removeStorageRow(uid) {
                        this.extraStorageRows = this.extraStorageRows
                            .filter((row) => String(row.uid) !== String(uid));
                    },
                    inferDdrProfile(value) {
                        const normalized = String(value || '').toLowerCase().replace(/\s+/g, '');
                        if (normalized.includes('ddr5')) return 'ddr5';
                        if (normalized.includes('ddr4')) return 'ddr4';
                        if (normalized.includes('ddr3')) return 'ddr3';
                        return '';
                    },
                    inferCpuBrandProfile(value) {
                        const normalized = String(value || '').toLowerCase().replace(/\s+/g, '');
                        if (!normalized) return '';

                        const amdNeedles = ['amd', 'ryzen', 'athlon', 'threadripper', 'am4', 'am5', 'a320', 'a520', 'b350', 'b450', 'b550', 'b650', 'b650e', 'x370', 'x470', 'x570', 'x670', 'x670e'];
                        const intelNeedles = ['intel', 'corei', 'core2', 'pentium', 'celeron', 'xeon', 'lga', 'h310', 'h410', 'h510', 'h610', 'h670', 'b360', 'b365', 'b460', 'b560', 'b660', 'b760', 'z370', 'z390', 'z490', 'z590', 'z690', 'z790'];

                        if (amdNeedles.some((needle) => normalized.includes(needle))) return 'amd';
                        if (intelNeedles.some((needle) => normalized.includes(needle))) return 'intel';
                        return '';
                    },
                    productDdrProfile(product) {
                        if (!product) return '';
                        return this.inferDdrProfile(product.subcategory || '')
                            || this.inferDdrProfile(product.name || '');
                    },
                    productCpuBrandProfile(product) {
                        if (!product) return '';
                        return this.inferCpuBrandProfile(product.subcategory || '')
                            || this.inferCpuBrandProfile(product.name || '');
                    },
                    activeProcessorDdrProfile() {
                        return this.productDdrProfile(this.selectedProduct('processor'));
                    },
                    activeProcessorBrandProfile() {
                        return this.productCpuBrandProfile(this.selectedProduct('processor'));
                    },
                    usesDdrCompatibility(categoryId) {
                        return String(categoryId) === 'motherboard' || String(categoryId) === 'desktop_ram';
                    },
                    usesBrandCompatibility(categoryId) {
                        return String(categoryId) === 'motherboard';
                    },
                    isCompatibleProduct(categoryId, product) {
                        let ddrCompatible = true;
                        if (this.usesDdrCompatibility(categoryId)) {
                            const processorDdrProfile = this.activeProcessorDdrProfile();
                            if (processorDdrProfile) {
                                ddrCompatible = this.productDdrProfile(product) === processorDdrProfile;
                            }
                        }

                        let brandCompatible = true;
                        if (this.usesBrandCompatibility(categoryId)) {
                            const processorBrandProfile = this.activeProcessorBrandProfile();
                            if (processorBrandProfile) {
                                brandCompatible = this.productCpuBrandProfile(product) === processorBrandProfile;
                            }
                        }

                        return ddrCompatible && brandCompatible;
                    },
                    dedupeProducts(list, selectedProductId = null) {
                        const preferredId = selectedProductId ? String(selectedProductId) : '';
                        const unique = new Map();

                        (Array.isArray(list) ? list : []).forEach((product) => {
                            const subcategory = String(product?.subcategory || '')
                                .trim()
                                .toLowerCase()
                                .replace(/\s+/g, ' ');
                            const normalizedName = String(product?.name || '')
                                .trim()
                                .toLowerCase()
                                .replace(/\s+/g, ' ');
                            const key = `${subcategory}::${normalizedName}`;

                            if (!unique.has(key)) {
                                unique.set(key, product);
                                return;
                            }

                            if (preferredId && String(product?.id ?? '') === preferredId) {
                                unique.set(key, product);
                            }
                        });

                        return Array.from(unique.values());
                    },
                    optionGroups(category) {
                        if (!Array.isArray(category?.groups) || category.groups.length === 0) {
                            return [];
                        }

                        return category.groups
                            .map((group) => ({
                                label: group.label,
                                products: this.dedupeProducts(
                                    (Array.isArray(group.products) ? group.products : [])
                                        .filter((product) => this.isCompatibleProduct(category.id, product)),
                                    this.selected[category.id]
                                ),
                            }))
                            .filter((group) => group.products.length > 0);
                    },
                    optionProducts(category) {
                        return this.dedupeProducts(
                            (Array.isArray(category?.products) ? category.products : [])
                                .filter((product) => this.isCompatibleProduct(category.id, product)),
                            this.selected[category.id]
                        );
                    },
                    onCategoryChange(categoryId) {
                        if (String(categoryId) === 'processor') {
                            this.enforceDdrCompatibility();
                        }
                    },
                    enforceDdrCompatibility() {
                        ['motherboard', 'desktop_ram'].forEach((targetId) => {
                            const selectedProduct = this.selectedProduct(targetId);
                            if (!selectedProduct) return;
                            if (this.isCompatibleProduct(targetId, selectedProduct)) return;
                            this.selected[targetId] = '';
                            this.qty[targetId] = 1;
                        });
                    },
                    formatMoney(amount) {
                        return new Intl.NumberFormat('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        }).format(Number(amount || 0));
                    },
                }"
                x-init="init()">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-gray-900">DIY PC Builder</h2>
                        <p class="theme-muted mt-1 text-sm">
                            Build your setup by picking one item per category and get an instant estimated total.
                        </p>
                    </div>
                    <button type="button"
                        class="theme-secondary-btn inline-flex items-center rounded-md px-3 py-2 text-sm font-medium"
                        @click="clearAll()">
                        Clear builder
                    </button>
                </div>

                <template x-if="categories.length === 0">
                    <div class="theme-card theme-muted mt-4 rounded-lg px-4 py-3 text-sm">
                        No in-stock components available for DIY builder right now.
                    </div>
                </template>

                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-5" x-show="categories.length > 0">
                    <div class="lg:col-span-3 space-y-3">
                        <template x-for="category in categories" :key="category.id">
                            <div class="theme-card rounded-xl p-4">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-10 sm:items-end">
                                    <div class="sm:col-span-6">
                                        <label class="block text-sm font-medium text-gray-900" :for="`diy_${category.id}`" x-text="category.name"></label>
                                        <select :id="`diy_${category.id}`"
                                            class="theme-input mt-1 block w-full rounded-md text-sm shadow-sm"
                                            x-model="selected[category.id]"
                                            @change="onCategoryChange(category.id)">
                                            <option value="">Select component...</option>
                                            <template x-if="optionGroups(category).length > 0">
                                                <template x-for="group in optionGroups(category)" :key="`${category.id}_${group.label}`">
                                                    <optgroup :label="group.label">
                                                        <template x-for="product in group.products" :key="product.id">
                                                            <option :value="String(product.id)"
                                                                x-text="product.name"></option>
                                                        </template>
                                                    </optgroup>
                                                </template>
                                            </template>
                                            <template x-if="optionGroups(category).length === 0">
                                                <template x-for="product in optionProducts(category)" :key="product.id">
                                                    <option :value="String(product.id)"
                                                        x-text="product.name"></option>
                                                </template>
                                            </template>
                                        </select>
                                        <p x-cloak x-show="String(category.id) === 'cpu_cooler'" class="theme-muted mt-1 text-xs">
                                            Leave blank for stock cooler.
                                        </p>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-900">Qty</label>
                                        <input type="number" min="1" step="1" inputmode="numeric"
                                            class="theme-input mt-1 block w-full rounded-md text-sm shadow-sm disabled:bg-gray-100"
                                            x-model.number="qty[category.id]"
                                            @input="normalizeQty(category.id)"
                                            :disabled="!selected[category.id]">
                                    </div>

                                    <div class="sm:col-span-2 text-right">
                                        <div class="theme-muted text-xs">Line total</div>
                                        <div class="inline-flex w-full items-baseline justify-end gap-1 whitespace-nowrap text-base font-semibold tabular-nums text-gray-900">
                                            <span>PHP</span>
                                            <span x-text="formatMoney(lineTotal(category.id))"></span>
                                        </div>
                                    </div>
                                </div>

                                <template x-if="String(category.id) === 'storage'">
                                    <div class="mt-3 border-t border-black/10 pt-3">
                                        <div class="flex items-center justify-end">
                                            <button type="button"
                                                class="theme-secondary-btn inline-flex items-center rounded-md px-3 py-2 text-xs font-medium"
                                                @click="addStorageRow()">
                                                Add more storage
                                            </button>
                                        </div>

                                        <div class="mt-3 space-y-3" x-show="extraStorageRows.length > 0">
                                            <template x-for="row in extraStorageRows" :key="row.uid">
                                                <div class="theme-card rounded-lg p-3">
                                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-10 sm:items-end">
                                                        <div class="sm:col-span-6">
                                                            <label class="block text-sm font-medium text-gray-900">Additional storage</label>
                                                            <select class="theme-input mt-1 block w-full rounded-md text-sm shadow-sm"
                                                                x-model="row.product_id">
                                                                <option value="">Select storage...</option>
                                                                <template x-if="optionGroups(category).length > 0">
                                                                    <template x-for="group in optionGroups(category)" :key="`${row.uid}_${group.label}`">
                                                                        <optgroup :label="group.label">
                                                                            <template x-for="product in group.products" :key="product.id">
                                                                                <option :value="String(product.id)"
                                                                                    x-text="product.name"></option>
                                                                            </template>
                                                                        </optgroup>
                                                                    </template>
                                                                </template>
                                                                <template x-if="optionGroups(category).length === 0">
                                                                    <template x-for="product in optionProducts(category)" :key="product.id">
                                                                        <option :value="String(product.id)"
                                                                            x-text="product.name"></option>
                                                                    </template>
                                                                </template>
                                                            </select>
                                                        </div>

                                                        <div class="sm:col-span-2">
                                                            <label class="block text-sm font-medium text-gray-900">Qty</label>
                                                            <input type="number" min="1" step="1" inputmode="numeric"
                                                                class="theme-input mt-1 block w-full rounded-md text-sm shadow-sm disabled:bg-gray-100"
                                                                x-model.number="row.qty"
                                                                @input="normalizeStorageQty(row)"
                                                                :disabled="!row.product_id">
                                                        </div>

                                                        <div class="sm:col-span-2 text-right">
                                                            <div class="theme-muted text-xs">Line total</div>
                                                            <div class="inline-flex w-full items-baseline justify-end gap-1 whitespace-nowrap text-base font-semibold tabular-nums text-gray-900">
                                                                <span>PHP</span>
                                                                <span x-text="formatMoney(storageRowLineTotal(row))"></span>
                                                            </div>
                                                            <button type="button"
                                                                class="mt-1 text-xs font-medium text-rose-700 hover:text-rose-600"
                                                                @click="removeStorageRow(row.uid)">
                                                                Remove
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <div class="mt-3" x-show="selectedProduct(category.id)">
                                    <div class="theme-card flex items-center gap-3 rounded-lg px-3 py-2">
                                        <template x-if="selectedProduct(category.id)?.image">
                                            <div class="theme-image-wrap h-12 w-12 rounded-md">
                                                <img :src="selectedProduct(category.id).image"
                                                    :alt="`${selectedProduct(category.id).name} image`"
                                                    class="h-12 w-12 rounded-md object-cover">
                                            </div>
                                        </template>
                                        <template x-if="!selectedProduct(category.id)?.image">
                                            <div class="theme-image-placeholder flex h-12 w-12 items-center justify-center rounded-md text-[10px] font-medium">
                                                No image
                                            </div>
                                        </template>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-gray-900" x-text="selectedProduct(category.id)?.name"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="theme-panel lg:col-span-2 rounded-xl p-4">
                        <h3 class="text-base font-semibold text-gray-900">Build Summary</h3>
                        <p class="theme-muted mt-1 text-xs">Estimated only. Final pricing may vary by availability and promos.</p>

                        <template x-if="pickedItems.length === 0">
                            <div class="theme-card theme-muted mt-4 rounded-md px-3 py-4 text-sm">
                                Start selecting components to see your build summary.
                            </div>
                        </template>

                        <div class="mt-4 space-y-2" x-show="pickedItems.length > 0">
                            <template x-for="(item, idx) in pickedItems" :key="`${item.category}_${idx}`">
                                <div class="theme-card rounded-md px-3 py-2">
                                    <div class="grid grid-cols-[minmax(0,1fr)_9rem] items-start gap-2 sm:grid-cols-[minmax(0,1fr)_10.5rem]">
                                        <div class="min-w-0">
                                            <div class="theme-muted text-xs font-semibold uppercase tracking-wide" x-text="item.category"></div>
                                            <div class="truncate text-sm font-medium text-gray-900" x-text="item.name"></div>
                                            <div class="theme-muted mt-0.5 text-xs">
                                                Qty <span x-text="item.qty"></span>
                                            </div>
                                        </div>
                                        <div class="inline-flex w-[9rem] items-baseline justify-end gap-1 whitespace-nowrap text-right text-sm font-semibold tabular-nums text-gray-900 sm:w-[10.5rem]">
                                            <span>PHP</span>
                                            <span x-text="formatMoney(item.total)"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 border-t border-black/20 pt-3">
                            <div class="theme-muted flex items-center justify-between text-sm">
                                <span>Selected items</span>
                                <span class="font-medium tabular-nums" x-text="pickedItems.length"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-base font-semibold text-gray-900">
                                <span>Estimated Total</span>
                                <span class="inline-flex w-[9rem] items-baseline justify-end gap-1 tabular-nums whitespace-nowrap text-right sm:w-[10.5rem]">
                                    <span>PHP</span>
                                    <span x-text="formatMoney(subtotal)"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        @unless ($isDiyBuilderPage)
        <div class="mt-4" x-show="activeTab === 'price_list'" x-transition>
            @php($listQuery = request()->only(['search', 'category', 'page', 'sort']))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @forelse($products as $product)
                    <a href="{{ route('pricelist.show', array_merge(['product' => $product], $listQuery)) }}"
                        class="theme-card theme-card-hover group block rounded-2xl p-5 text-left shadow-sm"
                        aria-label="View details for {{ $product->name }}">
                        @if ($product->image_src)
                            <div class="theme-image-wrap mb-4 overflow-hidden rounded-xl bg-gray-50/70">
                                <img src="{{ $product->image_src }}" alt="{{ $product->name }}" class="h-40 w-full object-cover">
                            </div>
                        @endif

                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-semibold text-gray-900">{{ $product->name }}</h3>
                                <p class="theme-muted mt-1 truncate text-sm">
                                    {{ $product->category?->parent ? $product->category->parent->name.' / '.$product->category->name : ($product->category->name ?? 'Uncategorized') }}
                                </p>
                            </div>

                            <span class="theme-panel shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium text-gray-900">
                                {{ $product->stock > 0 ? 'In stock' : 'Out' }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-end justify-between">
                            <div class="text-2xl font-semibold text-gray-900 tabular-nums">
                                &#8369;{{ number_format((float) $product->price, 2) }}
                            </div>
                        </div>

                        @if ($product->description)
                            <p class="theme-muted mt-3 text-sm line-clamp-2">{{ $product->description }}</p>
                        @else
                            <p class="theme-muted mt-3 text-sm">No description yet. Open details page.</p>
                        @endif

                        <div class="mt-4 flex items-center justify-end text-sm font-medium text-gray-900">
                            <span class="transition group-hover:translate-x-0.5">View details →</span>
                        </div>
                    </a>
                @empty
                    <div class="theme-card sm:col-span-2 lg:col-span-4 rounded-2xl p-10 text-center shadow-sm">
                        <div class="text-base font-medium text-gray-900">No products found.</div>
                        <div class="theme-muted mt-2 text-sm">Try a different search or clear the category filter.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        </div>
        @endunless
    </section>
@endsection
