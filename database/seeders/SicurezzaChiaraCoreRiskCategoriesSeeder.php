<?php

namespace Database\Seeders;

use App\Models\RiskCategory;
use Illuminate\Database\Seeder;

class SicurezzaChiaraCoreRiskCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'MECC',
                'name' => 'Rischi meccanici',
                'description' => 'Urto, schiacciamento, cesoiamento, parti in movimento e uso attrezzature.',
                'sort_order' => 10,
            ],
            [
                'code' => 'FIS',
                'name' => 'Rischi fisici',
                'description' => 'Rumore, vibrazioni, microclima e altri agenti fisici.',
                'sort_order' => 20,
            ],
            [
                'code' => 'CHIM',
                'name' => 'Rischi chimici',
                'description' => 'Agenti chimici, detergenti, fumi, vapori e sostanze pericolose.',
                'sort_order' => 30,
            ],
            [
                'code' => 'BIOL',
                'name' => 'Rischi biologici',
                'description' => 'Esposizioni biologiche legate a contesti, materiali o agenti potenzialmente contaminanti.',
                'sort_order' => 40,
            ],
            [
                'code' => 'ELEC',
                'name' => 'Rischi elettrici',
                'description' => 'Contatti elettrici, quadri, impianti, manutenzione e alimentazioni.',
                'sort_order' => 50,
            ],
            [
                'code' => 'ERG',
                'name' => 'Rischi ergonomici',
                'description' => 'Movimentazione manuale carichi, posture e sovraccarico biomeccanico.',
                'sort_order' => 60,
            ],
            [
                'code' => 'ORG',
                'name' => 'Rischi organizzativi',
                'description' => 'Interferenze, viabilita\' interna, stress e assetto organizzativo.',
                'sort_order' => 70,
            ],
            [
                'code' => 'EMER',
                'name' => 'Rischi emergenza e sicurezza generale',
                'description' => 'Incendio, emergenze, esodo e presidi di sicurezza generale.',
                'sort_order' => 80,
            ],
        ];

        foreach ($categories as $categoryData) {
            RiskCategory::query()->updateOrCreate(
                ['name' => $categoryData['name']],
                [
                    ...$categoryData,
                    'is_active' => true,
                ],
            );
        }
    }
}
