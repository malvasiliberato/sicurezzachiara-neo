## Executive summary
- Contesto: dopo l'allineamento del registro contestuale, la dashboard azienda restava leggermente sfalsata nel linguaggio operativo.
- Obiettivo: far parlare cockpit e registro con la stessa grammatica di chiusura, senza redesign e senza nuovi moduli.
- Modifiche: `workQueue` e `operationalQueue` ora portano `tone` e `laneLabel`, e il frontend li usa per badge e CTA coerenti.
- Invariato: nessuna migration, nessun nuovo modulo, nessun cambio di dominio.
- Stato finale: `NO-DEPLOY / PRE-DEPLOY`.

## Obiettivo dello step
- Step ID: `SC-POSTMVP-GOV-010`
- Titolo: `Dashboard allineata alle corsie del registro`
- Obiettivo: rendere piu' naturale il passaggio `dashboard -> registro contestuale`, mantenendo la stessa semantica operativa.

## File verificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\CompanyController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\companies\Show.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraCompanyManagementTest.php`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`

## File modificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\CompanyController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\companies\Show.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraCompanyManagementTest.php`

## Modifiche effettuate
- `CompanyController.php`
  - `workQueue` arricchita con:
    - `tone`
    - `laneLabel`
  - `operationalQueue` riallineata nel linguaggio:
    - `Riallinea review`
    - `Segui follow-up`
    - `Chiudi scaduti`
    - `Copri rischi scoperti`
  - action label rese coerenti con la corsia operativa
- `Show.vue`
  - badge corsia visibili nella `Coda di lavoro minima`
  - CTA della dashboard colorate in base al tono operativo
  - stessa grammatica visiva del registro senza cambiare layout
- `SicurezzaChiaraCompanyManagementTest.php`
  - esteso il contratto Inertia per fissare label, tone e corsie della dashboard

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests/Feature/SicurezzaChiaraCompanyManagementTest.php tests/Feature/SicurezzaChiaraMeasureRegistryTest.php`
- Esito:
  - `20 passed / 397 assertions`
- Build:
  - `npm run build` verde

## Rischi residui
- il cockpit resta ancora intenzionalmente leggero: non sostituisce un agenda/worklist dedicato
- alcuni count della dashboard restano summary-driven per scelta MVP

## Prossimo step consigliato
- publish su `area` e recheck live del cockpit allineato alle corsie del registro

## Stato finale
- Stato: `NO-DEPLOY / PRE-DEPLOY`
