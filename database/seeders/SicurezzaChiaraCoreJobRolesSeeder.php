<?php

namespace Database\Seeders;

use App\Models\JobRole;
use Illuminate\Database\Seeder;

class SicurezzaChiaraCoreJobRolesSeeder extends Seeder
{
    public function run(): void
    {
        $jobRoles = [
            [
                'code' => 'CORE-IMP',
                'name' => 'Impiegato amministrativo',
                'description' => 'Profilo core per ruoli d\'ufficio, back office e supporto amministrativo.',
            ],
            [
                'code' => 'CORE-MAG',
                'name' => 'Addetto di magazzino',
                'description' => 'Profilo core per stoccaggio, picking e logistica interna.',
            ],
            [
                'code' => 'CORE-PROD',
                'name' => 'Operatore di produzione',
                'description' => 'Profilo core per attivita\' operative di reparto e linea.',
            ],
            [
                'code' => 'CORE-CARR',
                'name' => 'Carrellista',
                'description' => 'Profilo core per conduzione mezzi di movimentazione interna.',
            ],
            [
                'code' => 'CORE-CONF',
                'name' => 'Addetto confezionamento',
                'description' => 'Profilo core per confezionamento, etichettatura e fine linea.',
            ],
            [
                'code' => 'CORE-MAN',
                'name' => 'Manutentore interno',
                'description' => 'Profilo core per interventi tecnici interni su impianti e attrezzature.',
            ],
            [
                'code' => 'CORE-PUL',
                'name' => 'Addetto pulizie',
                'description' => 'Profilo core per pulizie ordinarie e presidio servizi interni.',
            ],
            [
                'code' => 'CORE-LAB',
                'name' => 'Tecnico di laboratorio',
                'description' => 'Profilo core per campionamenti, controlli e prove di laboratorio.',
            ],
            [
                'code' => 'CORE-CARSC',
                'name' => 'Addetto carico e scarico',
                'description' => 'Profilo core per ricezione merci e movimentazione manuale di materiali.',
            ],
            [
                'code' => 'CORE-RECP',
                'name' => 'Reception / front office',
                'description' => 'Profilo core per accoglienza, front office e gestione accessi.',
            ],
            [
                'code' => 'CORE-COORD',
                'name' => 'Coordinatore operativo di reparto',
                'description' => 'Profilo core per coordinamento operativo e presidio processi di reparto.',
            ],
            [
                'code' => 'CORE-AMB',
                'name' => 'Addetto servizi ambientali',
                'description' => 'Profilo core per raccolta, presidio rifiuti e servizi ambientali operativi.',
            ],
        ];

        foreach ($jobRoles as $jobRoleData) {
            JobRole::query()->updateOrCreate(
                [
                    'source' => JobRole::SOURCE_CORE,
                    'name' => $jobRoleData['name'],
                ],
                [
                    ...$jobRoleData,
                    'tenant_id' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
