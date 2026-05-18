<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreWorkerJobRoleAssignmentRequest;
use App\Http\Requests\UpdateWorkerJobRoleAssignmentRequest;
use App\Models\JobRole;
use App\Models\Tenant;
use App\Models\Worker;
use App\Models\WorkerJobRoleAssignment;
use App\Support\AuditLogger;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkerJobRoleAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class);
    }

    public function store(
        StoreWorkerJobRoleAssignmentRequest $request,
        Worker $worker,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $validated = $request->validated();
        $jobRole = $this->jobRoleForTenant($tenant, JobRole::query()->findOrFail($validated['job_role_id']));

        $existingAssignment = $worker->jobRoleAssignments()
            ->where('job_role_id', $jobRole->id)
            ->first();

        if ($existingAssignment !== null) {
            throw ValidationException::withMessages([
                'job_role_id' => 'La mansione selezionata e\' gia\' assegnata a questo lavoratore.',
            ]);
        }

        if ($validated['is_primary']) {
            $worker->jobRoleAssignments()->update(['is_primary' => false]);
        }

        $assignment = $worker->jobRoleAssignments()->create($validated);
        $assignment->loadMissing('jobRole');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.job_role_assignment.created',
            $assignment,
            'Assegnata mansione '.$assignment->jobRole?->name.' a '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'job_role_id' => $assignment->job_role_id,
                'job_role_name' => $assignment->jobRole?->name,
                'is_primary' => $assignment->is_primary,
            ],
        );

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Mansione assegnata correttamente al lavoratore.');
    }

    public function update(
        UpdateWorkerJobRoleAssignmentRequest $request,
        Worker $worker,
        WorkerJobRoleAssignment $assignment,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $assignment = $this->assignmentForWorker($worker, $assignment);
        $validated = $request->validated();
        $jobRole = $this->jobRoleForTenant($tenant, JobRole::query()->findOrFail($validated['job_role_id']));

        $duplicate = $worker->jobRoleAssignments()
            ->where('job_role_id', $jobRole->id)
            ->whereKeyNot($assignment->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'job_role_id' => 'La mansione selezionata e\' gia\' assegnata a questo lavoratore.',
            ]);
        }

        if ($validated['is_primary']) {
            $worker->jobRoleAssignments()
                ->whereKeyNot($assignment->id)
                ->update(['is_primary' => false]);
        }

        $assignment->update($validated);
        $assignment->loadMissing('jobRole');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.job_role_assignment.updated',
            $assignment,
            'Aggiornata assegnazione mansione per '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'job_role_id' => $assignment->job_role_id,
                'job_role_name' => $assignment->jobRole?->name,
                'is_primary' => $assignment->is_primary,
            ],
        );

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Assegnazione mansione aggiornata correttamente.');
    }

    public function destroy(
        Request $request,
        Worker $worker,
        WorkerJobRoleAssignment $assignment,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $assignment = $this->assignmentForWorker($worker, $assignment);
        $assignment->loadMissing('jobRole');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.job_role_assignment.removed',
            $assignment,
            'Rimossa mansione '.$assignment->jobRole?->name.' da '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'job_role_id' => $assignment->job_role_id,
                'job_role_name' => $assignment->jobRole?->name,
                'is_primary' => $assignment->is_primary,
            ],
        );

        $assignment->delete();

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Assegnazione mansione rimossa correttamente.');
    }

    private function workerForTenant(Tenant $tenant, Worker $worker): Worker
    {
        $worker->loadMissing('company');

        abort_unless($worker->company !== null && $worker->company->tenant_id === $tenant->id, 404);

        return $worker;
    }

    private function assignmentForWorker(Worker $worker, WorkerJobRoleAssignment $assignment): WorkerJobRoleAssignment
    {
        abort_unless($assignment->worker_id === $worker->id, 404);

        return $assignment;
    }

    private function jobRoleForTenant(Tenant $tenant, JobRole $jobRole): JobRole
    {
        $allowed = $jobRole->source === JobRole::SOURCE_CORE || $jobRole->tenant_id === $tenant->id;
        abort_unless($allowed, 404);

        return $jobRole;
    }
}
