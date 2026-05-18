## Executive summary
- Step ID: `SC-POSTMVP-GOV-017`
- Stato: `NO-DEPLOY / PRE-DEPLOY`
- Obiettivo: riallineare il profilo rischio lavoratore al loop di rientro su rischio specifico gia' presente nel profilo azienda.
- Esito: il profilo lavoratore conserva ora `risk_profile_item_id` quando rientra dal registro contestuale e mostra un blocco esplicito di `Rischio di rientro` con CTA coerenti verso review, misure e registro.

## Obiettivo dello step
- Rendere simmetrico il tratto `registro contestuale -> profilo rischio lavoratore`.
- Preservare focus e rischio origine senza introdurre nuovi moduli o cambiare dominio.

## File verificati
- `app/Http/Controllers/RiskProfileController.php`
- `resources/js/Pages/sicurezzachiara/risk-profiles/WorkerShow.vue`
- `tests/Feature/SicurezzaChiaraRiskProfileEngineTest.php`
- `app/Http/Controllers/RiskProfileReviewController.php`
- `app/Http/Controllers/RiskMeasureController.php`

## File modificati
- `app/Http/Controllers/RiskProfileController.php`
- `resources/js/Pages/sicurezzachiara/risk-profiles/WorkerShow.vue`
- `tests/Feature/SicurezzaChiaraRiskProfileEngineTest.php`
- `AI_CONTEXT.md`
- `sicurezzachiara_master_progress.md`

## Modifiche effettuate
- `showWorker()` legge ora `risk_profile_item_id`, risolve il rischio origine nel perimetro del lavoratore e fallisce con `404` se l'id non appartiene al profilo.
- `buildWorkerWorkspaceBridge()` accetta `originRiskProfileItem` e costruisce `workspaceBridge.originRisk` con:
  - `reviewRoute`
  - `measuresRoute`
  - `registryRoute`
  - `helper`
- `actions.registryRoute` del profilo lavoratore preserva `risk_profile_item_id` quando il loop nasce da un rischio specifico.
- `WorkerShow.vue` espone un blocco `Rischio di rientro` con CTA:
  - `Torna alla review`
  - `Torna alle misure`
  - `Riapri registro rischio`
- Aggiunto test feature dedicato al rientro del profilo lavoratore dal registro contestuale.

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests\Feature\SicurezzaChiaraRiskProfileEngineTest.php tests\Feature\SicurezzaChiaraRiskProfileReviewTest.php tests\Feature\SicurezzaChiaraRiskMeasureManagementTest.php`
- Esito:
  - `21 passed / 330 assertions`
- Build:
  - `npm run build`
  - esito verde

## Rischi residui
- Il loop azienda e il loop lavoratore sono ora allineati sul rientro da rischio origine, ma resta da verificare il comportamento live dopo publish.
- `AI_CONTEXT.md` e `sicurezzachiara_master_progress.md` restano file di tracciamento locale e non vanno mischiati al publish applicativo.

## Decisioni richieste
- Nessuna decisione di dominio nuova emersa in questo step.

## Prossimo step consigliato
- Publish su `area` e smoke live mirato del loop `registro -> profilo rischio lavoratore -> review/misure`.

## Stato finale
- `NO-DEPLOY / PRE-DEPLOY`
