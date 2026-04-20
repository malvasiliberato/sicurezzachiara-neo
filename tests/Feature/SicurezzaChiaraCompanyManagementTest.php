<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\User;

test('authenticated users can create companies and sites inside their current tenant', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $companyResponse = $this->post(route('companies.store'), [
        'name' => 'Metalnova S.r.l.',
        'legal_name' => 'Metalnova S.r.l.',
        'vat_number' => 'IT01234567890',
        'tax_code' => '01234567890',
        'industry' => 'Metalmeccanica',
        'contact_email' => 'info@metalnova.test',
        'contact_phone' => '059000000',
        'city' => 'Modena',
        'province' => 'MO',
        'notes' => 'Cliente fondativo.',
    ]);

    $company = Company::query()->firstOrFail();

    $companyResponse->assertRedirect(route('companies.show', $company));
    expect($company->tenant_id)->toBe($user->fresh()->current_tenant_id);

    $siteResponse = $this->post(route('companies.sites.store', $company), [
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
        'address_line' => 'Via Roma 1',
        'postal_code' => '41121',
        'city' => 'Modena',
        'province' => 'MO',
        'notes' => 'Sede produttiva.',
    ]);

    $siteResponse->assertRedirect(route('companies.show', $company));
    expect($company->fresh()->sites)->toHaveCount(1);
});
