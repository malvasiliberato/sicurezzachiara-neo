# SicurezzaChiara - SC-BOOT-002 Cleanup Report

## Contesto
Sessione di pulizia controllata del template Velzon Laravel + Vue per trasformare la demo iniziale in una base prodotto ordinata, senza rompere shell applicativa, build e componenti shared.

## Obiettivo
Ridurre il rumore demo mantenendo intatte le parti riusabili del template e lasciando una base coerente con SicurezzaChiara.

## Evidenze principali
- rimosso `app/Http/Controllers/VelzonRoutesController.php`
- sostituito `routes/web.php` con routing minimo prodotto
- introdotto `SicurezzaChiaraController` con dashboard e workspace placeholder
- sostituiti menu, topbar e footer con versioni minime coerenti con il progetto
- rimossi layout orizzontale e two-column non necessari al bootstrap
- eliminate le principali cartelle demo sotto `resources/js/Pages`

## Rimosso
- demo dashboards multipli
- apps, charts, forms, maps, icons, tables, widgets, pages marketing e showcase
- navigation sample non pertinente
- route demo massive e controller di supporto associato

## Mantenuto
- shell verticale Velzon
- layout shared riusabili
- pipeline Vite / SCSS
- componenti base e infrastruttura Jetstream / Fortify
- assets e aree shared non chiaramente demo-critical

## Disattivato o neutralizzato
- `RightBar` disaccoppiato dal layout
- menu laterale ridotto a una navigazione minima di prodotto
- topbar ridotta eliminando elementi demo-heavy
- home iniziale demo e-commerce sostituita con dashboard sobria

## Rinviato con prudenza
- bonifica aggressiva di assets demo non referenziati
- riallineamento di pagine standard Jetstream non critiche per il bootstrap
- ulteriore pruning di dipendenze `package.json` non piu' usate

## Delta template -> prodotto
- da demo generica multi-sezione a base verticale singola orientata al prodotto
- da routing esteso e rumoroso a superficie controllata e leggibile
- da navigation showcase a workspace minimo SicurezzaChiara

## Gap aperti
- restano asset e dataset demo non routati
- il repository puo' essere ulteriormente alleggerito in task futuri, ma fuori da questo perimetro bootstrap

## Punto di stato finale
`SC-BOOT-002` chiuso. Cleanup prudente completato con build preservata.
