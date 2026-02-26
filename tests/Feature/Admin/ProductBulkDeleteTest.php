<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('owner can bulk delete selected products', function () {
    $owner = User::factory()->create([
        'role' => 'owner',
    ]);

    $category = Category::query()->create([
        'name' => 'Storage',
    ]);

    $first = Product::query()->create([
        'name' => 'SSD 256GB',
        'price' => 1800,
        'cost_price' => 1500,
        'stock' => 5,
        'initial_stock' => 5,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $second = Product::query()->create([
        'name' => 'SSD 512GB',
        'price' => 2800,
        'cost_price' => 2400,
        'stock' => 4,
        'initial_stock' => 4,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $remaining = Product::query()->create([
        'name' => 'SSD 1TB',
        'price' => 4500,
        'cost_price' => 3900,
        'stock' => 3,
        'initial_stock' => 3,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->from(route('admin.products.index'))
        ->delete(route('admin.products.bulk-destroy'), [
            'product_ids' => [$first->id, $second->id],
        ])
        ->assertRedirect(route('admin.products.index', absolute: false))
        ->assertSessionHas('status', '2 product(s) deleted.');

    $this->assertDatabaseMissing('products', ['id' => $first->id]);
    $this->assertDatabaseMissing('products', ['id' => $second->id]);
    $this->assertDatabaseHas('products', ['id' => $remaining->id]);
});

test('staff without products edit permission cannot bulk delete products', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'admin_permissions' => ['products.view'],
    ]);

    $category = Category::query()->create([
        'name' => 'Memory',
    ]);

    $product = Product::query()->create([
        'name' => 'DDR4 16GB',
        'price' => 2100,
        'cost_price' => 1800,
        'stock' => 8,
        'initial_stock' => 8,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->delete(route('admin.products.bulk-destroy'), [
            'product_ids' => [$product->id],
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});
