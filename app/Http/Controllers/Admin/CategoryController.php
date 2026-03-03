<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\AuditLogger;
use App\Support\PublicCatalogCache;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = Category::create($validated);

        AuditLogger::record(
            $request,
            'created',
            'category',
            (int) $category->id,
            (string) $category->name,
            'Created category.'
        );

        $this->forgetPublicCatalogCaches();

        return redirect()->route('admin.categories.index');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $before = (string) $category->name;
        $category->update($validated);

        AuditLogger::record(
            $request,
            'updated',
            'category',
            (int) $category->id,
            (string) $category->name,
            'Updated category.',
            [
                'before_name' => $before,
                'after_name' => (string) $category->name,
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
}
