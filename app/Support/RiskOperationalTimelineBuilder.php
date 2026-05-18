<?php

namespace App\Support;

use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\RiskProfileItemReview;
use App\Models\Tenant;
use App\Models\Worker;
use Illuminate\Support\Collection;

class RiskOperationalTimelineBuilder
{
    public function buildForProfileItem(RiskProfileItem $profileItem, Collection $measures, int $limit = 12): Collection
    {
        $profileItem->loadMissing([
            'riskCatalogItem',
            'operationalOwner:id,name',
            'reviews.actor:id,name',
            'reviews.operationalOwner:id,name',
        ]);

        $events = collect();

        foreach ($profileItem->reviews as $review) {
            $events = $events
                ->push($this->reviewEvent($profileItem, $review))
                ->when(
                    filled($review->follow_up_status),
                    fn (Collection $collection) => $collection->push($this->followUpEvent($profileItem, $review)),
                )
                ->when(
                    filled($review->follow_up_outcome_status),
                    fn (Collection $collection) => $collection->push($this->outcomeEvent($profileItem, $review)),
                );
        }

        foreach ($measures as $measure) {
            $events = $events
                ->push($this->measureCreatedEvent($profileItem, $measure))
                ->when(
                    $measure->completed_at !== null,
                    fn (Collection $collection) => $collection->push($this->measureCompletedEvent($profileItem, $measure)),
                )
                ->when(
                    $measure->due_date !== null && $measure->status !== RiskMeasure::STATUS_IMPLEMENTED,
                    fn (Collection $collection) => $collection->push($this->measureDueEvent($profileItem, $measure)),
                );
        }

        return $events
            ->filter(fn (array $event) => $event['occurred_at'] !== null)
            ->sortByDesc(fn (array $event) => $event['sort_at'])
            ->take($limit)
            ->values()
            ->map(fn (array $event) => collect($event)->except('sort_at')->all());
    }

    public function buildForTenant(
        Tenant $tenant,
        Collection $profileItems,
        Collection $measures,
        Collection $companyLabels,
        Collection $workerLabels,
        Collection $workerCompanyLabels,
        int $limit = 10,
    ): Collection {
        $events = collect();

        $measureGroups = $measures->groupBy(fn (RiskMeasure $measure) => $this->scopeKey(
            $measure->profileable_type,
            (int) $measure->profileable_id,
            (int) $measure->risk_catalog_item_id,
        ));

        foreach ($profileItems as $profileItem) {
            $timeline = $this->buildForProfileItem(
                $profileItem,
                $measureGroups->get($this->scopeKey(
                    $profileItem->profileable_type,
                    (int) $profileItem->profileable_id,
                    (int) $profileItem->risk_catalog_item_id,
                ), collect()),
                8,
            )->map(function (array $event) use ($profileItem, $companyLabels, $workerLabels, $workerCompanyLabels) {
                return [
                    ...$event,
                    'context' => $profileItem->profileable_type === Worker::class
                        ? ($workerLabels->get($profileItem->profileable_id) ?? 'Lavoratore')
                        : ($companyLabels->get($profileItem->profileable_id) ?? 'Azienda'),
                    'company_name' => $profileItem->profileable_type === Worker::class
                        ? ($workerCompanyLabels->get($profileItem->profileable_id) ?? 'Azienda non disponibile')
                        : ($companyLabels->get($profileItem->profileable_id) ?? 'Azienda non disponibile'),
                    'route' => $profileItem->profileable_type === Worker::class
                        ? route('workers.risk-profile.review.show', [$profileItem->profileable_id, $profileItem->id])
                        : route('companies.risk-profile.review.show', [$profileItem->profileable_id, $profileItem->id]),
                ];
            });

            $events = $events->merge($timeline);
        }

        return $events
            ->sortByDesc(fn (array $event) => $event['occurred_at'])
            ->take($limit)
            ->values();
    }

    private function reviewEvent(RiskProfileItem $profileItem, RiskProfileItemReview $review): array
    {
        return [
            'type' => 'review',
            'title' => $profileItem->riskCatalogItem?->name ?? 'Rischio non disponibile',
            'label' => $review->event_type === RiskProfileItemReview::EVENT_MANUAL_ADDED
                ? 'Rischio aggiunto manualmente'
                : 'Valutazione consulente aggiornata',
            'detail' => $review->consultant_notes ?: 'Aggiornato lo stato operativo del rischio.',
            'meta' => $review->actor?->name ?: 'Sistema / seed',
            'occurred_at' => $review->reviewed_at?->format('Y-m-d H:i'),
            'sort_at' => $review->reviewed_at?->timestamp,
            'tone' => 'info',
        ];
    }

    private function followUpEvent(RiskProfileItem $profileItem, RiskProfileItemReview $review): array
    {
        $statusLabel = match ($review->follow_up_status) {
            RiskProfileItem::FOLLOW_UP_STATUS_OPEN => 'Follow-up aperto',
            RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS => 'Follow-up in lavorazione',
            RiskProfileItem::FOLLOW_UP_STATUS_BLOCKED => 'Follow-up bloccato',
            RiskProfileItem::FOLLOW_UP_STATUS_CLOSED => 'Follow-up chiuso',
            default => 'Follow-up aggiornato',
        };

        return [
            'type' => 'follow_up',
            'title' => $profileItem->riskCatalogItem?->name ?? 'Rischio non disponibile',
            'label' => $statusLabel,
            'detail' => $review->follow_up_notes ?: 'Aggiornata la presa in carico operativa.',
            'meta' => $review->operationalOwner?->name ?: 'Referente non assegnato',
            'occurred_at' => $review->reviewed_at?->format('Y-m-d H:i'),
            'sort_at' => $review->reviewed_at?->timestamp,
            'tone' => 'primary',
        ];
    }

    private function outcomeEvent(RiskProfileItem $profileItem, RiskProfileItemReview $review): array
    {
        $label = match ($review->follow_up_outcome_status) {
            RiskProfileItem::FOLLOW_UP_OUTCOME_RESOLVED => 'Esito: presidio completato',
            RiskProfileItem::FOLLOW_UP_OUTCOME_MONITORED => 'Esito: chiuso con monitoraggio',
            RiskProfileItem::FOLLOW_UP_OUTCOME_DEFERRED => 'Esito: chiuso con rinvio',
            default => 'Esito operativo registrato',
        };

        $recordedAt = $review->follow_up_outcome_recorded_at ?? $review->reviewed_at;

        return [
            'type' => 'outcome',
            'title' => $profileItem->riskCatalogItem?->name ?? 'Rischio non disponibile',
            'label' => $label,
            'detail' => $review->follow_up_outcome_notes ?: 'Registrato un esito operativo minimo.',
            'meta' => $review->actor?->name ?: 'Sistema / seed',
            'occurred_at' => $recordedAt?->format('Y-m-d H:i'),
            'sort_at' => $recordedAt?->timestamp,
            'tone' => 'success',
        ];
    }

    private function measureCreatedEvent(RiskProfileItem $profileItem, RiskMeasure $measure): array
    {
        return [
            'type' => 'measure_created',
            'title' => $measure->title,
            'label' => 'Misura registrata',
            'detail' => $this->measureFamilyLabel($measure->family).' collegata a '.$this->riskLabel($profileItem),
            'meta' => $this->measureStatusLabel($measure->status),
            'occurred_at' => $measure->created_at?->format('Y-m-d H:i'),
            'sort_at' => $measure->created_at?->timestamp,
            'tone' => 'secondary',
        ];
    }

    private function measureCompletedEvent(RiskProfileItem $profileItem, RiskMeasure $measure): array
    {
        return [
            'type' => 'measure_completed',
            'title' => $measure->title,
            'label' => 'Misura attuata',
            'detail' => $this->measureFamilyLabel($measure->family).' chiusa sul rischio '.$this->riskLabel($profileItem),
            'meta' => $measure->completed_at?->format('Y-m-d H:i'),
            'occurred_at' => $measure->completed_at?->format('Y-m-d H:i'),
            'sort_at' => $measure->completed_at?->timestamp,
            'tone' => 'success',
        ];
    }

    private function measureDueEvent(RiskProfileItem $profileItem, RiskMeasure $measure): array
    {
        return [
            'type' => 'measure_due',
            'title' => $measure->title,
            'label' => $measure->due_date !== null && $measure->due_date->isPast()
                ? 'Misura in ritardo'
                : 'Misura in agenda',
            'detail' => $this->measureFamilyLabel($measure->family).' ancora aperta sul rischio '.$this->riskLabel($profileItem),
            'meta' => $this->measureStatusLabel($measure->status),
            'occurred_at' => $measure->due_date?->format('Y-m-d'),
            'sort_at' => $measure->due_date?->startOfDay()->timestamp,
            'tone' => $measure->due_date !== null && $measure->due_date->isPast() ? 'danger' : 'warning',
        ];
    }

    private function measureFamilyLabel(string $family): string
    {
        return match ($family) {
            RiskMeasure::FAMILY_TRAINING => 'Formazione',
            RiskMeasure::FAMILY_MEDICAL => 'Visita medica',
            RiskMeasure::FAMILY_DPI => 'DPI',
            RiskMeasure::FAMILY_TECHNICAL => 'Misura tecnica',
            default => 'Misura organizzativa',
        };
    }

    private function measureStatusLabel(string $status): string
    {
        return match ($status) {
            RiskMeasure::STATUS_IMPLEMENTED => 'Attuata',
            RiskMeasure::STATUS_TO_VERIFY => 'Da verificare',
            default => 'Non attuata',
        };
    }

    private function riskLabel(RiskProfileItem $profileItem): string
    {
        return $profileItem->riskCatalogItem?->name ?? 'rischio non disponibile';
    }

    private function scopeKey(string $type, int $id, int $riskCatalogItemId): string
    {
        return implode(':', [$type, $id, $riskCatalogItemId]);
    }
}
