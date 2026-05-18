<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreWorkerWorkplaceExposureRequest;
use App\Http\Requests\UpdateWorkerWorkplaceExposureRequest;
use App\Models\Tenant;
use App\Models\Worker;
use App\Models\WorkerWorkplaceExposure;
use App\Models\Workplace;
use App\Support\AuditLogger;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkerWorkplaceExposureController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class);
    }

    public function store(
        StoreWorkerWorkplaceExposureRequest $request,
        Worker $worker,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $validated = $request->validated();
        $workplace = $this->workplaceForTenant($tenant, Workplace::query()->findOrFail($validated['workplace_id']));

        $this->ensureWorkplaceBelongsToWorkerCompany($worker, $workplace);
        $this->ensureAssignmentIsUnique($worker, $workplace);

        if ($validated['is_primary'] ?? false) {
            $worker->workplaceExposures()->update(['is_primary' => false]);
        }

        $exposure = $worker->workplaceExposures()->create($validated);
        $exposure->loadMissing('workplace.workplaceType');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.workplace_exposure.created',
            $exposure,
            'Associato luogo '.$exposure->workplace?->name.' a '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'workplace_id' => $exposure->workplace_id,
                'workplace_name' => $exposure->workplace?->name,
                'workplace_type_name' => $exposure->workplace?->workplaceType?->name,
                'is_primary' => $exposure->is_primary,
            ],
        );

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Luogo associato al lavoratore.');
    }

    public function update(
        UpdateWorkerWorkplaceExposureRequest $request,
        Worker $worker,
        WorkerWorkplaceExposure $workerWorkplaceExposure,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $workerWorkplaceExposure = $this->exposureForWorker($worker, $workerWorkplaceExposure);
        $validated = $request->validated();
        $workplace = $this->workplaceForTenant($tenant, Workplace::query()->findOrFail($validated['workplace_id']));

        $this->ensureWorkplaceBelongsToWorkerCompany($worker, $workplace);
        $this->ensureAssignmentIsUnique($worker, $workplace, $workerWorkplaceExposure->id);

        if ($validated['is_primary'] ?? false) {
            $worker->workplaceExposures()
                ->whereKeyNot($workerWorkplaceExposure->id)
                ->update(['is_primary' => false]);
        }

        $workerWorkplaceExposure->update($validated);
        $workerWorkplaceExposure->loadMissing('workplace.workplaceType');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.workplace_exposure.updated',
            $workerWorkplaceExposure,
            'Aggiornata associazione luogo per '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'workplace_id' => $workerWorkplaceExposure->workplace_id,
                'workplace_name' => $workerWorkplaceExposure->workplace?->name,
                'workplace_type_name' => $workerWorkplaceExposure->workplace?->workplaceType?->name,
                'is_primary' => $workerWorkplaceExposure->is_primary,
            ],
        );

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Associazione luogo aggiornata.');
    }

    public function destroy(
        Request $request,
        Worker $worker,
        WorkerWorkplaceExposure $workerWorkplaceExposure,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $workerWorkplaceExposure = $this->exposureForWorker($worker, $workerWorkplaceExposure);
        $workerWorkplaceExposure->loadMissing('workplace.workplaceType');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.workplace_exposure.removed',
            $workerWorkplaceExposure,
            'Rimossa associazione luogo da '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'workplace_id' => $workerWorkplaceExposure->workplace_id,
                'workplace_name' => $workerWorkplaceExposure->workplace?->name,
                'workplace_type_name' => $workerWorkplaceExposure->workplace?->workplaceType?->name,
                'is_primary' => $workerWorkplaceExposure->is_primary,
            ],
        );
        $workerWorkplaceExposure->delete();

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Associazione luogo rimossa.');
    }

    private function workerForTenant(Tenant $tenant, Worker $worker): Worker
    {
        $worker->loadMissing('company');
        abort_unless($worker->company !== null && $worker->company->tenant_id === $tenant->id, 404);

        return $worker;
    }

    private function workplaceForTenant(Tenant $tenant, Workplace $workplace): Workplace
    {
        $workplace->loadMissing('site.company');
        abort_unless($workplace->site !== null && $workplace->site->company?->tenant_id === $tenant->id, 404);

        return $workplace;
    }

    private function exposureForWorker(Worker $worker, WorkerWorkplaceExposure $workerWorkplaceExposure): WorkerWorkplaceExposure
    {
        abort_unless($workerWorkplaceExposure->worker_id === $worker->id, 404);

        return $workerWorkplaceExposure;
    }

    private function ensureWorkplaceBelongsToWorkerCompany(Worker $worker, Workplace $workplace): void
    {
        if ($worker->company_id !== $workplace->site->company_id) {
            throw ValidationException::withMessages([
                'workplace_id' => 'Il luogo deve appartenere alla stessa azienda del lavoratore.',
            ]);
        }
    }

    private function ensureAssignmentIsUnique(Worker $worker, Workplace $workplace, ?int $ignoreId = null): void
    {
        $query = $worker->workplaceExposures()->where('workplace_id', $workplace->id);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'workplace_id' => 'Questo luogo e\' gia\' associato al lavoratore.',
            ]);
        }
    }
}
