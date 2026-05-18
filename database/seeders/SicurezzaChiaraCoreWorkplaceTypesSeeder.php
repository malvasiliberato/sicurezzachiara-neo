<?php

namespace Database\Seeders;

use App\Models\WorkplaceType;
use Illuminate\Database\Seeder;

class SicurezzaChiaraCoreWorkplaceTypesSeeder extends Seeder
{
    public function run(): void
    {
        $workplaceTypes = [
            [
                'code' => 'CORE-LINEA',
                'name' => 'Linea produttiva',
                'description' => 'Tipologia core per aree di lavorazione continua o assemblaggio.',
            ],
            [
                'code' => 'CORE-MAG',
                'name' => 'Area di magazzino',
                'description' => 'Tipologia core per stoccaggio, picking e movimentazione merci.',
            ],
            [
                'code' => 'CORE-UFF',
                'name' => 'Ufficio operativo',
                'description' => 'Tipologia core per ambienti amministrativi e di coordinamento.',
            ],
            [
                'code' => 'CORE-BAIA',
                'name' => 'Baia di carico e scarico',
                'description' => 'Tipologia core per aree di carico, scarico e ricevimento merci.',
            ],
            [
                'code' => 'CORE-LAB',
                'name' => 'Laboratorio e controllo qualita\'',
                'description' => 'Tipologia core per prove, campionamenti e controllo qualita\'.',
            ],
            [
                'code' => 'CORE-OFF',
                'name' => 'Officina manutenzione',
                'description' => 'Tipologia core per interventi tecnici interni e manutenzione.',
            ],
            [
                'code' => 'CORE-PIAZ',
                'name' => 'Area esterna e piazzale',
                'description' => 'Tipologia core per aree esterne, viabilita\' e manovra mezzi.',
            ],
            [
                'code' => 'CORE-TEC',
                'name' => 'Locale tecnico e impianti',
                'description' => 'Tipologia core per centrali, quadri e locali impiantistici.',
            ],
            [
                'code' => 'CORE-SERV',
                'name' => 'Area servizi e deposito detergenti',
                'description' => 'Tipologia core per servizi, prodotti di pulizia e stoccaggi accessori.',
            ],
            [
                'code' => 'CORE-REC',
                'name' => 'Reception e front office',
                'description' => 'Tipologia core per accoglienza, attesa e gestione ingressi.',
            ],
        ];

        foreach ($workplaceTypes as $workplaceTypeData) {
            WorkplaceType::query()->updateOrCreate(
                ['name' => $workplaceTypeData['name'], 'tenant_id' => null],
                [
                    ...$workplaceTypeData,
                    'source' => WorkplaceType::SOURCE_CORE,
                    'is_active' => true,
                ],
            );
        }
    }
}
