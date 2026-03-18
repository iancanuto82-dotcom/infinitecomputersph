<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExpenseController as AdminExpenseController;
use App\Http\Controllers\Admin\ReplacementWarrantyController as AdminReplacementWarrantyController;
use App\Http\Controllers\Admin\WebsiteContentController as AdminWebsiteContentController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\PcBuilderController as AdminPcBuilderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SalesController as AdminSalesController;
use App\Http\Controllers\Admin\StaffAccountController as AdminStaffAccountController;
use App\Http\Controllers\ProductController as PublicProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicProductController::class, 'landing'])->name('home');
Route::get('/pricelist', [PublicProductController::class, 'index'])->name('pricelist');
Route::get('/featured-builds', [PublicProductController::class, 'featuredBuilds'])->name('featured-builds');
Route::get('/pricelist/{product}', [PublicProductController::class, 'show'])->name('pricelist.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)
        ->middleware('admin.permission:dashboard.view')
        ->name('dashboard');
    Route::patch('/dashboard/theme', [AdminDashboardController::class, 'updateThemePreference'])
        ->middleware('admin.permission:dashboard.view')
        ->name('dashboard.theme');

    Route::middleware('admin.permission:sales.view')->group(function () {
        Route::get('/sales', [AdminSalesController::class, 'index'])->name('sales');
        Route::get('/expenses', [AdminExpenseController::class, 'index'])->name('expenses');
        Route::get('/replacements', [AdminReplacementWarrantyController::class, 'index'])->name('replacements.index');
        Route::get('/replacements/inventory', [AdminReplacementWarrantyController::class, 'inventory'])->name('replacements.inventory');
    });

    Route::middleware('admin.permission:sales.edit')->group(function () {
        Route::get('/sales/create', [AdminSalesController::class, 'create'])->name('sales.create');
        Route::post('/sales', [AdminSalesController::class, 'store'])->name('sales.store');
        Route::post('/expenses', [AdminExpenseController::class, 'store'])->name('expenses.store');
        Route::post('/replacements', [AdminReplacementWarrantyController::class, 'store'])->name('replacements.store');
        Route::delete('/expenses/{expense}', [AdminExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::patch('/sales/{sale}/payment', [AdminSalesController::class, 'updatePayment'])->name('sales.payment');
        Route::patch('/sales/{sale}/status', [AdminSalesController::class, 'updateStatus'])->name('sales.status');
        Route::post('/sales/{sale}/payments', [AdminSalesController::class, 'addPayment'])->name('sales.payments.store');
        Route::post('/sales/{sale}/refund', [AdminSalesController::class, 'refund'])->name('sales.refund');
        Route::post('/sales/{sale}/cancel', [AdminSalesController::class, 'cancel'])->name('sales.cancel');
        Route::post('/pc-builder/quotations/{quotation}/add-to-sales', [AdminSalesController::class, 'storeFromQuotation'])->name('pc-builder.quotations.add-to-sales');
    });

    Route::middleware('admin.permission:sales.view')->group(function () {
        Route::get('/sales/{sale}/receipt', [AdminSalesController::class, 'downloadReceipt'])->name('sales.receipt');
        Route::get('/sales/{sale}', [AdminSalesController::class, 'show'])->name('sales.show');
    });

    Route::middleware('admin.permission:pc_builder.view')->group(function () {
        Route::get('/pc-builder', AdminPcBuilderController::class)->name('pc-builder');
        Route::get('/pc-builder/history', [AdminPcBuilderController::class, 'history'])->name('pc-builder.history');
        Route::get('/pc-builder/quotations/{quotation}/preview', [AdminPcBuilderController::class, 'preview'])->name('pc-builder.quotations.preview');
        Route::get('/pc-builder/quotations/{quotation}/download-pdf', [AdminPcBuilderController::class, 'downloadPdf'])->name('pc-builder.quotations.download-pdf');
    });

    Route::middleware('admin.permission:pc_builder.edit')->group(function () {
        Route::post('/pc-builder/preview-pdf', [AdminPcBuilderController::class, 'downloadPreviewPdf'])->name('pc-builder.preview-pdf');
        Route::post('/pc-builder/quotations', [AdminPcBuilderController::class, 'store'])->name('pc-builder.quotations.store');
    });

    Route::middleware('admin.permission:products.edit')->group(function () {
        Route::get('products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::get('products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::post('products/import', [AdminProductController::class, 'import'])->name('products.import');
        Route::post('products/import/revert', [AdminProductController::class, 'revertImport'])->name('products.import.revert');
        Route::post('products', [AdminProductController::class, 'store'])->name('products.store');
        Route::delete('products/bulk-destroy', [AdminProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
        Route::match(['put', 'patch'], 'products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    });
    Route::middleware('admin.permission:products.view')->group(function () {
        Route::get('products', [AdminProductController::class, 'index'])->name('products.index');
    });

    Route::middleware('admin.permission:categories.edit')->group(function () {
        Route::get('categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::get('categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::match(['put', 'patch'], 'categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    });
    Route::middleware('admin.permission:categories.view')->group(function () {
        Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    });

    Route::middleware('admin.permission:content.view')->group(function () {
        Route::get('/content', [AdminWebsiteContentController::class, 'edit'])->name('content.edit');
    });
    Route::middleware('admin.permission:content.edit')->group(function () {
        Route::put('/content', [AdminWebsiteContentController::class, 'update'])->name('content.update');
    });

    Route::middleware('admin.permission:users.manage')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [AdminStaffAccountController::class, 'index'])->name('index');
        Route::get('/create', [AdminStaffAccountController::class, 'create'])->name('create');
        Route::post('/', [AdminStaffAccountController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [AdminStaffAccountController::class, 'edit'])->name('edit');
        Route::put('/{user}', [AdminStaffAccountController::class, 'update'])->name('update');
    });

    Route::middleware('admin.permission:audit.view')->group(function () {
        Route::get('/history', [AdminAuditLogController::class, 'index'])->name('audit.index');
        Route::post('/history/{auditLog}/revert', [AdminAuditLogController::class, 'revert'])->name('audit.revert');
    });
});

Route::get('/admin', function () {
    $routeName = \App\Support\AdminAccess::preferredAdminRouteName(request()->user());

    if (! $routeName) {
        abort(403);
    }

    return redirect()->route($routeName);
})->middleware('auth');

require __DIR__.'/auth.php';
