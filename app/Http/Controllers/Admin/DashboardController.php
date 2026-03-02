<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function updateThemePreference(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_preference' => ['required', Rule::in(['light', 'dark'])],
        ]);

        $request->user()->forceFill([
            'theme_preference' => (string) $validated['theme_preference'],
        ])->save();

        return back()->with('status', 'Display mode preference updated.');
    }

    public function __invoke(): View
    {
        $lowStockThreshold = 5;

        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $lowStockProducts = Product::where('stock', '<=', $lowStockThreshold)->count();
        $categoriesCount = Category::count();
        $totalStockUnits = (int) Product::sum('stock');

        $inventoryProducts = Product::with('category')
            ->orderByDesc('updated_at')
            ->take(8)
            ->get();

        $lowStockItems = Product::with('category')
            ->where('stock', '<=', $lowStockThreshold)
            ->orderBy('stock')
            ->orderBy('name')
            ->take(6)
            ->get();

        return view('admin.dashboard', [
            'lowStockThreshold' => $lowStockThreshold,
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'lowStockProducts' => $lowStockProducts,
            'categoriesCount' => $categoriesCount,
            'totalStockUnits' => $totalStockUnits,
            'inventoryProducts' => $inventoryProducts,
            'lowStockItems' => $lowStockItems,
        ]);
    }
}
