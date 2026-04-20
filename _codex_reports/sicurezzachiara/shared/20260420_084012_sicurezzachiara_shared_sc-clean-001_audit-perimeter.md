# SicurezzaChiara - SC-CLEAN-001 Audit Perimeter

## Contesto
Audit prudente del perimetro `model / controller / view / sidebar` dopo la creazione della UI reference page, da preservare come gold standard visivo e operativo.

## Obiettivo
Distinguere con prudenza cio' che e' utile, cio' che e' demo/non pertinente, cio' che va nascosto o rinviato e le dipendenze che rendono rischiosa una rimozione immediata.

## Censimento sintetico

### Model
- presenti: `User.php`
- esito: nessun model demo da rimuovere

### Controller
- presenti: `Controller.php`, `VelzonRoutesController.php`
- esito: `VelzonRoutesController` contiene la quasi totalita' del rumore demo

### View / Pages
- presenti numerose cartelle demo sotto `resources/js/Pages`
- aree utili da mantenere:
  - `Auth`
  - `Profile`
  - `sicurezzachiara`
  - `dashboard/index.vue` come dashboard bootstrap
  - `PrivacyPolicy.vue`
  - `TermsOfService.vue`
- aree demo fuori perimetro:
  - `advance-ui`
  - `API`
  - `apps`
  - `auth-pages`
  - `calendar`
  - `charts`
  - `forms`
  - `icons`
  - `landing`
  - `maps`
  - `pages`
  - `tables`
  - `ui`
  - `widgets`
  - dashboard demo secondarie

### Sidebar
- stato iniziale: menu demo esteso e fuorviante
- criticita': troppe voci non coerenti con un bootstrap prodotto reale

## Classificazione

### Da rimuovere
- route demo del template
- metodi demo in `VelzonRoutesController`
- cartelle `resources/js/Pages` non piu' coerenti o non necessarie

### Da mantenere
- `User.php`
- `Controller.php`
- auth/profile Jetstream/Fortify
- topbar
- shell layout
- `dashboard/index.vue`
- `resources/js/Pages/sicurezzachiara/ui-reference.vue`
- privacy/terms

### Da disattivare o nascondere
- sidebar demo estesa

### Da rinviare
- pruning di asset statici non toccati da questo task
- eventuale ulteriore riduzione di componenti shared ambigui

## Dipendenze tecniche rilevate
- auth/profile Jetstream non vanno toccati
- topbar va lasciata invariata
- la UI reference page va preservata e resa sempre raggiungibile
- routing e controller demo sono fortemente collegati alle pagine demo e possono essere ridotti insieme in modo sicuro

## Piano prudente di razionalizzazione
1. ridurre `VelzonRoutesController` e `routes/web.php` al minimo utile
2. mantenere dashboard bootstrap + UI reference + auth/profile
3. rimuovere le view demo non piu' raggiungibili
4. ridurre la sidebar a una struttura minima di prodotto

## Punto di stato finale
`SC-CLEAN-001` chiuso. Audit completato con piano prudente di razionalizzazione.
