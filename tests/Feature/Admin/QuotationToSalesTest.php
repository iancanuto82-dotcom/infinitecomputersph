<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;

test('admin can convert a quotation to a sale and deduct stock', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-07 10:00:00'));

    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'owner',
    ]);

    $category = Category::query()->create(['name' => 'GPU']);
    $product = Product::query()->create([
        'name' => 'RTX 4060',
        'description' => null,
        'price' => 18000,
        'cost_price' => 12000,
        'stock' => 5,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $quotation = Quotation::query()->create([
        'created_by' => $admin->id,
        'quotation_name' => 'Customer Quotation',
        'customer_name' => 'John Doe',
        'customer_contact' => '09123456789',
        'notes' => 'Approved.',
        'subtotal' => 18000,
        'labor_fee' => 0,
        'discount_type' => 'amount',
        'discount' => 0,
        'discount_amount' => 0,
        'grand_total' => 18000,
        'items' => [
            [
                'section_key' => 'graphics',
                'section' => 'Graphics',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'qty' => 1,
                'unit_price' => 18000,
                'line_total' => 18000,
            ],
        ],
    ]);

    $response = $this->actingAs($admin)->post(route('admin.pc-builder.quotations.add-to-sales', $quotation), [
        'deduct_stock' => 1,
    ]);

    $response->assertRedirect();
    expect(Sale::count())->toBe(1);
    expect((float) Sale::query()->firstOrFail()->expenses)->toBe(0.0);

    $product->refresh();
    expect((int) $product->stock)->toBe(4);

    $quotation->refresh();
    expect($quotation->sale_id)->not->toBeNull();
});

test('converting the same quotation twice does not create duplicate sales', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-07 10:00:00'));

    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'owner',
    ]);

    $category = Category::query()->create(['name' => 'RAM']);
    $product = Product::query()->create([
        'name' => '16GB DDR4',
        'description' => null,
        'price' => 2000,
        'stock' => 10,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $quotation = Quotation::query()->create([
        'created_by' => $admin->id,
        'customer_name' => 'Walk-in',
        'subtotal' => 2000,
        'labor_fee' => 0,
        'discount_type' => 'amount',
        'discount' => 0,
        'discount_amount' => 0,
        'grand_total' => 2000,
        'items' => [
            [
                'section_key' => 'ram',
                'section' => 'RAM',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'qty' => 1,
                'unit_price' => 2000,
                'line_total' => 2000,
            ],
        ],
    ]);

    $this->actingAs($admin)->post(route('admin.pc-builder.quotations.add-to-sales', $quotation), [
        'deduct_stock' => 1,
    ])->assertRedirect();

    $quotation->refresh();
    $firstSaleId = $quotation->sale_id;

    $this->actingAs($admin)->post(route('admin.pc-builder.quotations.add-to-sales', $quotation), [
        'deduct_stock' => 1,
    ])->assertRedirect();

    expect(Sale::count())->toBe(1);
    $quotation->refresh();
    expect($quotation->sale_id)->toBe($firstSaleId);
});
