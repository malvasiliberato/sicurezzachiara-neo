<?php

namespace App\Http\Controllers;

use App\Support\CurrentTenantResolver;
use App\Support\OperationalWorkspaceBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VelzonRoutesController extends Controller
{
    public function dashboard(
        Request $request,
        CurrentTenantResolver $tenantResolver,
        OperationalWorkspaceBuilder $workspaceBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $focus = $request->string('focus')->toString() ?: 'all';

        abort_unless(in_array($focus, [
            'all',
            'urgent',
            'deadlines',
            'follow_up',
            'reviews',
        ], true), 404);

        return Inertia::render('dashboard/index', $workspaceBuilder->buildForTenant($tenant, $focus));
    }

    public function sicurezzachiara_ui_reference(): Response
    {
        return Inertia::render('sicurezzachiara/ui-reference');
    }
}
