# SicurezzaChiara - SC-UI-001 Pattern Audit

## Contesto
Audit leggero del template Velzon eseguito senza cleanup distruttivo, con l'obiettivo di individuare i pattern UI piu' adatti a un SaaS operativo B2B per consulenti della sicurezza.

## Obiettivo
Classificare i pattern utili del template da riusare come base della futura UI di SicurezzaChiara.

## Evidenze lette
- `resources/js/Pages/widgets/index.vue`
- `resources/js/Pages/tables/basic.vue`
- `resources/js/Pages/forms/layouts.vue`
- `resources/js/Pages/ui/tabs.vue`
- `resources/js/Pages/ui/offcanvas.vue`
- `resources/js/Pages/dashboard/*`
- `resources/js/Components/menu.vue`
- `app/Http/Controllers/VelzonRoutesController.php`

## Famiglie di pattern riusabili

### 1. KPI cards
- pattern molto utile
- punti forti: lettura immediata, uso naturale di icone/avatar-title, supporto a numeri, delta e stato
- uso consigliato: overview consulente, priorita', stato portafoglio aziende

### 2. Summary / operative cards
- pattern molto utile
- punti forti: ottimo equilibrio tra sintesi, stato e progress bar
- uso consigliato: copertura rischi, formazione, visite, DPI, livello di presidio

### 3. Tabelle standard
- pattern molto utile
- punti forti: varianti allineate, stripe/hover/compact, badge integrati, colonne azioni semplici
- uso consigliato: aziende, lavoratori, scadenze, misure, registri

### 4. Chart cards
- pattern utile
- punti forti: card con header leggero, chart Apex in blocchi sobri, buone per dashboard B2B
- uso consigliato: trend scadenze, criticita', copertura misure, rischi per origine

### 5. Badge e stati
- pattern fondamentale
- punti forti: Velzon fornisce combinazioni leggibili con `*-subtle`
- uso consigliato: conforme, da verificare, in scadenza, scaduto, non coperto, attuato

### 6. Toolbar e filtri
- pattern molto utile
- punti forti: search-box, row di filtri, pulsanti soft e primari
- uso consigliato: workspace operativi e viste elenco

### 7. Alert / callout
- pattern utile
- punti forti: chiari, sobri, facilmente contestualizzabili al dominio
- uso consigliato: scadenze ravvicinate, rischi non coperti, note consulente

### 8. Form sections
- pattern molto utile
- punti forti: grid chiara, card sections, input e select gia' allineati al template
- uso consigliato: anagrafiche, configurazione mansioni, associazione misure

### 9. Tabs / workspace pattern
- pattern molto utile
- punti forti: ottimi per separare profilo rischio, DPI, formazione, visite, documenti
- uso consigliato: workspace per azienda, mansione o rischio

### 10. Offcanvas / modal
- pattern utile
- punti forti: dettaglio rapido e azioni senza perdere contesto
- uso consigliato: dettaglio rischio, dettaglio scadenza, storico misura, note consulente

## Pattern presenti ma sconsigliati
- widget troppo marketing-oriented
- componenti molto decorativi o troppo consumer
- dashboard con rumore informativo eccessivo
- blocchi NFT / crypto / landing pages come ispirazione diretta

## Decisioni
- non procedere con cleanup esteso del template
- usare il template come libreria di pattern
- costruire una reference page interna come gold standard visivo del progetto

## Punto di stato finale
`SC-UI-001` chiuso. Audit completato senza bonifiche aggressive.
