## Executive summary
- Step ID: `SC-POSTMVP-GOV-012`
- Stato: `NO-DEPLOY / PRE-DEPLOY`
- Obiettivo: uniformare il tratto `profilo rischio -> review singola -> gestione misure` con la stessa grammatica di corsie, toni e prossimo passo gia' usata nel cockpit e nel registro.
- Modifiche effettuate:
  - `reviewBridge` arricchito con `laneLabel`, `actionLabel`, `actionRoute` e `operationalQueue`
  - `measureBridge` arricchito con la stessa semantica
  - review e gestione misure mostrano ora una piccola coda operativa coerente
- Rimasto invariato:
  - nessun cambio di dominio
  - nessuna migration
  - nessun nuovo modulo
  - nessun redesign

## Obiettivo dello step
Far percepire review e misure come due tratti della stessa corsia operativa, non come pagine isolate. Il consulente deve capire da ciascuna pagina qual e' il prossimo passo utile e dove rientrare per chiudere il lavoro.

## File verificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskProfileReviewController.php`
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskMeasureController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\risk-profiles\Review.vue`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\risk-measures\Manage.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskProfileReviewTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskMeasureManagementTest.php`

## File modificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskProfileReviewController.php`
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskMeasureController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\risk-profiles\Review.vue`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\risk-measures\Manage.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskProfileReviewTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskMeasureManagementTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\AI_CONTEXT.md`
- `G:\Mirror\htdocs\tpl.default.vue\sicurezzachiara_master_progress.md`

## Modifiche effettuate
- `RiskProfileReviewController`
  - `reviewBridge.decision` ora espone anche:
    - `laneLabel`
    - `actionLabel`
    - `actionRoute`
  - aggiunta `reviewBridge.operationalQueue` con corsie:
    - `Corsia follow-up`
    - `Corsia copertura`
    - `Corsia review`
- `RiskMeasureController`
  - `measureBridge.decision` ora espone anche:
    - `laneLabel`
    - `actionLabel`
    - `actionRoute`
  - aggiunta `measureBridge.operationalQueue` con corsie:
    - `Corsia copertura`
    - `Corsia misure`
    - `Corsia review`
- `Review.vue`
  - il bridge operativo mostra badge di corsia e CTA coerente col tono
  - aggiunta sezione `Corsie operative della review`
- `Manage.vue`
  - il bridge operativo mostra badge di corsia e CTA coerente col tono
  - aggiunta sezione `Corsie operative delle misure`
- test feature
  - fissato il contratto minimo su lane e tone per review e gestione misure

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests\Feature\SicurezzaChiaraRiskProfileReviewTest.php tests\Feature\SicurezzaChiaraRiskMeasureManagementTest.php tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`
- Esito:
  - `22 passed / 287 assertions`
- Comando:
  - `npm run build`
- Esito:
  - verde
- Warning noti:
  - warning `lottie-web eval` gia' noto e non introdotto da questo step

## Rischi residui
- Review e misure sono ora piu' coerenti, ma la navigazione resta volutamente sobria: non e' ancora un workflow wizardizzato.
- Il working tree locale resta storicamente sporco fuori perimetro.
- Il publish live su `area` va ancora verificato.

## Decisioni richieste
- Nessuna decisione di prodotto nuova emersa.

## Prossimo step consigliato
- Publish su `area` e recheck live del tratto `review -> misure -> registri`.

## Stato finale
- Stato: `NO-DEPLOY / PRE-DEPLOY`
