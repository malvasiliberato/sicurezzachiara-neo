<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskSourceLink;
use App\Models\WorkplaceType;
use Illuminate\Database\Seeder;

class SicurezzaChiaraCoreSourceRiskMappingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedJobRoleMappings();
        $this->seedEquipmentTypeMappings();
        $this->seedWorkplaceTypeMappings();
    }

    private function seedJobRoleMappings(): void
    {
        $mappings = [
            'Impiegato amministrativo' => [
                ['risk' => 'Uso prolungato di videoterminale', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Incendio e gestione emergenze', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Addetto di magazzino' => [
                ['risk' => 'Movimentazione manuale dei carichi', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
            'Operatore di produzione' => [
                ['risk' => 'Schiacciamento e cesoiamento', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Esposizione a rumore', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
                ['risk' => 'Posture incongrue e movimenti ripetitivi', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Carrellista' => [
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Vibrazioni meccaniche', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
            'Addetto confezionamento' => [
                ['risk' => 'Posture incongrue e movimenti ripetitivi', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Movimentazione manuale dei carichi', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Manutentore interno' => [
                ['risk' => 'Rischio elettrico', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Caduta dall\'alto e accesso in quota', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Addetto pulizie' => [
                ['risk' => 'Esposizione ad agenti chimici', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Scivolamento e caduta a livello', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
            'Tecnico di laboratorio' => [
                ['risk' => 'Esposizione ad agenti chimici', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Esposizione ad agenti biologici', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Addetto carico e scarico' => [
                ['risk' => 'Movimentazione manuale dei carichi', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Reception / front office' => [
                ['risk' => 'Uso prolungato di videoterminale', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
                ['risk' => 'Incendio e gestione emergenze', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Coordinatore operativo di reparto' => [
                ['risk' => 'Stress lavoro-correlato', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Addetto servizi ambientali' => [
                ['risk' => 'Esposizione ad agenti biologici', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Esposizione ad agenti chimici', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
        ];

        foreach ($mappings as $sourceName => $links) {
            $source = JobRole::query()
                ->where('source', JobRole::SOURCE_CORE)
                ->where('name', $sourceName)
                ->firstOrFail();

            $this->upsertLinks($source, $links);
        }
    }

    private function seedEquipmentTypeMappings(): void
    {
        $mappings = [
            'Carrello elevatore' => [
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Vibrazioni meccaniche', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Transpallet elettrico' => [
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
            'Pressa industriale' => [
                ['risk' => 'Schiacciamento e cesoiamento', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Esposizione a rumore', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Cesoia da taglio' => [
                ['risk' => 'Schiacciamento e cesoiamento', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
            'Trapano a colonna' => [
                ['risk' => 'Schiacciamento e cesoiamento', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Saldatrice' => [
                ['risk' => 'Rischio elettrico', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Esposizione ad agenti chimici', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Compressore d\'aria' => [
                ['risk' => 'Esposizione a rumore', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
            'Nastro trasportatore' => [
                ['risk' => 'Schiacciamento e cesoiamento', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
            'Macchina confezionatrice' => [
                ['risk' => 'Schiacciamento e cesoiamento', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Posture incongrue e movimenti ripetitivi', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Lavasciuga pavimenti' => [
                ['risk' => 'Scivolamento e caduta a livello', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
        ];

        foreach ($mappings as $sourceName => $links) {
            $source = EquipmentType::query()
                ->where('source', EquipmentType::SOURCE_CORE)
                ->where('name', $sourceName)
                ->firstOrFail();

            $this->upsertLinks($source, $links);
        }
    }

    private function seedWorkplaceTypeMappings(): void
    {
        $mappings = [
            'Linea produttiva' => [
                ['risk' => 'Schiacciamento e cesoiamento', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
                ['risk' => 'Esposizione a rumore', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Area di magazzino' => [
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Scivolamento e caduta a livello', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Ufficio operativo' => [
                ['risk' => 'Uso prolungato di videoterminale', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Stress lavoro-correlato', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Baia di carico e scarico' => [
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Microclima e ambienti severi', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Laboratorio e controllo qualita\'' => [
                ['risk' => 'Esposizione ad agenti chimici', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
                ['risk' => 'Esposizione ad agenti biologici', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Officina manutenzione' => [
                ['risk' => 'Rischio elettrico', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Caduta dall\'alto e accesso in quota', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Area esterna e piazzale' => [
                ['risk' => 'Circolazione mezzi e interferenze', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Microclima e ambienti severi', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
            ],
            'Locale tecnico e impianti' => [
                ['risk' => 'Rischio elettrico', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Incendio e gestione emergenze', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Area servizi e deposito detergenti' => [
                ['risk' => 'Esposizione ad agenti chimici', 'relevance' => RiskSourceLink::RELEVANCE_PRIMARY],
                ['risk' => 'Scivolamento e caduta a livello', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
            'Reception e front office' => [
                ['risk' => 'Uso prolungato di videoterminale', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
                ['risk' => 'Incendio e gestione emergenze', 'relevance' => RiskSourceLink::RELEVANCE_SECONDARY],
            ],
        ];

        foreach ($mappings as $sourceName => $links) {
            $source = WorkplaceType::query()
                ->where('source', WorkplaceType::SOURCE_CORE)
                ->where('name', $sourceName)
                ->firstOrFail();

            $this->upsertLinks($source, $links);
        }
    }

    private function upsertLinks(JobRole|EquipmentType|WorkplaceType $source, array $links): void
    {
        foreach ($links as $linkData) {
            $risk = RiskCatalogItem::query()
                ->where('source', RiskCatalogItem::SOURCE_CORE)
                ->where('name', $linkData['risk'])
                ->firstOrFail();

            RiskSourceLink::query()->updateOrCreate(
                [
                    'risk_catalog_item_id' => $risk->id,
                    'sourceable_type' => $source::class,
                    'sourceable_id' => $source->id,
                ],
                [
                    'relevance' => $linkData['relevance'],
                    'notes' => 'Starter pack core prudenziale: mapping iniziale riusabile e non esaustivo.',
                ],
            );
        }
    }
}
