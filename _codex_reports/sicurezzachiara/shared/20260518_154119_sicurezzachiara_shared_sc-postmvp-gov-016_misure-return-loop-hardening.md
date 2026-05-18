## Contesto

Step `SC-POSTMVP-GOV-016` nella sequenza post-MVP di maturazione del cockpit operativo.

Il tratto `profilo rischio -> review -> misure -> registro -> rientro` era gia' forte su dashboard, profilo e review, ma la pagina `Misure` conservava ancora in modo troppo debole `origin` e `focus` della provenienza.

## Obiettivo

Rendere la pagina `Misure` coerente con il resto del loop operativo:

- preservare `origin` e `focus` quando si arriva da review o profilo;
- far rientrare correttamente a review, profilo e registro contestuale;
- non introdurre nuovo dominio;
- non cambiare layout o grammatica visiva oltre il minimo necessario.

## Stato attuale

Prima di questo step:

- `Review` conservava gia' `origin`, `focus` e `returnContext`;
- `Manage` lavorava bene sul rischio ma non esponeva un vero `returnContext`;
- le mutation `store/update/delete` della pagina misure rischiavano di perdere il query context;
- il pulsante di ritorno alto non seguiva ancora sempre il profilo di provenienza.

## Assunzioni

- La memoria operativa del consulente va preservata anche nelle pagine intermedie, non solo nei grandi workspace.
- La pagina misure resta una corsia del rischio, non un modulo autonomo.
- Il ritorno finale giusto va reso esplicito, non lasciato implicito.

## Evidenze

File toccati:

- `app/Http/Controllers/RiskProfileReviewController.php`
- `app/Http/Controllers/RiskMeasureController.php`
- `resources/js/Pages/sicurezzachiara/risk-measures/Manage.vue`
- `tests/Feature/SicurezzaChiaraRiskMeasureManagementTest.php`
- `tests/Feature/SicurezzaChiaraRiskProfileReviewTest.php`

## Decisioni

- `Review -> Misure` ora passa esplicitamente `origin` e `focus`.
- `Misure` ricalcola `profileRoute`, `reviewRoute`, `workspaceRoute` e `currentRoute` preservando il contesto di provenienza.
- `measureBridge` espone `origin`, `originLabel`, `focus`, `focusLabel` e `returnContext`.
- Le mutation sulla pagina misure mantengono il query context nel redirect finale.

## Fasi rilevanti

1. Lettura del tratto review/misure e del bridge corrente.
2. Riallineamento controller review per passare il context alla route misure.
3. Hardening controller misure per leggere e preservare il context.
4. Aggiunta UI minima di `Rientro finale` nella pagina misure.
5. Aggiornamento test feature su review e measure management.
6. Verifica locale con test e build.

## Dipendenze

- Contratto di `origin/focus` consolidato negli step GOV-013 / GOV-014 / GOV-015.
- Route gia' esistenti per:
  - `companies.risk-profile.show`
  - `workers.risk-profile.show`
  - `companies.risk-profile.review.show`
  - `workers.risk-profile.review.show`
  - `measure-registries.index`

## Rischi

- Le URL delle pagine misure diventano piu' verbose perche' conservano query operative, ma il guadagno di chiarezza e' maggiore del costo.
- Le mutation con query context vanno sempre trattate con attenzione per non rompere redirect futuri.

## Proposta operativa

Pubblicare lo step su `main` e fare smoke live su `area` verificando:

- `reviewBridge.actions.measuresRoute` con query contestuale;
- `measureBridge.returnContext` su live;
- ritorno a profilo/review/registro con lo stesso `focus`.

## Punto di stato finale

- Stato step: pronto per publish
- Test locali: verdi (`15 passed / 219 assertions`)
- Build locale: verde
- Migration: non richieste
