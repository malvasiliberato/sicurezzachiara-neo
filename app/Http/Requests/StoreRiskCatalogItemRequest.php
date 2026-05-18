<?php

namespace App\Http\Requests;

use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRiskCatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'risk_category_id' => ['required', 'integer', 'exists:risk_categories,id'],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'expected_measures' => ['nullable', 'array'],
            'expected_measures.*.code' => ['nullable', 'string', 'max:100'],
            'expected_measures.*.family' => ['required_with:expected_measures', Rule::in([
                RiskMeasure::FAMILY_ORGANIZATIONAL,
                RiskMeasure::FAMILY_TECHNICAL,
                RiskMeasure::FAMILY_DPI,
                RiskMeasure::FAMILY_TRAINING,
                RiskMeasure::FAMILY_MEDICAL,
            ])],
            'expected_measures.*.title' => ['required_with:expected_measures', 'string', 'max:255'],
            'expected_measures.*.description' => ['nullable', 'string'],
            'expected_measures.*.is_required' => ['nullable', 'boolean'],
            'expected_measures.*.allows_family_substitution' => ['nullable', 'boolean'],
            'default_priority' => ['required', Rule::in([
                RiskCatalogItem::PRIORITY_LOW,
                RiskCatalogItem::PRIORITY_MEDIUM,
                RiskCatalogItem::PRIORITY_HIGH,
            ])],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
