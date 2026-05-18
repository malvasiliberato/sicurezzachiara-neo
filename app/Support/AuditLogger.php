<?php

namespace App\Support;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AuditLogger
{
    public function log(
        Tenant $tenant,
        ?User $actor,
        string $action,
        ?Model $auditable = null,
        ?string $summary = null,
        array $metadata = [],
    ): AuditEvent {
        return AuditEvent::query()->create([
            'tenant_id' => $tenant->id,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'summary' => $summary,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'occurred_at' => Carbon::now(),
        ]);
    }
}
