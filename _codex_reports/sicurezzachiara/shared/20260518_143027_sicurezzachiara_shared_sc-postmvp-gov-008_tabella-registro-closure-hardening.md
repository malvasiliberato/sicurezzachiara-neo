## Executive summary
- Contesto: dopo il publish della coda di chiusura contestuale, il registro misure restava ancora troppo summary-driven a livello di singola riga.
- Obiettivo: rendere ogni riga piu' leggibile come unita' di chiusura lavoro, senza aprire nuovi moduli e senza cambiare grammatica UI.
- Modifiche: introdotto `operational_posture` nel payload del registro e resa visibile la postura operativa accanto alle CTA di riga.
- Invariato: nessuna migration, nessun cambio di dominio, nessun redesign, nessuna modifica a ATECO/cluster o DVR.
- Stato finale: `NO-DEPLOY / PRE-DEPLOY`.

## Obiettivo dello step
- Step ID: `SC-POSTMVP-GOV-008`
- Titolo: `Tabella registro orientata alla chiusura per riga`
- Obiettivo: chiarire a colpo d'occhio se una misura e':
  - scaduta da chiudere
  - in follow-up
  - da attuare
  - da validare
  - gia' registrata

## File verificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\MeasureRegistryController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\measure-registries\Index.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`

## File modificati
- `G:\Mirror\htdocs\tpl.default.vue\app\Http\Controllers\MeasureRegistryController.php`
- `G:\Mirror\htdocs\tpl.default.vue\resources\js\Pages\sicurezzachiara\measure-registries\Index.vue`
- `G:\Mirror\htdocs\tpl.default.vue\tests\Feature\SicurezzaChiaraMeasureRegistryTest.php`

## Modifiche effettuate
- `MeasureRegistryController.php`
  - aggiunto `operational_posture` al payload di ogni misura
  - introdotto il resolver minimo `registryMeasureOperationalPosture()`
- `Index.vue`
  - visualizzato badge di postura operativa prima delle CTA di riga
  - helper di riga riallineato per preferire `operational_posture.helper`
- `SicurezzaChiaraMeasureRegistryTest.php`
  - estese le asserzioni Inertia per fissare il contratto:
    - `Presidio da attuare`
    - `Scaduto da chiudere`

## Test eseguiti
- Comando:
  - `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests/Feature/SicurezzaChiaraMeasureRegistryTest.php tests/Feature/SicurezzaChiaraCompanyManagementTest.php`
- Esito:
  - `20 passed / 367 assertions`
- Build:
  - `npm run build` verde

## Rischi residui
- la postura operativa e' ancora una lettura sintetica derivata, non un workflow separato
- il routing di chiusura resta intenzionalmente appoggiato alle route esistenti
- il valore reale va confermato con publish e recheck live su `area`

## Prossimo step consigliato
- publish su `area` e recheck live del registro contestuale con postura per riga

## Stato finale
- Stato: `NO-DEPLOY / PRE-DEPLOY`
