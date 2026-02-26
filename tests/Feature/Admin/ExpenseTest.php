<?php

use App\Models\Expense;
use App\Models\User;

test('admin can view expenses page', function () {
    $admin = User::factory()->create([
        'role' => 'owner',
        'email' => 'owner-expenses@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.expenses'))
        ->assertOk()
        ->assertSee('Expenses');
});

test('admin can record an expense', function () {
    $admin = User::factory()->create([
        'role' => 'owner',
        'email' => 'owner-expenses-write@example.com',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.expenses.store'), [
            'spent_at' => '2026-02-26T10:00',
            'title' => 'Electric Bill',
            'category' => 'Utilities',
            'amount' => '2500.50',
            'notes' => 'February power bill',
        ])
        ->assertRedirect(route('admin.expenses', absolute: false));

    $expense = Expense::query()->firstOrFail();
    expect((string) $expense->title)->toBe('Electric Bill')
        ->and((string) $expense->category)->toBe('Utilities')
        ->and((float) $expense->amount)->toBe(2500.5);
});

test('staff with sales view permission can view expenses page', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'admin_permissions' => ['sales.view'],
    ]);

    $this->actingAs($staff)
        ->get(route('admin.expenses'))
        ->assertOk()
        ->assertSee('Expenses');
});

test('staff without sales edit permission cannot record expenses', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'admin_permissions' => ['sales.view'],
    ]);

    $this->actingAs($staff)
        ->post(route('admin.expenses.store'), [
            'spent_at' => '2026-02-26T11:00',
            'title' => 'Internet',
            'amount' => '1200',
        ])
        ->assertForbidden();
});
