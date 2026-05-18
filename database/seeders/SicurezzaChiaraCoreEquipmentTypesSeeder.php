<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class SicurezzaChiaraCoreEquipmentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $equipmentTypes = [
            [
                'code' => 'CORE-MULETTO',
                'name' => 'Carrello elevatore',
                'description' => 'Tipologia core per mezzi di sollevamento e movimentazione interna.',
            ],
            [
                'code' => 'CORE-TRANSP',
                'name' => 'Transpallet elettrico',
                'description' => 'Tipologia core per movimentazione interna leggera e media su corsie o baie.',
            ],
            [
                'code' => 'CORE-PRESSA',
                'name' => 'Pressa industriale',
                'description' => 'Tipologia core per presse e macchine di deformazione metalli.',
            ],
            [
                'code' => 'CORE-CESOIA',
                'name' => 'Cesoia da taglio',
                'description' => 'Tipologia core per macchine di taglio lamiera e profili.',
            ],
            [
                'code' => 'CORE-TRAPANO',
                'name' => 'Trapano a colonna',
                'description' => 'Tipologia core per foratura fissa di officina o reparto.',
            ],
            [
                'code' => 'CORE-SALDAT',
                'name' => 'Saldatrice',
                'description' => 'Tipologia core per saldatura ad arco o lavorazioni equivalenti.',
            ],
            [
                'code' => 'CORE-COMP',
                'name' => 'Compressore d\'aria',
                'description' => 'Tipologia core per impianti o attrezzature ad aria compressa.',
            ],
            [
                'code' => 'CORE-NASTRO',
                'name' => 'Nastro trasportatore',
                'description' => 'Tipologia core per linee di trasferimento e smistamento materiali.',
            ],
            [
                'code' => 'CORE-CONFEZ',
                'name' => 'Macchina confezionatrice',
                'description' => 'Tipologia core per confezionamento automatico o semiautomatico.',
            ],
            [
                'code' => 'CORE-LAVASC',
                'name' => 'Lavasciuga pavimenti',
                'description' => 'Tipologia core per pulizia meccanizzata di superfici interne.',
            ],
        ];

        foreach ($equipmentTypes as $equipmentTypeData) {
            EquipmentType::query()->updateOrCreate(
                ['name' => $equipmentTypeData['name'], 'tenant_id' => null],
                [
                    ...$equipmentTypeData,
                    'source' => EquipmentType::SOURCE_CORE,
                    'is_active' => true,
                ],
            );
        }
    }
}
