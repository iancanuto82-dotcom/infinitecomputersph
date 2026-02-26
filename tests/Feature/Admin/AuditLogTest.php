<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;

test('owner can view history log page', function () {
    $owner = User::factory()->create([
        'role' => 'owner',
    ]);

    AuditLog::query()->create([
        'user_id' => $owner->id,
        'action' => 'created',
        'target_type' => 'category',
        'target_id' => 1,
        'target_name' => 'CPU',
        'description' => 'Created category.',
        'created_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('admin.audit.index'))
        ->assertOk()
        ->assertSee('History Log')
        ->assertSee('Created category.');
});

test('product creation is recorded in audit logs', function () {
    $owner = User::factory()->create([
        'role' => 'owner',
    ]);

    $category = Category::query()->create([
        'name' => 'Storage',
    ]);

    $this->actingAs($owner)->post(route('admin.products.store'), [
        'name' => 'SSD 1TB',
        'price' => 4500,
        'cost_price' => 3600,
        'stock' => 10,
        'initial_stock' => 10,
        'category_id' => $category->id,
    ])->assertRedirect(route('admin.products.index', absolute: false));

    $product = Product::query()->where('name', 'SSD 1TB')->firstOrFail();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $owner->id,
        'action' => 'created',
        'target_type' => 'product',
        'target_id' => $product->id,
        'target_name' => 'SSD 1TB',
    ]);
});

test('sale creation is recorded in audit logs', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-07 10:00:00'));

    $owner = User::factory()->create([
        'role' => 'owner',
    ]);

    $category = Category::query()->create(['name' => 'CPU']);
    $product = Product::query()->create([
        'name' => 'Ryzen 7',
        'price' => 12000,
        'cost_price' => 9000,
        'stock' => 6,
        'initial_stock' => 6,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $this->actingAs($owner)->post(route('admin.sales.store'), [
        'sold_at' => '2026-02-07',
        'customer_name' => 'Walk-in',
        'discount' => 0,
        'payment_status' => 'paid',
        'sale_status' => 'completed',
        'deduct_stock' => 1,
        'items' => [
            [
                'product_id' => $product->id,
                'qty' => 1,
                'unit_price' => 12000,
            ],
        ],
    ])->assertRedirect(route('admin.sales', absolute: false));

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $owner->id,
        'action' => 'created',
        'target_type' => 'sale',
    ]);
});
