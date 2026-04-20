<?php

namespace App\Support;

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CurrentTenantResolver
{
    public function __construct(
        private readonly CreateTenantWorkspace $tenantWorkspace,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function resolve(User $user): Tenant
    {
        $user->loadMissing([
            'currentTenant',
            'tenantMemberships.tenant',
        ]);

        if ($user->tenantMemberships->isEmpty()) {
            return $this->tenantWorkspace->handle($user);
        }

        if (
            $user->currentTenant !== null
            && $user->tenantMemberships->contains('tenant_id', $user->currentTenant->id)
        ) {
            return $user->currentTenant;
        }

        $membership = $user->tenantMemberships
            ->sortByDesc(fn ($membership) => $membership->role === 'owner')
            ->first();

        if ($membership === null || $membership->tenant === null) {
            throw new AuthorizationException('Tenant non disponibile per l\'utente autenticato.');
        }

        $user->forceFill([
            'current_tenant_id' => $membership->tenant_id,
        ])->save();

        return $membership->tenant;
    }
}
