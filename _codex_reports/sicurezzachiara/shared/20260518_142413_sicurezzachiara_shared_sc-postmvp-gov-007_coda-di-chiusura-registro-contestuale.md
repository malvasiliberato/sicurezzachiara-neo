## Contesto
Dopo `SC-POSTMVP-GOV-006`, il registro contestuale azienda era gia' piu' sobrio e chiaramente fissato sul perimetro aziendale. Restava pero' un gap di follow-through: il consulente vedeva il contesto, ma non ancora una pista minima e ordinata di chiusura lavoro.

## Obiettivo
Rendere il registro contestuale piu' orientato all'azione immediata, facendo emergere una coda di chiusura tra scaduti, follow-up, review e rischi scoperti, senza aprire nuovi moduli e senza cambiare il dominio.

## Stato attuale
- bridge contestuale azienda gia' presente
- KPI contestuali gia' presenti
- il workspace restava pero' ancora centrato su lettura e filtro, piu' che su priorita' di chiusura

## Assunzioni
- il registro famiglia resta workspace unico
- la chiusura lavoro deve passare dalle route gia' esistenti
- review e profilo rischio restano i luoghi giusti per le azioni consulenziali

## Evidenze
- `contextBridge` aveva gia' `suggestedAction`, `stats` e `actions`
- il dataset showcase esponeva gia' casi vivi di:
  - misure scadute
  - follow-up aperti
  - rischi scoperti

## Decisioni
- introdotta `contextBridge.operationalQueue`
- la coda espone solo le piste davvero disponibili nel contesto corrente:
  - `Chiudi scaduti`
  - `Segui follow-up`
  - `Riallinea review` se presenti review dovute
  - `Copri rischi scoperti`
- il frontend mostra la coda subito nel bridge contestuale, sopra la tabella

## Fasi rilevanti
1. estensione del payload backend in `MeasureRegistryController`
2. esposizione della `Coda di chiusura` in `Index.vue`
3. aggiornamento dei test feature sul contratto del bridge
4. riallineamento dei test al dataset reale showcase

## File modificati
- `app/Http/Controllers/MeasureRegistryController.php`
- `resources/js/Pages/sicurezzachiara/measure-registries/Index.vue`
- `tests/Feature/SicurezzaChiaraMeasureRegistryTest.php`
- `AI_CONTEXT.md`
- `sicurezzachiara_master_progress.md`

## Verifiche
- `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests\Feature\SicurezzaChiaraMeasureRegistryTest.php tests\Feature\SicurezzaChiaraCompanyManagementTest.php`
- esito: `20 passed / 363 assertions`
- `npm run build`
- esito: verde

## Dipendenze
- nessuna migration
- nessun seed
- publish su `area` possibile via procedura Plesk gia' consolidata

## Rischi
- la coda di chiusura deve restare compatta: se cresce troppo, il registro torna rumoroso
- il dataset reale puo' cambiare l'ordine delle piste: i test devono fissare il contratto, non simulare scenari inventati

## Proposta operativa
- publish mirato su `main`
- rollout su `area.sicurezzachiara.it` senza migration
- recheck live del registro contestuale con login HTTP e verifica payload

## Punto di stato finale
- step: `SC-POSTMVP-GOV-007`
- stato: `chiuso`
- esito locale: `NO-DEPLOY / PRE-DEPLOY`
- prossima ripartenza: publish live e verifica della `Coda di chiusura`
