<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('owner can import products using category and sub category spreadsheet template', function () {
    $owner = User::factory()->create([
        'role' => 'owner',
    ]);

    $csv = implode("\n", [
        'CATEGORY,Sub Category,Product Name,Cost per Unit,Price per Unit,Current Qty. in Stock',
        'COMPONENTS,CPU,RYZEN 5 5600,5500,6999,8',
    ]);

    $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

    $this->actingAs($owner)
        ->from(route('admin.products.index'))
        ->post(route('admin.products.import'), [
            'file' => $file,
        ])
        ->assertRedirect(route('admin.products.index', absolute: false))
        ->assertSessionHas('status', 'Import complete: 1 created, 0 updated, 0 skipped.');

    $product = Product::query()->firstOrFail();
    $category = Category::query()->findOrFail($product->category_id);
    $parentCategory = Category::query()->findOrFail((int) $category->parent_id);

    expect((string) $product->name)->toBe('RYZEN 5 5600')
        ->and((float) $product->cost_price)->toBe(5500.0)
        ->and((float) $product->price)->toBe(6999.0)
        ->and((int) $product->stock)->toBe(8)
        ->and((int) $product->initial_stock)->toBe(8)
        ->and((string) $category->name)->toBe('CPU')
        ->and((string) $parentCategory->name)->toBe('COMPONENTS');
});
