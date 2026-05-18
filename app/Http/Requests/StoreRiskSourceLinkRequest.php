<?php

namespace App\Http\Requests;

use App\Models\RiskSourceLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRiskSourceLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'source_family' => ['required', Rule::in(['job_role', 'equipment_type', 'workplace_type'])],
            'sourceable_id' => ['required', 'integer'],
            'relevance' => ['required', Rule::in([
                RiskSourceLink::RELEVANCE_PRIMARY,
                RiskSourceLink::RELEVANCE_SECONDARY,
            ])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
