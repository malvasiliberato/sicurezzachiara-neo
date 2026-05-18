## Executive summary
- Contesto: dopo `SC-POSTMVP-GOV-008` la tabella del registro mostrava bene la postura operativa, ma il raccordo tra corsia contestuale e CTA di riga era ancora implicito.
- Obiettivo: rendere ogni riga piu' governabile, mostrando esplicitamente da quale corsia di chiusura conviene partire.
- Modifiche: `next_step` ora porta anche `tone` e `lane`, e il frontend usa queste informazioni per rendere piu' leggibili badge e CTA primarie.
- Invariato: nessuna migration, nessun nuovo modulo, nessun cambio di dominio.
- Stato finale: `NO-DEPLOY / PRE-DEPLOY`.

## Obiettivo dello step
- Step ID: `SC-POSTMVP-GOV-009`
- Titolo: `CTA di riga allineate alla corsia di chiusura`
- Obiettivo: collegare in modo piu' netto la `Coda di chiusura` contestuale con l'azione immediata disponibile su ogni singola misura.

## File verificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\MeasureRegistryController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\measure-registries\Index.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`

## File modificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\MeasureRegistryController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\measure-registries\Index.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`

## Modifiche effettuate
- `MeasureRegistryController.php`
  - `buildRegistryMeasureBridge()` ora espone:
    - `tone`
    - `lane.key`
    - `lane.label`
  - priorita' esplicita per le misure scadute, anche quando convivono con follow-up aperto
- `Index.vue`
  - resa visibile la `corsia` prima delle CTA di riga
  - il pulsante principale usa il tono della corsia (`danger`, `warning`, `primary`, `secondary`)
- `SicurezzaChiaraMeasureRegistryTest.php`
  - aggiornato il contratto Inertia per fissare:
    - `Corsia copertura`
    - `Corsia scaduti`
    - tono CTA coerente
    - priorita' `scaduti` sopra `follow-up` nel caso combinato

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests/Feature/SicurezzaChiaraMeasureRegistryTest.php tests/Feature/SicurezzaChiaraCompanyManagementTest.php`
- Esito:
  - `20 passed / 379 assertions`
- Build:
  - `npm run build` verde

## Rischi residui
- il registro resta volutamente un workspace minimo: le corsie chiariscono la chiusura, ma non sostituiscono una coda operativa dedicata
- alcuni dataset showcase possono ancora mostrare piu' righe in corsia `scaduti` che in altre corsie

## Prossimo step consigliato
- publish su `area` e recheck live del raccordo `corsia -> CTA di riga`

## Stato finale
- Stato: `NO-DEPLOY / PRE-DEPLOY`
