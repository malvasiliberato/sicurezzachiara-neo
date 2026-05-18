## Executive summary
- Contesto:
  - dopo `SC-POSTMVP-GOV-012`, review singola e gestione misure parlavano gia' la stessa grammatica di corsie, ma il registro contestuale non conservava ancora in modo esplicito la memoria del rischio di origine.
- Obiettivo:
  - chiudere il raccordo `review singola -> registro contestuale` rendendo esplicito da quale rischio si arriva e dove si rientra.
- Modifiche effettuate:
  - `risk_profile_item_id` passato al registro da review e gestione misure
  - `workspaceContext.originRisk` costruito nel registro
  - CTA di rientro esplicite verso review, misure e profilo del rischio origine
  - badge `Rischio origine` sulle righe coinvolte
- Stato finale:
  - `NO-DEPLOY / PRE-DEPLOY`

## Obiettivo dello step
- Rendere il registro contestuale meno anonimo quando viene aperto da una review singola.
- Evitare che il consulente debba ricostruire mentalmente:
  - da quale rischio e' partito
  - se deve rientrare in review o in gestione misure
  - quale riga del registro appartiene proprio al rischio di origine

## File verificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskProfileReviewController.php`
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskMeasureController.php`
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\MeasureRegistryController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\measure-registries\Index.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskProfileReviewTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskMeasureManagementTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`

## File modificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskProfileReviewController.php`
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\RiskMeasureController.php`
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\MeasureRegistryController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\measure-registries\Index.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskProfileReviewTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraRiskMeasureManagementTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`

## Modifiche effettuate
- `RiskProfileReviewController`
  - `workspaceRoute` del `reviewBridge` ora include `risk_profile_item_id`.
- `RiskMeasureController`
  - `workspaceRoute` del `measureBridge` ora include `risk_profile_item_id`.
- `MeasureRegistryController`
  - legge `risk_profile_item_id` dal contesto
  - valida che il rischio origine appartenga davvero al tenant
  - costruisce `workspaceContext.originRisk` con:
    - `reviewRoute`
    - `measuresRoute`
    - `profileRoute`
    - `riskName`
    - `parentLabel`
    - helper narrativo coerente con l'origine
  - marca le righe con:
    - `risk_profile_item_id`
    - `is_origin_risk`
- `Index.vue`
  - mostra il blocco `Rischio di origine`
  - espone CTA:
    - `Torna alla review`
    - `Torna alle misure`
    - `Apri profilo rischio`
  - marca visivamente la riga del rischio origine

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests\Feature\SicurezzaChiaraRiskProfileReviewTest.php tests\Feature\SicurezzaChiaraRiskMeasureManagementTest.php tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`
- Esito:
  - `23 passed / 316 assertions`
- Build:
  - `npm run build`
  - verde

## Rischi residui
- Il registro contestuale continua volutamente a restare aziendale, non diventa un mini workspace isolato per singolo rischio.
- Il rientro esplicito e' disponibile solo quando il contesto porta `risk_profile_item_id`, quindi resta intenzionalmente opt-in.
- Nessun rischio di dominio nuovo introdotto.

## Prossimo step consigliato
- Publish su `area` e recheck live del tratto:
  - `review singola -> registro contestuale -> rientro alla review origine`

## Stato finale
- `NO-DEPLOY / PRE-DEPLOY`
