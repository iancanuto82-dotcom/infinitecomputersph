<?php

use App\Models\Category;
use App\Models\Product;

test('public pricelist main category filter includes subcategory products', function () {
    $main = Category::query()->create([
        'name' => 'STORAGE',
        'parent_id' => null,
    ]);

    $sub = Category::query()->create([
        'name' => 'SSD',
        'parent_id' => $main->id,
    ]);

    $other = Category::query()->create([
        'name' => 'MONITOR',
        'parent_id' => null,
    ]);

    Product::query()->create([
        'name' => 'NVME SSD 1TB',
        'price' => 4500,
        'stock' => 5,
        'initial_stock' => 5,
        'category_id' => $sub->id,
        'is_active' => true,
    ]);

    Product::query()->create([
        'name' => '1080p Monitor',
        'price' => 5000,
        'stock' => 3,
        'initial_stock' => 3,
        'category_id' => $other->id,
        'is_active' => true,
    ]);

    $response = $this->get('/pricelist?category='.$main->id);

    $response->assertStatus(200)
        ->assertSee('NVME SSD 1TB')
        ->assertDontSee('1080p Monitor');
});

