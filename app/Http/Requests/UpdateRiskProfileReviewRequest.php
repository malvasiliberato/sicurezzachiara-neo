<?php

namespace App\Http\Requests;

use App\Models\RiskCatalogItem;
use App\Models\RiskProfileItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiskProfileReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operational_status' => ['required', 'string', Rule::in([
                RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
                RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED,
            ])],
            'consultant_decision' => ['required', 'string', Rule::in([
                RiskProfileItem::DECISION_CONFIRMED,
                RiskProfileItem::DECISION_CUSTOMIZED,
                RiskProfileItem::DECISION_EXCLUDED,
                RiskProfileItem::DECISION_MANUAL_ADDITION,
            ])],
            'final_priority' => ['nullable', 'string', Rule::in([
                RiskCatalogItem::PRIORITY_HIGH,
                RiskCatalogItem::PRIORITY_MEDIUM,
                RiskCatalogItem::PRIORITY_LOW,
            ])],
            'consultant_notes' => ['nullable', 'string', 'max:2000'],
            'review_due_at' => ['nullable', 'date'],
            'operational_owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'follow_up_status' => ['nullable', 'string', Rule::in([
                RiskProfileItem::FOLLOW_UP_STATUS_OPEN,
                RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS,
                RiskProfileItem::FOLLOW_UP_STATUS_BLOCKED,
                RiskProfileItem::FOLLOW_UP_STATUS_CLOSED,
            ])],
            'follow_up_notes' => ['nullable', 'string', 'max:3000'],
            'follow_up_due_at' => ['nullable', 'date'],
            'follow_up_outcome_status' => ['nullable', 'string', Rule::in([
                RiskProfileItem::FOLLOW_UP_OUTCOME_RESOLVED,
                RiskProfileItem::FOLLOW_UP_OUTCOME_MONITORED,
                RiskProfileItem::FOLLOW_UP_OUTCOME_DEFERRED,
            ])],
            'follow_up_outcome_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $status = $this->input('follow_up_status');
            $outcomeStatus = $this->input('follow_up_outcome_status');

            if ($status === RiskProfileItem::FOLLOW_UP_STATUS_CLOSED && blank($outcomeStatus)) {
                $validator->errors()->add(
                    'follow_up_outcome_status',
                    'Quando chiudi il follow-up devi registrare un esito operativo minimo.'
                );
            }

            if ($status !== RiskProfileItem::FOLLOW_UP_STATUS_CLOSED && filled($outcomeStatus)) {
                $validator->errors()->add(
                    'follow_up_outcome_status',
                    'L\'esito operativo puo\' essere registrato solo su un follow-up chiuso.'
                );
            }
        });
    }
}
