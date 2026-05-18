## Executive summary
- Contesto:
  - dopo `SC-POSTMVP-GOV-013` il registro contestuale conservava gia' il rischio origine, ma il rientro al profilo rischio azienda restava ancora generico
- Obiettivo:
  - chiudere il loop `registro contestuale -> profilo rischio azienda` senza cambiare dominio, layout o moduli
- Stato attuale:
  - il profilo azienda ora accetta e valida `risk_profile_item_id` quando viene riaperto dal registro contestuale
  - `workspaceBridge.originRisk` espone il rischio di rientro e CTA coerenti
  - il registro preserva `risk_profile_item_id` anche nel ritorno al profilo azienda
- Stato finale:
  - `NO-DEPLOY / PRE-DEPLOY`

## Obiettivo dello step
- rendere esplicito il rischio di rientro nel profilo azienda
- preservare il focus consulenziale da cui il registro era stato aperto
- evitare che il rientro venga risucchiato dal focus piu' urgente del profilo

## File verificati
- `app/Http/Controllers/RiskProfileController.php`
- `app/Http/Controllers/MeasureRegistryController.php`
- `resources/js/Pages/sicurezzachiara/risk-profiles/CompanyShow.vue`
- `tests/Feature/SicurezzaChiaraRiskProfileEngineTest.php`
- `tests/Feature/SicurezzaChiaraMeasureRegistryTest.php`

## File modificati
- `app/Http/Controllers/RiskProfileController.php`
- `app/Http/Controllers/MeasureRegistryController.php`
- `resources/js/Pages/sicurezzachiara/risk-profiles/CompanyShow.vue`
- `tests/Feature/SicurezzaChiaraRiskProfileEngineTest.php`
- `tests/Feature/SicurezzaChiaraMeasureRegistryTest.php`
- `AI_CONTEXT.md`
- `sicurezzachiara_master_progress.md`

## Modifiche effettuate
- `RiskProfileController`
  - legge `risk_profile_item_id` nella show azienda
  - valida che il rischio origine appartenga davvero al profilo aziendale corrente
  - costruisce `workspaceBridge.originRisk` con review route, measures route e registry route
  - preserva il focus di provenienza quando esiste un rischio origine esplicito
- `MeasureRegistryController`
  - il ritorno al profilo azienda da `contextBridge` e da `originRisk.profileRoute` conserva `risk_profile_item_id`
  - il loop `registro -> profilo` non perde piu' il rischio origine
- `CompanyShow.vue`
  - aggiunge un blocco compatto `Rischio di rientro`
  - espone CTA sobrie: review, misure, riapertura registro rischio
- test
  - nuovo contratto testato per il rientro al profilo azienda dal registro contestuale
  - aggiornata l’aspettativa sul profilo rischio preservato dal registro

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests\Feature\SicurezzaChiaraRiskProfileEngineTest.php tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`
- Esito:
  - `15 passed / 241 assertions`
- Build:
  - `npm run build`
  - esito verde

## Rischi residui
- il loop ora e' coerente per il profilo azienda; resta da verificare live su `area`
- il focus suggerito del profilo continua giustamente a esistere, ma quando c'e' un rischio origine esplicito il sistema conserva il focus di provenienza solo nei rientri collegati

## Decisioni richieste
- nessuna decisione di prodotto nuova

## Prossimo step consigliato
- publish su `area` e smoke live del tratto `registro contestuale -> profilo rischio azienda`

## Stato finale
- `NO-DEPLOY / PRE-DEPLOY`
