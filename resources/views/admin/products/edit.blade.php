<x-app-layout>
    @php($indexQuery = $indexQuery ?? request()->only(['search', 'category', 'stock', 'page']))
    @php($mainCategories = collect($categories ?? [])->filter(fn ($category) => ($category->parent_id ?? null) === null)->values())
    @php($subcategoriesByParent = collect($categories ?? [])->filter(fn ($category) => ($category->parent_id ?? null) !== null)->groupBy(fn ($category) => (int) $category->parent_id))
    @php($orphanSubcategories = collect($categories ?? [])->filter(fn ($category) => ($category->parent_id ?? null) !== null && $category->parent === null)->values())

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Edit product</h2>
                <p class="mt-1 text-sm text-gray-600">Update details and stock.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                <form action="{{ route('admin.products.update', array_merge(['product' => $product], $indexQuery)) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-900">Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $product->name) }}"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-900">Description</label>
                        <textarea id="description" name="description" rows="4"
                            placeholder="Add full product description, specs, and notes..."
                            class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('description', $product->description) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="image" class="block text-sm font-medium text-gray-900">Replace image</label>
                            <input id="image" type="file" name="image" accept="image/*"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('image')" />
                        </div>

                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-900">Image URL</label>
                            <input id="image_url" type="url" name="image_url" value="{{ old('image_url', $product->image_url) }}" placeholder="https://example.com/image.jpg"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('image_url')" />
                        </div>
                    </div>

                    @if ($product->image_src)
                        <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-black/10">
                            <div class="flex items-center gap-3">
                                <img src="{{ $product->image_src }}" alt="{{ $product->name }}" class="h-16 w-16 rounded-md object-cover ring-1 ring-black/10">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-900">
                                    <input type="checkbox" name="remove_image" value="1" {{ old('remove_image') ? 'checked' : '' }}
                                        class="rounded border-black/30 text-gray-900 focus:ring-orange-500">
                                    Remove current image
                                </label>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-1">
                            <label for="price" class="block text-sm font-medium text-gray-900">Price</label>
                            <input id="price" type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('price')" />
                        </div>

                        <div class="sm:col-span-1">
                            <label for="cost_price" class="block text-sm font-medium text-gray-900">Cost price</label>
                            <input id="cost_price" type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('cost_price')" />
                        </div>

                        <div class="sm:col-span-1">
                            <label for="initial_stock" class="block text-sm font-medium text-gray-900">Initial stock</label>
                            <input id="initial_stock" type="number" name="initial_stock" value="{{ old('initial_stock', $product->initial_stock) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('initial_stock')" />
                        </div>

                        <div class="sm:col-span-1">
                            <label for="stock" class="block text-sm font-medium text-gray-900">Current stock</label>
                            <input id="stock" type="number" name="stock" value="{{ old('stock', $product->stock) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <x-input-error class="mt-2" :messages="$errors->get('stock')" />
                        </div>

                        <div class="sm:col-span-4">
                            <label for="category_id" class="block text-sm font-medium text-gray-900">Category</label>
                            <select id="category_id" name="category_id"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="" disabled>Select…</option>
                                @foreach($mainCategories as $category)
                                    <option value="{{ $category->id }}" {{ (string) old('category_id', $product->category_id) === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @foreach($subcategoriesByParent->get((int) $category->id, collect()) as $subcategory)
                                        <option value="{{ $subcategory->id }}" {{ (string) old('category_id', $product->category_id) === (string) $subcategory->id ? 'selected' : '' }}>
                                            {{ $category->name }} / {{ $subcategory->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                                @foreach($orphanSubcategories as $subcategory)
                                    <option value="{{ $subcategory->id }}" {{ (string) old('category_id', $product->category_id) === (string) $subcategory->id ? 'selected' : '' }}>
                                        {{ $subcategory->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Update
                        </button>
                        <a href="{{ route('admin.products.index', $indexQuery) }}"
                            class="text-sm font-medium text-gray-700 hover:text-gray-900 underline underline-offset-4 hover:no-underline">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
