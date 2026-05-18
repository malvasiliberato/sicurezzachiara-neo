<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreWorkerEquipmentExposureRequest;
use App\Http\Requests\UpdateWorkerEquipmentExposureRequest;
use App\Models\EquipmentAsset;
use App\Models\Tenant;
use App\Models\Worker;
use App\Models\WorkerEquipmentExposure;
use App\Support\AuditLogger;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkerEquipmentExposureController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class);
    }

    public function store(
        StoreWorkerEquipmentExposureRequest $request,
        Worker $worker,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $validated = $request->validated();
        $equipmentAsset = $this->assetForTenant($tenant, EquipmentAsset::query()->findOrFail($validated['equipment_asset_id']));

        $this->ensureAssetBelongsToWorkerCompany($worker, $equipmentAsset);
        $this->ensureAssignmentIsUnique($worker, $equipmentAsset);

        if ($validated['is_primary'] ?? false) {
            $worker->equipmentExposures()->update(['is_primary' => false]);
        }

        $exposure = $worker->equipmentExposures()->create($validated);
        $exposure->loadMissing('equipmentAsset.equipmentType');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.equipment_exposure.created',
            $exposure,
            'Associato macchinario '.$exposure->equipmentAsset?->name.' a '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'equipment_asset_id' => $exposure->equipment_asset_id,
                'equipment_asset_name' => $exposure->equipmentAsset?->name,
                'equipment_type_name' => $exposure->equipmentAsset?->equipmentType?->name,
                'is_primary' => $exposure->is_primary,
            ],
        );

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Macchinario associato al lavoratore.');
    }

    public function update(
        UpdateWorkerEquipmentExposureRequest $request,
        Worker $worker,
        WorkerEquipmentExposure $workerEquipmentExposure,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $workerEquipmentExposure = $this->exposureForWorker($worker, $workerEquipmentExposure);
        $validated = $request->validated();
        $equipmentAsset = $this->assetForTenant($tenant, EquipmentAsset::query()->findOrFail($validated['equipment_asset_id']));

        $this->ensureAssetBelongsToWorkerCompany($worker, $equipmentAsset);
        $this->ensureAssignmentIsUnique($worker, $equipmentAsset, $workerEquipmentExposure->id);

        if ($validated['is_primary'] ?? false) {
            $worker->equipmentExposures()
                ->whereKeyNot($workerEquipmentExposure->id)
                ->update(['is_primary' => false]);
        }

        $workerEquipmentExposure->update($validated);
        $workerEquipmentExposure->loadMissing('equipmentAsset.equipmentType');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.equipment_exposure.updated',
            $workerEquipmentExposure,
            'Aggiornata associazione macchinario per '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'equipment_asset_id' => $workerEquipmentExposure->equipment_asset_id,
                'equipment_asset_name' => $workerEquipmentExposure->equipmentAsset?->name,
                'equipment_type_name' => $workerEquipmentExposure->equipmentAsset?->equipmentType?->name,
                'is_primary' => $workerEquipmentExposure->is_primary,
            ],
        );

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Associazione macchinario aggiornata.');
    }

    public function destroy(
        Request $request,
        Worker $worker,
        WorkerEquipmentExposure $workerEquipmentExposure,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $workerEquipmentExposure = $this->exposureForWorker($worker, $workerEquipmentExposure);
        $workerEquipmentExposure->loadMissing('equipmentAsset.equipmentType');
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.equipment_exposure.removed',
            $workerEquipmentExposure,
            'Rimossa associazione macchinario da '.$worker->full_name,
            [
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
                'equipment_asset_id' => $workerEquipmentExposure->equipment_asset_id,
                'equipment_asset_name' => $workerEquipmentExposure->equipmentAsset?->name,
                'equipment_type_name' => $workerEquipmentExposure->equipmentAsset?->equipmentType?->name,
                'is_primary' => $workerEquipmentExposure->is_primary,
            ],
        );
        $workerEquipmentExposure->delete();

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Associazione macchinario rimossa.');
    }

    private function workerForTenant(Tenant $tenant, Worker $worker): Worker
    {
        $worker->loadMissing('company');
        abort_unless($worker->company !== null && $worker->company->tenant_id === $tenant->id, 404);

        return $worker;
    }

    private function assetForTenant(Tenant $tenant, EquipmentAsset $equipmentAsset): EquipmentAsset
    {
        $equipmentAsset->loadMissing('company');
        abort_unless($equipmentAsset->company !== null && $equipmentAsset->company->tenant_id === $tenant->id, 404);

        return $equipmentAsset;
    }

    private function exposureForWorker(Worker $worker, WorkerEquipmentExposure $workerEquipmentExposure): WorkerEquipmentExposure
    {
        abort_unless($workerEquipmentExposure->worker_id === $worker->id, 404);

        return $workerEquipmentExposure;
    }

    private function ensureAssetBelongsToWorkerCompany(Worker $worker, EquipmentAsset $equipmentAsset): void
    {
        if ($worker->company_id !== $equipmentAsset->company_id) {
            throw ValidationException::withMessages([
                'equipment_asset_id' => 'Il macchinario deve appartenere alla stessa azienda del lavoratore.',
            ]);
        }
    }

    private function ensureAssignmentIsUnique(Worker $worker, EquipmentAsset $equipmentAsset, ?int $ignoreId = null): void
    {
        $query = $worker->equipmentExposures()->where('equipment_asset_id', $equipmentAsset->id);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'equipment_asset_id' => 'Questo macchinario e\' gia\' associato al lavoratore.',
            ]);
        }
    }
}
