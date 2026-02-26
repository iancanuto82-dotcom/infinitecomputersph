<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('users can authenticate using their username', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->name,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('admin users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'role' => 'owner',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('admin login screen redirects to the main login', function () {
    $response = $this->get('/admin/login');

    $response->assertRedirect(route('login', absolute: false));
});

test('admin users can authenticate using the admin login', function () {
    $user = User::factory()->create([
        'name' => 'Owner',
        'email' => 'owner-admin@example.com',
        'role' => 'owner',
    ]);

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('non admin users can authenticate using the admin login', function () {
    $user = User::factory()->create([
        'name' => 'customer',
        'email' => 'customer@example.com',
    ]);

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home', absolute: false));
});

test('non admin users are not redirected back to admin pages after login', function () {
    $user = User::factory()->create();

    $this->get('/admin/dashboard')->assertRedirect(route('login', absolute: false));

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
