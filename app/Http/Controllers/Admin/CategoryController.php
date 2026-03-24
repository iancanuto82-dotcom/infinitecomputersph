<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\AuditLogger;
use App\Support\PublicCatalogCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->with([
                'parent:id,name',
                'children' => fn ($query) => $query
                    ->select(['id', 'name', 'parent_id'])
                    ->withCount('products')
                    ->orderBy('name'),
            ])
            ->withCount('products')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        $childProductCountsByParent = $categories
            ->filter(fn (Category $category): bool => $category->parent_id !== null)
            ->groupBy(fn (Category $category): int => (int) $category->parent_id)
            ->map(fn ($children): int => (int) $children->sum('products_count'));

        $categories->each(function (Category $category) use ($childProductCountsByParent): void {
            $directCount = (int) ($category->products_count ?? 0);
            $childCount = (int) ($childProductCountsByParent->get((int) $category->id, 0));

            $category->setAttribute('total_products_count', $directCount + $childCount);
            $category->setAttribute('subcategory_products_count', $childCount);
        });

        return view('admin.categories.index', compact('categories'));
    }

    public function create(Request $request)
    {
        $parentCategories = $this->parentCategoryOptions();
        $selectedParentId = $request->integer('parent');

        if (! $parentCategories->contains(fn (Category $category): bool => (int) $category->id === $selectedParentId)) {
            $selectedParentId = null;
        }

        return view('admin.categories.create', compact('parentCategories', 'selectedParentId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
            ],
        ]);

        $category = Category::create($validated);

        AuditLogger::record(
            $request,
            'created',
            'category',
            (int) $category->id,
            (string) $category->name,
            'Created category.',
            [
                'parent_id' => $category->parent_id !== null ? (int) $category->parent_id : null,
            ]
        );

        $this->forgetPublicCatalogCaches();

        return redirect()->route('admin.categories.index');
    }

    public function edit(Category $category)
    {
        $category->load(['parent:id,name', 'children:id,parent_id']);
        $parentCategories = $this->parentCategoryOptions((int) $category->id);

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                function (string $attribute, mixed $value, \Closure $fail) use ($category): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if ((int) $value === (int) $category->id) {
                        $fail('A category cannot be its own parent.');
                        return;
                    }

                    if ($category->children()->exists()) {
                        $fail('A category with subcategories cannot be moved under another parent.');
                    }
                },
            ],
        ]);

        $before = [
            'name' => (string) $category->name,
            'parent_id' => $category->parent_id !== null ? (int) $category->parent_id : null,
        ];
        $category->update($validated);

        AuditLogger::record(
            $request,
            'updated',
            'category',
            (int) $category->id,
            (string) $category->name,
            'Updated category.',
            [
                'before' => $before,
                'after_name' => (string) $category->name,
                'after_parent_id' => $category->parent_id !== null ? (int) $category->parent_id : null,
            ]
        );

        $this->forgetPublicCatalogCaches();

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Request $request, Category $category)
    {
        $name = (string) $category->name;
        $deletedSnapshot = $category->only(['name', 'parent_id']);
        $category->delete();

        AuditLogger::record(
            $request,
            'deleted',
            'category',
            (int) $category->id,
            $name,
            'Deleted category.',
            [
                'deleted_snapshot' => $deletedSnapshot,
            ]
        );

        $this->forgetPublicCatalogCaches();

        return back();
    }

    private function forgetPublicCatalogCaches(): void
    {
        PublicCatalogCache::forgetAll();
    }

    private function parentCategoryOptions(?int $exceptCategoryId = null)
    {
        return Category::query()
            ->whereNull('parent_id')
            ->when(
                $exceptCategoryId !== null,
                fn ($query) => $query->whereKeyNot($exceptCategoryId)
            )
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
