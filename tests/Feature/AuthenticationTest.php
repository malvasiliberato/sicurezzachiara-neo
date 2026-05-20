<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;

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
    $response->assertRedirect(RouteServiceProvider::HOME);
});

test('users can choose dashboard as login landing page', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'ui_home_page' => 'dashboard',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
});

test('root intended URL does not override chosen login landing page', function () {
    $user = User::factory()->create();

    $this->withSession(['url.intended' => url('/')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'ui_home_page' => 'method',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('sicurezzachiara.method'));
});

test('users can choose method as login landing page', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'ui_home_page' => 'method',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('sicurezzachiara.method'));
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});
