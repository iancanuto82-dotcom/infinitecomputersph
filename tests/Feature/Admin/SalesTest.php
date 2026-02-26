<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;

test('admin can view sales page', function () {
    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'owner',
    ]);

    $response = $this->actingAs($admin)->get('/admin/sales');

    $response->assertStatus(200);
    $response->assertSee('Sales');
});

test('admin can record an in-store sale and deduct stock', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-07 10:00:00'));

    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'owner',
    ]);

    $category = Category::query()->create(['name' => 'CPU']);
    $product = Product::query()->create([
        'name' => 'Ryzen 5',
        'description' => null,
        'price' => 5000,
        'cost_price' => 3500,
        'stock' => 10,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->post('/admin/sales', [
        'sold_at' => '2026-02-07',
        'customer_name' => 'Walk-in',
        'customer_contact' => '',
        'discount' => 100,
        'payment_status' => 'paid',
        'sale_status' => 'completed',
        'deduct_stock' => 1,
        'items' => [
            [
                'product_id' => $product->id,
                'qty' => 2,
                'unit_price' => 5000,
            ],
        ],
    ]);

    $response->assertRedirect();
    expect(Sale::count())->toBe(1);

    $sale = Sale::firstOrFail();
    expect((float) $sale->subtotal)->toBe(10000.0)
        ->and((float) $sale->discount)->toBe(100.0)
        ->and((float) $sale->expenses)->toBe(0.0)
        ->and((float) $sale->grand_total)->toBe(9900.0)
        ->and($sale->payment_status)->toBe('paid')
        ->and((float) $sale->amount_paid)->toBe(9900.0);

    $product->refresh();
    expect((int) $product->stock)->toBe(8);
});

test('admin sale grand total includes 4 percent surcharge for pos payment mode', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-07 11:00:00'));

    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'owner',
    ]);

    $category = Category::query()->create(['name' => 'GPU']);
    $product = Product::query()->create([
        'name' => 'RTX 4060',
        'description' => null,
        'price' => 15000,
        'cost_price' => 9000,
        'stock' => 5,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->post('/admin/sales', [
        'sold_at' => '2026-02-07',
        'customer_name' => 'POS Customer',
        'customer_contact' => '',
        'discount' => 1000,
        'payment_status' => 'paid',
        'payment_mode' => 'bank_card',
        'sale_status' => 'completed',
        'deduct_stock' => 1,
        'items' => [
            [
                'product_id' => $product->id,
                'qty' => 1,
                'unit_price' => 15000,
            ],
        ],
    ]);

    $response->assertRedirect();

    $sale = Sale::query()->latest('id')->firstOrFail();
    expect((float) $sale->subtotal)->toBe(15000.0)
        ->and((float) $sale->discount)->toBe(1000.0)
        ->and((float) $sale->expenses)->toBe(0.0)
        ->and((float) $sale->grand_total)->toBe(14560.0)
        ->and((float) $sale->amount_paid)->toBe(14560.0)
        ->and((string) $sale->payment_mode)->toBe('bank_card');
});

test('sales page filters by month', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-07 10:00:00'));

    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'owner',
    ]);

    Sale::query()->create([
        'created_by' => $admin->id,
        'sold_at' => Carbon::parse('2026-01-15'),
        'customer_name' => 'Test Customer',
        'subtotal' => 100,
        'discount' => 0,
        'grand_total' => 100,
    ]);

    Sale::query()->create([
        'created_by' => $admin->id,
        'sold_at' => Carbon::parse('2026-02-02'),
        'customer_name' => 'Test Customer',
        'subtotal' => 200,
        'discount' => 0,
        'grand_total' => 200,
    ]);

    $jan = $this->actingAs($admin)->get('/admin/sales?year=2026&month=1');
    $jan->assertStatus(200);
    $jan->assertSee('January 2026');
    $jan->assertSee('100.00');

    $feb = $this->actingAs($admin)->get('/admin/sales?year=2026&month=2');
    $feb->assertStatus(200);
    $feb->assertSee('February 2026');
    $feb->assertSee('200.00');
});

test('outstanding excludes sales already marked paid', function () {
    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin-outstanding@example.com',
        'role' => 'owner',
    ]);

    Sale::query()->create([
        'created_by' => $admin->id,
        'sold_at' => Carbon::parse('2026-02-07'),
        'customer_name' => 'Paid Legacy',
        'subtotal' => 100,
        'discount' => 0,
        'grand_total' => 100,
        'payment_status' => 'paid',
        'amount_paid' => 0,
    ]);

    Sale::query()->create([
        'created_by' => $admin->id,
        'sold_at' => Carbon::parse('2026-02-07'),
        'customer_name' => 'Unpaid',
        'subtotal' => 50,
        'discount' => 0,
        'grand_total' => 50,
        'payment_status' => 'unpaid',
        'amount_paid' => 0,
    ]);

    $response = $this->actingAs($admin)->get('/admin/sales');
    $response->assertStatus(200);
    $response->assertViewHas('outstanding', fn ($value) => abs((float) $value - 50.0) < 0.0001);
});

test('sales page total expenses includes logged expenses', function () {
    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin-expense-rollup@example.com',
        'role' => 'owner',
    ]);

    Expense::query()->create([
        'created_by' => $admin->id,
        'spent_at' => Carbon::parse('2026-02-26 09:00:00'),
        'title' => 'Internet bill',
        'category' => 'Utilities',
        'amount' => 1234.56,
    ]);

    $response = $this->actingAs($admin)->get('/admin/sales');
    $response->assertStatus(200);
    $response->assertSee('1,234.56');
});
