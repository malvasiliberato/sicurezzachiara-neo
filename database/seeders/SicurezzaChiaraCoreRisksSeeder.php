<?php

namespace Database\Seeders;

use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\RiskMeasure;
use Illuminate\Database\Seeder;

class SicurezzaChiaraCoreRisksSeeder extends Seeder
{
    public function run(): void
    {
        $risks = [
            [
                'category' => 'Rischi meccanici',
                'code' => 'R-MECC-SCHIACC',
                'name' => 'Schiacciamento e cesoiamento',
                'description' => 'Presenza di organi in movimento, zone di presa o fasi operative con pericolo meccanico diretto.',
                'expected_measures' => [
                    $this->measure('protections', RiskMeasure::FAMILY_TECHNICAL, 'Protezioni e schermature della macchina', 'Verifica o ripristino delle protezioni fisiche sulle zone di pericolo.', true, true),
                    $this->measure('training_specific', RiskMeasure::FAMILY_TRAINING, 'Addestramento operativo specifico', 'Addestramento mirato sui punti di schiacciamento e sulle procedure sicure.', true, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
            ],
            [
                'category' => 'Rischi fisici',
                'code' => 'R-FIS-RUMORE',
                'name' => 'Esposizione a rumore',
                'description' => 'Attivita\' o ambienti con emissione sonora significativa o prolungata.',
                'expected_measures' => [
                    $this->measure('hearing_ppe', RiskMeasure::FAMILY_DPI, 'Protezione uditiva assegnata', 'DPI per la protezione dell\'udito effettivamente assegnato e gestito.', true, true),
                    $this->measure('medical_protocol_noise', RiskMeasure::FAMILY_MEDICAL, 'Sorveglianza sanitaria dedicata', 'Protocollo sanitario coerente con l\'esposizione a rumore.', true, true),
                    $this->measure('noise_information', RiskMeasure::FAMILY_TRAINING, 'Informazione sui comportamenti in area rumorosa', 'Richiamo pratico sull\'uso corretto dei DPI e sul rispetto delle aree segnalate.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
            [
                'category' => 'Rischi fisici',
                'code' => 'R-FIS-VIBR',
                'name' => 'Vibrazioni meccaniche',
                'description' => 'Uso di mezzi o attrezzature che possono determinare esposizione a vibrazioni mano-braccio o corpo intero.',
                'expected_measures' => [
                    $this->measure('vibration_assessment', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Valutazione dell\'esposizione e tempi di impiego', 'Presidio organizzativo sui tempi di utilizzo e sulla verifica dell\'esposizione.', true, true),
                    $this->measure('vibration_training', RiskMeasure::FAMILY_TRAINING, 'Informazione su uso corretto e pause', 'Indicazioni operative per ridurre l\'esposizione e gestire le pause.', false, true),
                    $this->measure('medical_protocol_vibration', RiskMeasure::FAMILY_MEDICAL, 'Sorveglianza sanitaria per vibrazioni', 'Protocollo sanitario coerente con il livello di esposizione stimato.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
            [
                'category' => 'Rischi chimici',
                'code' => 'R-CHIM-AGENTI',
                'name' => 'Esposizione ad agenti chimici',
                'description' => 'Presenza di sostanze, detergenti, fumi o vapori che richiedono gestione prudenziale dell\'esposizione.',
                'expected_measures' => [
                    $this->measure('chemical_ppe', RiskMeasure::FAMILY_DPI, 'DPI per manipolazione agenti chimici', 'Guanti, protezioni oculari o altri DPI coerenti con le sostanze impiegate.', true, true),
                    $this->measure('chemical_information', RiskMeasure::FAMILY_TRAINING, 'Informazione su sostanze e schede di sicurezza', 'Richiamo pratico su rischi, etichette e gestione sicura delle sostanze.', true, true),
                    $this->measure('sds_and_storage', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Schede di sicurezza e stoccaggio controllato', 'Presidio organizzativo su disponibilita\' SDS e modalita\' di stoccaggio.', true, true),
                    $this->measure('medical_protocol_chemical', RiskMeasure::FAMILY_MEDICAL, 'Sorveglianza sanitaria dedicata', 'Protocollo sanitario minimo quando il profilo di esposizione lo richiede.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
            ],
            [
                'category' => 'Rischi biologici',
                'code' => 'R-BIOL-AGENTI',
                'name' => 'Esposizione ad agenti biologici',
                'description' => 'Contatto potenziale con materiali, superfici o contesti che richiedono una gestione biologica prudenziale.',
                'expected_measures' => [
                    $this->measure('biological_ppe', RiskMeasure::FAMILY_DPI, 'DPI igienico-sanitari dedicati', 'DPI coerenti con il contesto biologico da presidiare.', true, true),
                    $this->measure('hygiene_protocol', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Protocollo igienico e di sanificazione', 'Presidio organizzativo su procedure, igiene e gestione materiali.', true, true),
                    $this->measure('medical_protocol_bio', RiskMeasure::FAMILY_MEDICAL, 'Sorveglianza sanitaria dedicata', 'Protocollo sanitario per esposizioni biologiche rilevanti.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
            ],
            [
                'category' => 'Rischi elettrici',
                'code' => 'R-ELEC-CONT',
                'name' => 'Rischio elettrico',
                'description' => 'Attivita\' o ambienti con accesso a quadri, impianti, alimentazioni o attrezzature elettriche.',
                'expected_measures' => [
                    $this->measure('electrical_checks', RiskMeasure::FAMILY_TECHNICAL, 'Verifica impianto e attrezzature elettriche', 'Controllo minimo su integrita\', protezioni e assetto impiantistico.', true, true),
                    $this->measure('electrical_training', RiskMeasure::FAMILY_TRAINING, 'Informazione/addestramento sul rischio elettrico', 'Richiamo operativo su accessi, divieti e comportamenti corretti.', true, true),
                    $this->measure('access_control', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Controllo accessi a locali e quadri', 'Presidio organizzativo per limitare accessi impropri alle aree elettriche.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
            ],
            [
                'category' => 'Rischi ergonomici',
                'code' => 'R-ERG-MMC',
                'name' => 'Movimentazione manuale dei carichi',
                'description' => 'Attivita\' con movimentazione, traino o posture incongrue legate al trasporto di materiali.',
                'expected_measures' => [
                    $this->measure('handling_training', RiskMeasure::FAMILY_TRAINING, 'Formazione sulla movimentazione corretta', 'Formazione o addestramento sul gesto corretto e sulle posture.', true, true),
                    $this->measure('handling_aids', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Ausili o organizzazione del carico', 'Rotazioni, ausili o organizzazione del lavoro per ridurre il sovraccarico.', true, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
            [
                'category' => 'Rischi ergonomici',
                'code' => 'R-ERG-POST',
                'name' => 'Posture incongrue e movimenti ripetitivi',
                'description' => 'Attivita\' ripetitive o con posture mantenute che possono generare sovraccarico biomeccanico.',
                'expected_measures' => [
                    $this->measure('ergonomic_review', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Verifica ergonomica della postazione', 'Controllo minimo dell\'assetto di lavoro e della ripetitivita\'.', true, true),
                    $this->measure('micro_pause_information', RiskMeasure::FAMILY_TRAINING, 'Informazione su pause e gesti corretti', 'Indicazioni pratiche per alternanza attivita\' e pause.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
            [
                'category' => 'Rischi ergonomici',
                'code' => 'R-ERG-VDT',
                'name' => 'Uso prolungato di videoterminale',
                'description' => 'Attivita\' amministrative o tecniche con uso continuativo di postazioni VDT.',
                'expected_measures' => [
                    $this->measure('posture_information', RiskMeasure::FAMILY_TRAINING, 'Informazione su postura e pause', 'Indicazioni pratiche su pause, postura e uso corretto della postazione.', true, true),
                    $this->measure('workstation_review', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Verifica postazione VDT', 'Controllo minimo sull\'assetto ergonomico della postazione.', true, true),
                    $this->measure('medical_protocol_vdt', RiskMeasure::FAMILY_MEDICAL, 'Sorveglianza sanitaria VDT', 'Presidio sanitario minimo quando coerente con il profilo espositivo.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_LOW,
            ],
            [
                'category' => 'Rischi organizzativi',
                'code' => 'R-ORG-CADLIV',
                'name' => 'Scivolamento e caduta a livello',
                'description' => 'Pavimentazioni, transiti o condizioni ambientali che aumentano il rischio di caduta in piano.',
                'expected_measures' => [
                    $this->measure('cleaning_plan', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Piano pulizie e segnalazione criticita\'', 'Presidio organizzativo per pavimentazioni, sversamenti e ostacoli.', true, true),
                    $this->measure('anti_slip_ppe', RiskMeasure::FAMILY_DPI, 'Calzature adeguate al contesto', 'DPI o calzature coerenti con superfici e ambiente di lavoro.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
            [
                'category' => 'Rischi organizzativi',
                'code' => 'R-ORG-CADQUO',
                'name' => 'Caduta dall\'alto e accesso in quota',
                'description' => 'Attivita\' che comportano accesso a scale, trabattelli o postazioni sopraelevate.',
                'expected_measures' => [
                    $this->measure('fall_protection_ppe', RiskMeasure::FAMILY_DPI, 'DPI o sistemi anticaduta coerenti', 'Presidio individuale o collettivo per accessi in quota.', true, true),
                    $this->measure('work_at_height_training', RiskMeasure::FAMILY_TRAINING, 'Addestramento per attivita\' in quota', 'Richiamo operativo su accessi, uso attrezzature e comportamenti sicuri.', true, true),
                    $this->measure('access_equipment_check', RiskMeasure::FAMILY_TECHNICAL, 'Verifica attrezzature e punti di accesso', 'Controllo minimo su scale, parapetti e attrezzature di accesso.', true, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
            ],
            [
                'category' => 'Rischi organizzativi',
                'code' => 'R-ORG-MEZZI',
                'name' => 'Circolazione mezzi e interferenze',
                'description' => 'Presenza di mezzi, carrelli o interferenze operative in spazi condivisi.',
                'expected_measures' => [
                    $this->measure('separated_routes', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Percorsi separati mezzi e pedoni', 'Assetto organizzativo e segnaletica coerenti per la viabilita\' interna.', true, true),
                    $this->measure('traffic_training', RiskMeasure::FAMILY_TRAINING, 'Addestramento sulla circolazione interna', 'Richiamo operativo su interferenze, attraversamenti e procedure interne.', true, true),
                    $this->measure('visibility_ppe', RiskMeasure::FAMILY_DPI, 'Alta visibilita\' o presidio equivalente', 'DPI visibile per aree ad interferenza mezzi/pedoni quando coerente.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
            ],
            [
                'category' => 'Rischi emergenza e sicurezza generale',
                'code' => 'R-EMER-INC',
                'name' => 'Incendio e gestione emergenze',
                'description' => 'Presenza di condizioni che richiedono presidio su emergenze, esodo e sicurezza generale.',
                'expected_measures' => [
                    $this->measure('emergency_plan', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Piano di emergenza e procedure di esodo', 'Presidio organizzativo su emergenze, esodo e ruoli minimi.', true, true),
                    $this->measure('emergency_training', RiskMeasure::FAMILY_TRAINING, 'Informazione/formazione su emergenze', 'Richiamo operativo su comportamenti, segnalazioni e procedure di evacuazione.', true, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
            [
                'category' => 'Rischi fisici',
                'code' => 'R-FIS-MICRO',
                'name' => 'Microclima e ambienti severi',
                'description' => 'Lavoro in ambienti caldi, freddi o esposti agli agenti atmosferici con possibile impatto sul presidio operativo.',
                'expected_measures' => [
                    $this->measure('microclimate_organization', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Organizzazione del lavoro e pause', 'Assetto operativo prudenziale su turni, pause e presidio ambientale.', true, true),
                    $this->measure('protective_clothing', RiskMeasure::FAMILY_DPI, 'Abbigliamento o DPI coerenti con il contesto', 'Presidio individuale adeguato alle condizioni microclimatiche.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
            [
                'category' => 'Rischi organizzativi',
                'code' => 'R-ORG-STRESS',
                'name' => 'Stress lavoro-correlato',
                'description' => 'Fattori organizzativi, turnazioni o carichi che richiedono monitoraggio prudenziale del contesto di lavoro.',
                'expected_measures' => [
                    $this->measure('work_organization_review', RiskMeasure::FAMILY_ORGANIZATIONAL, 'Verifica organizzativa minima', 'Presidio sul carico di lavoro, sulle turnazioni e sulla chiarezza organizzativa.', true, true),
                    $this->measure('manager_alignment', RiskMeasure::FAMILY_TRAINING, 'Allineamento responsabili e preposti', 'Momento formativo minimo su segnali e gestione del carico organizzativo.', false, true),
                ],
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
        ];

        foreach ($risks as $riskData) {
            $category = RiskCategory::query()->where('name', $riskData['category'])->firstOrFail();

            RiskCatalogItem::query()->updateOrCreate(
                [
                    'tenant_id' => null,
                    'name' => $riskData['name'],
                ],
                [
                    'risk_category_id' => $category->id,
                    'source' => RiskCatalogItem::SOURCE_CORE,
                    'code' => $riskData['code'],
                    'description' => $riskData['description'],
                    'expected_measures' => $riskData['expected_measures'] ?? null,
                    'default_priority' => $riskData['default_priority'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function measure(
        string $code,
        string $family,
        string $title,
        string $description,
        bool $required = true,
        bool $allowsFamilySubstitution = true,
    ): array {
        return [
            'code' => $code,
            'family' => $family,
            'title' => $title,
            'description' => $description,
            'is_required' => $required,
            'allows_family_substitution' => $allowsFamilySubstitution,
        ];
    }
}
