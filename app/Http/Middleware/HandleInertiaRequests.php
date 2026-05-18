<?php

namespace App\Http\Middleware;

use App\Support\CurrentTenantResolver;
use App\Support\TenantPermissionResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        $tenantContext = null;

        if ($request->user() !== null) {
            $tenant = app(CurrentTenantResolver::class)->resolve($request->user());
            $membership = app(TenantPermissionResolver::class)->resolveMembership($request->user(), $tenant);

            $tenantContext = [
                'current' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                ],
                'membership' => [
                    'role' => $membership?->role,
                    'is_system_admin' => $request->user()->isSystemAdmin(),
                ],
                'permissions' => [
                    'can_manage_data' => app(TenantPermissionResolver::class)->canManageTenantData($request->user(), $tenant),
                ],
            ];
        }

        return array_merge(parent::share($request), [
            'tenantContext' => $tenantContext,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
