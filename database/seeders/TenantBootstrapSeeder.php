<?php

namespace Database\Seeders;

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantBootstrapSeeder extends Seeder
{
    public function run(CreateTenantWorkspace $tenantWorkspace): void
    {
        User::query()
            ->whereDoesntHave('tenantMemberships')
            ->each(fn (User $user) => $tenantWorkspace->handle($user));
    }
}
