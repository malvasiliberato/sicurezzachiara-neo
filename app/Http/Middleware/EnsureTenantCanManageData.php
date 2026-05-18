<?php

namespace App\Http\Middleware;

use App\Support\CurrentTenantResolver;
use App\Support\TenantPermissionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantCanManageData
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $tenant = app(CurrentTenantResolver::class)->resolve($user);

        app(TenantPermissionResolver::class)->ensureCanManageTenantData($user, $tenant);

        return $next($request);
    }
}
