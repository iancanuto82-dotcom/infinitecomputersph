<?php

use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;

test('admin can update sale payment status until it is paid', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-07 10:00:00'));

    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'owner',
    ]);

    $sale = Sale::query()->create([
        'created_by' => $admin->id,
        'sold_at' => now(),
        'customer_name' => 'Test Customer',
        'subtotal' => 100,
        'discount' => 0,
        'grand_total' => 100,
        'payment_status' => 'unpaid',
        'amount_paid' => 0,
    ]);

    $this->actingAs($admin)->patch(route('admin.sales.payment', $sale), [
        'payment_status' => 'partial',
        'amount_paid' => 40,
    ])->assertRedirect();

    $sale->refresh();
    expect($sale->payment_status)->toBe('partial')
        ->and((float) $sale->amount_paid)->toBe(40.0);

    $this->actingAs($admin)->patch(route('admin.sales.payment', $sale), [
        'payment_status' => 'paid',
        'amount_paid' => 0,
    ])->assertRedirect();

    $sale->refresh();
    expect($sale->payment_status)->toBe('paid')
        ->and((float) $sale->amount_paid)->toBe(100.0);

    $this->actingAs($admin)->patch(route('admin.sales.payment', $sale), [
        'payment_status' => 'unpaid',
        'amount_paid' => 0,
    ])->assertRedirect();

    $sale->refresh();
    expect($sale->payment_status)->toBe('paid');
});

test('partial payment requires amount paid between 0 and grand total', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-07 10:00:00'));

    $admin = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'owner',
    ]);

    $sale = Sale::query()->create([
        'created_by' => $admin->id,
        'sold_at' => now(),
        'customer_name' => 'Test Customer',
        'subtotal' => 200,
        'discount' => 0,
        'grand_total' => 200,
        'payment_status' => 'unpaid',
        'amount_paid' => 0,
    ]);

    $this->actingAs($admin)->from('/admin/sales')->patch(route('admin.sales.payment', $sale), [
        'payment_status' => 'partial',
        'amount_paid' => 0,
    ])->assertRedirect('/admin/sales')->assertSessionHasErrors('amount_paid');

    $this->actingAs($admin)->from('/admin/sales')->patch(route('admin.sales.payment', $sale), [
        'payment_status' => 'partial',
        'amount_paid' => 200,
    ])->assertRedirect('/admin/sales')->assertSessionHasErrors('amount_paid');
});
