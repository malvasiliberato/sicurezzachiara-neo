<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTenantWorkspace
{
    public function handle(User $user, ?string $tenantName = null): Tenant
    {
        return DB::transaction(function () use ($user, $tenantName) {
            $existingMembership = $user->tenantMemberships()->with('tenant')->first();

            if ($existingMembership?->tenant !== null) {
                if ($user->current_tenant_id !== $existingMembership->tenant_id) {
                    $user->forceFill([
                        'current_tenant_id' => $existingMembership->tenant_id,
                    ])->save();
                }

                return $existingMembership->tenant;
            }

            $name = $tenantName ?: sprintf('Workspace %s', $user->name);

            $tenant = Tenant::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'owner_user_id' => $user->id,
            ]);

            TenantMembership::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => TenantMembership::ROLE_OWNER,
                'joined_at' => now(),
            ]);

            $user->forceFill([
                'current_tenant_id' => $tenant->id,
            ])->save();

            return $tenant;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : Str::lower(Str::random(8));
        $counter = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = sprintf('%s-%d', $baseSlug !== '' ? $baseSlug : 'workspace', $counter);
            $counter++;
        }

        return $slug;
    }
}
