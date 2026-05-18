<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SystemAdminSeeder;

function withBootstrapEnv(array $values, callable $callback): void
{
    $keys = [
        'SC_SYSTEM_ADMIN_EMAIL',
        'SC_SYSTEM_ADMIN_NAME',
        'SC_SYSTEM_ADMIN_PASSWORD',
    ];

    $snapshot = [];

    foreach ($keys as $key) {
        $snapshot[$key] = getenv($key);
    }

    try {
        foreach ($keys as $key) {
            if (array_key_exists($key, $values)) {
                putenv($key.'='.$values[$key]);
                $_ENV[$key] = $values[$key];
                $_SERVER[$key] = $values[$key];
            } else {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            }
        }

        $callback();
    } finally {
        foreach ($keys as $key) {
            $original = $snapshot[$key];

            if ($original === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);

                continue;
            }

            putenv($key.'='.$original);
            $_ENV[$key] = $original;
            $_SERVER[$key] = $original;
        }
    }
}

test('system admin seeder skips when dedicated env variables are missing', function () {
    withBootstrapEnv([], function () {
        $this->seed(SystemAdminSeeder::class);

        expect(User::query()->where('is_system_admin', true)->count())->toBe(0);
    });
});

test('system admin seeder can create an admin only when dedicated env variables are present', function () {
    withBootstrapEnv([
        'SC_SYSTEM_ADMIN_EMAIL' => 'admin.seed@test.local',
        'SC_SYSTEM_ADMIN_NAME' => 'Admin Seed',
        'SC_SYSTEM_ADMIN_PASSWORD' => 'temp-password',
    ], function () {
        $this->seed(SystemAdminSeeder::class);

        $user = User::query()->where('email', 'admin.seed@test.local')->firstOrFail();

        expect($user->is_system_admin)->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull()
            ->and($user->current_tenant_id)->not->toBeNull()
            ->and($user->tenantMemberships()->count())->toBe(1);
    });
});

test('database seeder keeps system admin bootstrap explicit', function () {
    withBootstrapEnv([], function () {
        $this->seed(DatabaseSeeder::class);

        expect(User::query()->where('is_system_admin', true)->count())->toBe(0);
    });
});

test('ensure system admin command creates or promotes an admin explicitly', function () {
    $this->artisan('sicurezzachiara:ensure-system-admin', [
        'email' => 'owner.command@test.local',
        '--name' => 'Owner Command',
        '--password' => 'command-password',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'owner.command@test.local')->firstOrFail();

    expect($user->is_system_admin)->toBeTrue()
        ->and($user->current_tenant_id)->not->toBeNull()
        ->and($user->tenantMemberships()->count())->toBe(1);
});
