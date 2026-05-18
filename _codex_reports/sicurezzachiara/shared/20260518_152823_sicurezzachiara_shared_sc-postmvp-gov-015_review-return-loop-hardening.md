## Executive summary
- Contesto:
  - il profilo rischio azienda era gia' allineato alle corsie operative, ma il passaggio verso la review singola perdeva il focus di provenienza
- Obiettivo:
  - rendere esplicito il rientro `profilo rischio -> review singola -> profilo`
- Stato attuale:
  - la review conserva `origin` e `focus` del profilo
  - la pagina distingue il focus operativo interno della review dal focus di provenienza
  - il blocco `Rientro finale` rende esplicite le CTA di chiusura
- Stato finale:
  - `NO-DEPLOY / PRE-DEPLOY`

## Obiettivo dello step
- preservare il focus operativo quando si apre una review dal profilo rischio
- evitare che il focus di rientro venga sovrascritto dal focus interno della review
- esporre CTA sobrie e coerenti verso profilo, misure e registro

## File verificati
- `app/Http/Controllers/RiskProfileController.php`
- `app/Http/Controllers/RiskProfileReviewController.php`
- `resources/js/Pages/sicurezzachiara/risk-profiles/Review.vue`
- `tests/Feature/SicurezzaChiaraRiskProfileEngineTest.php`
- `tests/Feature/SicurezzaChiaraRiskProfileReviewTest.php`

## File modificati
- `app/Http/Controllers/RiskProfileController.php`
- `app/Http/Controllers/RiskProfileReviewController.php`
- `resources/js/Pages/sicurezzachiara/risk-profiles/Review.vue`
- `tests/Feature/SicurezzaChiaraRiskProfileEngineTest.php`
- `tests/Feature/SicurezzaChiaraRiskProfileReviewTest.php`
- `AI_CONTEXT.md`
- `sicurezzachiara_master_progress.md`

## Modifiche effettuate
- `RiskProfileController`
  - le route `review_route` generate nel profilo passano `origin` e `focus`
- `RiskProfileReviewController`
  - legge `origin` e `focus` dalla request
  - costruisce `backRoute` e `reviewBridge.returnContext` mantenendo il focus del profilo
  - separa il `workspaceFocus` interno della review dal `focus` di provenienza
- `Review.vue`
  - aggiunge un blocco `Rientro finale`
  - rende visibili origine e focus del ritorno
  - espone CTA verso profilo, misure e registro contestuale
- test
  - contratto delle route review dal profilo fissato
  - contratto della review con ritorno esplicito fissato

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests/Feature/SicurezzaChiaraRiskProfileEngineTest.php tests/Feature/SicurezzaChiaraRiskProfileReviewTest.php`
- Esito:
  - `15 passed / 204 assertions`
- Build:
  - `npm run build`
  - esito verde

## Rischi residui
- il contratto e' consolidato localmente ma resta da verificare live su `area`
- il passo successivo dovra' controllare che la narrativa di rientro resti coerente anche entrando da profilo azienda reale showcase

## Decisioni richieste
- nessuna decisione di prodotto nuova

## Prossimo step consigliato
- publish su `area` e smoke live del raccordo `profilo rischio -> review singola`

## Stato finale
- `NO-DEPLOY / PRE-DEPLOY`
