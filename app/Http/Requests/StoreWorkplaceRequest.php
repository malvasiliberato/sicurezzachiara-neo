<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkplaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_site_id' => ['required', 'integer', 'exists:company_sites,id'],
            'workplace_type_id' => ['nullable', 'integer', 'exists:workplace_types,id', 'required_without:custom_workplace_type_name'],
            'custom_workplace_type_name' => ['nullable', 'string', 'max:255', 'required_without:workplace_type_id'],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'workplace_type_id.required_without' => 'Seleziona una tipologia luogo oppure inseriscine una personalizzata.',
            'custom_workplace_type_name.required_without' => 'Inserisci una tipologia luogo personalizzata oppure scegli una voce dal catalogo.',
        ];
    }
}
