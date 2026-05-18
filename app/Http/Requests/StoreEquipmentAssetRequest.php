<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'company_site_id' => ['nullable', 'integer', 'exists:company_sites,id'],
            'equipment_type_id' => ['nullable', 'integer', 'exists:equipment_types,id', 'required_without:custom_equipment_type_name'],
            'custom_equipment_type_name' => ['nullable', 'string', 'max:255', 'required_without:equipment_type_id'],
            'asset_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_type_id.required_without' => 'Seleziona una tipologia macchinario oppure inseriscine una personalizzata.',
            'custom_equipment_type_name.required_without' => 'Inserisci una tipologia macchinario personalizzata oppure scegli una voce dal catalogo.',
        ];
    }
}
