<?php

namespace App\Http\Requests;

use App\Models\RiskCatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualRiskProfileItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'risk_catalog_item_id' => ['required', 'integer', Rule::exists(RiskCatalogItem::class, 'id')],
            'final_priority' => ['nullable', 'string', Rule::in([
                RiskCatalogItem::PRIORITY_HIGH,
                RiskCatalogItem::PRIORITY_MEDIUM,
                RiskCatalogItem::PRIORITY_LOW,
            ])],
            'consultant_notes' => ['nullable', 'string', 'max:2000'],
            'review_due_at' => ['nullable', 'date'],
        ];
    }
}
