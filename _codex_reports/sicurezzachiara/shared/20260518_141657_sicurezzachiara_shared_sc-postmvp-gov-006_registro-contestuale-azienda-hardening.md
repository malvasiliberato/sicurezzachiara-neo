## Contesto
Dopo `SC-POSTMVP-GOV-005`, il cockpit operativo pubblicato su `area.sicurezzachiara.it` risultava coerente, ma il registro misure aperto dal contesto aziendale manteneva ancora una superficie troppo portfolio-driven.

## Obiettivo
Rafforzare il comportamento del registro contestuale azienda senza aprire nuovi moduli e senza cambiare il dominio: meno rumore, piu' perimetro esplicito, CTA e filtri piu' naturali per il consulente.

## Stato attuale
- il registro famiglia era gia' funzionante
- il contesto aziendale arrivava correttamente
- restava pero' visibile anche una grammatica da portfolio:
  - filtro azienda ancora presente
  - conteggio filtri attivi che includeva anche il contesto gia' fissato
  - KPI iniziali non abbastanza leggibili come dati del solo perimetro aziendale

## Assunzioni
- il dominio `risk_measures` resta invariato
- il registro famiglia continua a essere workspace unico, non modulo specialistico
- il contesto aziendale deve restringere la lettura, non riaprire il portfolio

## Evidenze
- `MeasureRegistryController` gia' riceveva `company_id`, `origin`, `focus`
- il payload Inertia conteneva gia' abbastanza dati per una lettura piu' contestuale
- il test feature sul registro copriva gia' il bridge dashboard -> registry

## Decisioni
- introdotto un `workspaceContext` piu' esplicito per il caso aziendale:
  - `mode=company_scoped`
  - `isCompanyScoped`
  - `showCompanyFilter`
  - `contextLabel`
  - `companyName`
- nel caso aziendale:
  - il filtro azienda viene nascosto
  - il contesto azienda non conta come filtro attivo
  - i KPI iniziali vengono riletti come KPI del perimetro aziendale
  - la narrativa esplicita che conviene lavorare per stato/referente e non riaprire il portfolio

## Fasi rilevanti
1. hardening del payload backend in `MeasureRegistryController`
2. hardening della lettura frontend in `measure-registries/Index.vue`
3. allineamento del contratto test in `SicurezzaChiaraMeasureRegistryTest`
4. correzione del test fragile che verificava una stringa HTML troppo specifica

## File modificati
- `app/Http/Controllers/MeasureRegistryController.php`
- `resources/js/Pages/sicurezzachiara/measure-registries/Index.vue`
- `tests/Feature/SicurezzaChiaraMeasureRegistryTest.php`
- `AI_CONTEXT.md`
- `sicurezzachiara_master_progress.md`

## Verifiche
- `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests\Feature\SicurezzaChiaraMeasureRegistryTest.php tests\Feature\SicurezzaChiaraCompanyManagementTest.php`
- esito: `20 passed / 355 assertions`
- `npm run build`
- esito: verde

## Dipendenze
- cockpit operativo gia' pubblicato su `area`
- dataset showcase gia' attivo e coerente
- nessuna migration o seed richiesta

## Rischi
- il registro generale portfolio resta volutamente piu' ampio: il contrasto tra vista portfolio e vista contestuale va preservato con attenzione
- il live recheck su `area` resta necessario per validare la resa finale del payload pubblicato

## Proposta operativa
- publish mirato su `main`
- rollout su `area.sicurezzachiara.it` senza migration
- recheck live del registro contestuale dal dashboard azienda

## Punto di stato finale
- step: `SC-POSTMVP-GOV-006`
- stato: `chiuso`
- esito locale: `NO-DEPLOY / PRE-DEPLOY`
- prossima ripartenza: publish su `area` e smoke live del registro contestuale
