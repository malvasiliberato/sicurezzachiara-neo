## Executive summary
- Step ID: `SC-POSTMVP-GOV-011`
- Stato: `NO-DEPLOY / PRE-DEPLOY`
- Obiettivo: completare il raccordo operativo `dashboard -> profilo rischio azienda -> registro misure` usando la stessa grammatica di corsie, toni e CTA.
- Modifiche effettuate:
  - `workspaceBridge` del profilo rischio azienda arricchito con `workQueue` e `operationalQueue`
  - blocco `Passaggio operativo del contesto` riallineato a corsie, toni e CTA gia' usati in dashboard e registro
  - test feature aggiornato sul nuovo contratto
- Rimasto invariato:
  - nessun cambio di dominio
  - nessuna migration
  - nessun nuovo modulo
  - nessun redesign

## Obiettivo dello step
Rendere il profilo rischio azienda un ponte operativo leggibile, non una pagina solo consulenziale o summary-driven. Il consulente deve poter capire da qui quale corsia aprire subito e ritrovare la stessa semantica gia' usata in dashboard e registro.

## File verificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskProfileController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\risk-profiles\CompanyShow.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskProfileEngineTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\CompanyController.php`
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\MeasureRegistryController.php`

## File modificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskProfileController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\risk-profiles\CompanyShow.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskProfileEngineTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\AI_CONTEXT.md`
- `G:\Mirror\htdocs\tpl.default.vue\sicurezzachiara_master_progress.md`

## Modifiche effettuate
- `RiskProfileController`
  - aggiunte `workQueue` e `operationalQueue` al `workspaceBridge` del profilo rischio azienda
  - corsie esposte:
    - `Corsia scaduti`
    - `Corsia review`
    - `Corsia follow-up`
    - `Corsia copertura`
  - toni coerenti con dashboard e registro:
    - `danger`
    - `primary`
    - `warning`
    - `info`
- `CompanyShow.vue`
  - il blocco `Passaggio operativo del contesto` mostra una `Coda di lavoro minima`
  - aggiunta una sezione `Corsie operative del profilo`
  - badge e CTA usano la stessa grammatica delle altre aree operative
- `SicurezzaChiaraRiskProfileEngineTest`
  - il contratto del `workspaceBridge` ora verifica lane e tone minimi anche sul profilo rischio azienda

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests\Feature\SicurezzaChiaraRiskProfileEngineTest.php tests\Feature\SicurezzaChiaraCompanyManagementTest.php tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`
- Esito:
  - `24 passed / 450 assertions`
- Comando:
  - `npm run build`
- Esito:
  - verde
- Warning noti:
  - warning `lottie-web eval` gia' noto e non introdotto da questo step

## Rischi residui
- Il profilo rischio azienda resta ancora una pagina piu' consulenziale del registro: il riallineamento migliora la lettura operativa, ma non sostituisce il ruolo del workspace misure.
- Il working tree locale resta storicamente sporco fuori perimetro di questo step.
- Il publish live va ancora validato su `area.sicurezzachiara.it`.

## Decisioni richieste
- Nessuna decisione di prodotto nuova emersa in questo step.

## Prossimo step consigliato
- Publish su `area` e recheck live del raccordo `dashboard -> profilo rischio -> registro`.

## Stato finale
- Stato: `NO-DEPLOY / PRE-DEPLOY`
