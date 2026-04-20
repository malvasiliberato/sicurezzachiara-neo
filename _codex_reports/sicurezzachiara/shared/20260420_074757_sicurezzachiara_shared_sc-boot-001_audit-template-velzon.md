# SC-BOOT-001 - Audit controllato del template Velzon

## Contesto
Repository bootstrap `tpl.default.vue` basato su Velzon `default` in stack Laravel + Vue + Inertia + PostgreSQL.
Il repository e' stato inizialmente allineato per installazione e build, ma mantiene ancora la struttura ampia del template demo.

## Obiettivo
Distinguere in modo prudente e tracciabile:

- infrastruttura applicativa da mantenere
- componenti/layout/shared da mantenere
- demo pages da rimuovere o disattivare
- sample content, menu, route e widget da ridurre
- asset demo da eliminare o lasciare temporaneamente
- aree ambigue da non toccare senza necessita'

## Evidenze raccolte

### Struttura generale
- stack Laravel standard con Inertia + Vue + Jetstream/Fortify
- entry frontend principale in `resources/js/app.js`
- layout system principale in `resources/js/Layouts`
- shared components principali in `resources/js/Components`
- store layout in `resources/js/state`
- theme SCSS in `resources/scss`

### Densita' demo
- pagine Vue censite in `resources/js/Pages`: `275`
- cartelle piu' dense:
  - `apps`: `57` file
  - `dashboard`: `57` file
  - `charts`: `41` file
  - `ui`: `24` file
  - `auth-pages`: `21` file
  - `pages`: `16` file
- assets immagine censiti in `resources/images`: `916`

### Routing applicativo
- `routes/web.php` espone una mappa demo estesa tramite `VelzonRoutesController`
- il routing demo copre dashboard, apps, ecommerce, crypto, invoices, jobs, charts, forms, icons, maps, widgets, auth sample, landing e pagine showcase
- la home `/` punta ancora a `dashboard/ecommerce/index`

### Shell e shared riusabili
Da mantenere:
- `resources/js/app.js`
- `resources/js/Layouts/main.vue`
- `resources/js/Layouts/vertical.vue`
- `resources/js/Layouts/horizontal.vue`
- `resources/js/Layouts/twocolumn.vue`
- `resources/js/Components/nav-bar.vue`
- `resources/js/Components/menu.vue`
- `resources/js/Components/footer.vue`
- `resources/js/Components/page-header.vue`
- `resources/js/state/modules/layout.js`
- pipeline SCSS Velzon in `resources/scss`
- configurazione Vite e Blade Inertia

Motivazione:
- costituiscono la shell applicativa, il comportamento layout e il linguaggio visivo Velzon

### Rumore demo ad alta probabilita'
Da ridurre/rimuovere:
- dashboard multipli demo
- moduli demo `ecommerce`, `crypto`, `nft`, `jobs`, `blog`, `widgets`, `charts`, `maps`, `icons`
- pagine `ui`, `forms`, `tables`, `advance-ui` usate come showcase del template
- landing demo
- sample auth pages alternative non necessarie al prodotto
- contenuti demo in topbar e sidebar:
  - cart mock
  - notifiche showcase
  - voci menu molto estese
  - greeting e-commerce

### Aree ambigue
Da non tagliare in modo aggressivo:
- `resources/js/Components/right-bar.vue`
  - fortemente demo oriented
  - ma intrecciato al comportamento layout e ai settaggi UI
- asset immagini del tema
  - molti sono demo puri
  - alcuni supportano layout, placeholder o pattern Velzon
- pagine auth/profile di Jetstream
  - non sono puro showcase
  - vanno valutate in funzione della base applicativa

## Classificazione prudente

### Keep
- infrastruttura Laravel/Inertia/Vite
- layout shared e store layout
- topbar/sidebar/footer/page-header
- componenti form/auth shared di Jetstream
- SCSS, fonts, plugin styles e asset pipeline

### Remove or neutralize
- route demo e controller methods non pertinenti al bootstrap
- navigation sample massiva
- dashboard e-commerce come home iniziale
- showcase UI/Charts/Maps/Icons/Widgets
- mock content topbar/cart/notifications non prodotto

### Postpone / ambiguous
- right sidebar configuratore
- subset di assets immagine
- alcune pagine auth/profile legate al runtime base

## Criterio operativo di cleanup proposto
Cleanup prudente, non aggressivo:

1. ridurre prima menu, home e route visibili
2. mantenere temporaneamente i file ambigui se non bloccano chiarezza o build
3. rimuovere in blocchi coerenti di demo pages solo dopo aver ridotto i loro entrypoint
4. preservare sempre layout, topbar/sidebar, store layout, SCSS e pipeline asset
5. documentare cio' che resta per prudenza, invece di eliminarlo senza evidenza

## Decisioni operative
- `SC-BOOT-001` puo' considerarsi chiuso
- il cleanup deve partire da route/menu/home, non da una bonifica cieca degli assets
- la home va sostituita con una dashboard/workspace sobria SicurezzaChiara
- la navigation va ridotta a una base minima prodotto, mantenendo Velzon come shell

## Gap aperti
- right-bar ancora da decidere tra mantenimento minimo e disattivazione
- molti assets demo non ancora classificati file per file
- topbar contiene ancora diversi sample data sources non prodotto

## Punto di stato finale
- Step ID: `SC-BOOT-001`
- Esito: `GO`
- Stato aggiornato: `chiuso`
- Done condition: raggiunta
- Prossima ripartenza consigliata: `SC-BOOT-002`
