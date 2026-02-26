<x-app-layout>
    @php($canEditCategories = \App\Support\AdminAccess::hasPermission(auth()->user(), 'categories.edit'))

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Categories</h2>
                <p class="mt-1 text-sm text-gray-600">Group products to keep your list organized.</p>
            </div>

            @if ($canEditCategories)
                <a href="{{ route('admin.categories.create') }}"
                    class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Add category
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10">
                            @forelse($categories as $category)
                                <tr class="hover:bg-gray-50/90">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $category->name }}</div>
                                        <div class="mt-0.5 text-xs text-gray-600">
                                            Updated {{ optional($category->updated_at)->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        @if ($canEditCategories)
                                            <a href="{{ route('admin.categories.edit', $category) }}"
                                                class="text-sm font-medium text-gray-900 underline underline-offset-4 hover:no-underline">
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Delete this category? This action cannot be undone.');">
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
                                    <td class="px-6 py-10 text-center text-sm text-gray-700" colspan="2">
                                        No categories yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
