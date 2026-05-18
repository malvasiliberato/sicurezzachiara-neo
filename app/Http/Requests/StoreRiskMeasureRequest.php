<?php

namespace App\Http\Requests;

use App\Models\RiskMeasure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRiskMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'family' => [
                'required',
                'string',
                Rule::in([
                    RiskMeasure::FAMILY_ORGANIZATIONAL,
                    RiskMeasure::FAMILY_TECHNICAL,
                    RiskMeasure::FAMILY_DPI,
                    RiskMeasure::FAMILY_TRAINING,
                    RiskMeasure::FAMILY_MEDICAL,
                ]),
            ],
            'expected_measure_code' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    RiskMeasure::STATUS_IMPLEMENTED,
                    RiskMeasure::STATUS_NOT_IMPLEMENTED,
                    RiskMeasure::STATUS_TO_VERIFY,
                ]),
            ],
            'details' => ['nullable', 'array'],
            'details.provider' => ['nullable', 'string', 'max:255'],
            'details.delivery_mode' => ['nullable', 'string', 'max:255'],
            'details.valid_until' => ['nullable', 'date'],
            'details.physician' => ['nullable', 'string', 'max:255'],
            'details.protocol' => ['nullable', 'string', 'max:255'],
            'details.item_name' => ['nullable', 'string', 'max:255'],
            'details.category' => ['nullable', 'string', 'max:255'],
            'details.owner' => ['nullable', 'string', 'max:255'],
            'details.verification_method' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
