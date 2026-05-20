<?php

use App\Models\TenantMembership;
use App\Models\User;
use Laravel\Fortify\Features;

test('registration creates a tenant workspace and owner membership', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => true,
    ]);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->current_tenant_id)->not->toBeNull();
    expect($user->tenantMemberships()->count())->toBe(1);
    expect($user->tenantMemberships()->first()->role)->toBe(TenantMembership::ROLE_OWNER);

    $response->assertRedirect(route('companies.index'));
})->skip(fn () => ! Features::enabled(Features::registration()), 'Registration support is not enabled.');
