<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class TenantPermissionResolver
{
    public function resolveMembership(?User $user, Tenant $tenant): ?TenantMembership
    {
        if ($user === null) {
            return null;
        }

        return $user->membershipForTenant($tenant);
    }

    public function canManageTenantData(?User $user, Tenant $tenant): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->canManageTenantData($tenant);
    }

    /**
     * @throws AuthorizationException
     */
    public function ensureCanManageTenantData(?User $user, Tenant $tenant): void
    {
        if (! $this->canManageTenantData($user, $tenant)) {
            throw new AuthorizationException('Il profilo corrente dispone di accesso consultivo ma non puo\' modificare i dati del tenant.');
        }
    }
}
