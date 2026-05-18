<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'primary_site_id' => ['nullable', 'integer', 'exists:company_sites,id'],
            'job_role_id' => ['nullable', 'integer', 'exists:job_roles,id'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'tax_code' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'hire_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'primary_site_id' => $this->filled('primary_site_id') ? (int) $this->input('primary_site_id') : null,
            'company_id' => $this->filled('company_id') ? (int) $this->input('company_id') : null,
            'job_role_id' => $this->filled('job_role_id') ? (int) $this->input('job_role_id') : null,
        ]);
    }
}
