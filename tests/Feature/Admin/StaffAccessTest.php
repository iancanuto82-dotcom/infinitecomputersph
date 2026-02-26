<?php

use App\Models\User;

test('owner can create staff account with selected permissions', function () {
    $owner = User::factory()->create([
        'role' => 'owner',
        'email' => 'owner@example.com',
    ]);

    $response = $this->actingAs($owner)->post(route('admin.staff.store'), [
        'name' => 'Staff One',
        'email' => 'staff.one@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'permissions' => ['products.edit', 'sales.view'],
    ]);

    $response->assertRedirect(route('admin.staff.index', absolute: false));

    $staff = User::query()->where('email', 'staff.one@example.com')->first();

    expect($staff)->not->toBeNull();
    expect($staff->role)->toBe('staff');
    expect($staff->admin_permissions)->toContain('products.edit');
    expect($staff->admin_permissions)->toContain('products.view');
    expect($staff->admin_permissions)->toContain('sales.view');
});

test('staff can only access modules they are permitted to view', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'admin_permissions' => ['sales.view'],
    ]);

    $this->actingAs($staff)
        ->get(route('admin.sales'))
        ->assertOk();

    $this->actingAs($staff)
        ->get(route('admin.products.index'))
        ->assertForbidden();

    $this->actingAs($staff)
        ->get(route('admin.staff.index'))
        ->assertForbidden();
});

test('staff login redirects to first allowed admin page', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'admin_permissions' => ['sales.view'],
        'email' => 'staff-login@example.com',
    ]);

    $response = $this->post('/login', [
        'email' => $staff->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($staff);
    $response->assertRedirect(route('admin.sales', absolute: false));
});
