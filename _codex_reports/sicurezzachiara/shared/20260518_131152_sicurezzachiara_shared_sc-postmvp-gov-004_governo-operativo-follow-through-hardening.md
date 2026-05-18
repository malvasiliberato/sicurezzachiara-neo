## Contesto
SicurezzaChiara e' gia' online in area come MVP guidato. Dopo `SC-POSTMVP-GOV-001/002/003`, il punto residuo della dashboard azienda era rendere il track `Governo operativo` meno descrittivo e piu' orientato a chiusura lavoro e follow-through.

## Obiettivo
Rafforzare il cockpit operativo della dashboard azienda senza redesign, senza nuovi moduli e senza toccare il dominio consolidato.

## Stato attuale
- La dashboard aveva gia':
  - focus operativo
  - esito del motore piu' leggibile
  - CTA contestuali verso profilo rischio, registri e DVR light
- Il track `Governo operativo` restava pero' ancora troppo summary-driven.

## Assunzioni
- Il consulente deve vedere in alto le code operative minime da chiudere.
- Review, follow-up, registri famiglia e DVR light sono gia' dati disponibili e non richiedono nuove tabelle o nuovi moduli.

## Evidenze
- `app/Http/Controllers/CompanyController.php`
- `resources/js/Pages/sicurezzachiara/companies/Show.vue`
- `tests/Feature/SicurezzaChiaraCompanyManagementTest.php`
- test locale verde:
  - `11 passed / 251 assertions`
- build frontend verde:
  - `npm run build`

## Decisioni
- Aggiungere `contextBridge.operationalQueue` come read model leggero per il track operativo.
- Non cambiare il layout generale della dashboard.
- Mantenere CTA verso workspace reali gia' esistenti.

## Fasi rilevanti
1. Esteso il payload dashboard con una coda operativa minima:
   - review da chiudere
   - follow-up operativi
   - registri famiglia
   - DVR light
2. Inserita una fascia compatta nel track `Governo operativo`.
3. Riallineata la CTA `follow-up` al registro contestuale invece del generico profilo rischio.
4. Aggiornato il test feature per fissare il contratto del nuovo payload.

## Dipendenze
- `CompanyController::buildCompanyContextBridge`
- dashboard azienda `resources/js/Pages/sicurezzachiara/companies/Show.vue`
- dataset rischio/misure gia' presente

## Rischi
- Il working tree locale resta storicamente sporco fuori perimetro.
- Il publish va fatto selettivamente, senza includere file non pertinenti.
- Il warning Vite su `lottie-web` resta noto ma non e' introdotto da questo step.

## Proposta operativa
- Pubblicare solo:
  - `app/Http/Controllers/CompanyController.php`
  - `resources/js/Pages/sicurezzachiara/companies/Show.vue`
  - `tests/Feature/SicurezzaChiaraCompanyManagementTest.php`
  - tracciamento e report di step
- Fare rollout su `area` senza migration.
- Eseguire recheck live del cockpit operativo.

## Punto di stato finale
- Step ID: `SC-POSTMVP-GOV-004`
- Stato: `chiuso`
- Esito locale: verde
- Stato finale atteso per publish: `NO-DEPLOY / PRE-DEPLOY`
