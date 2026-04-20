# SicurezzaChiara - SC-CLEAN-002 / SC-CLEAN-003 Session Report

## Contesto
Sessione dedicata alla riduzione controllata del rumore demo in `model / controller / view / sidebar`, preservando topbar e UI reference page.

## Obiettivo
Lasciare il progetto piu' ordinato e coerente con uno stato bootstrap reale, senza impoverire la base UI utile del template.

## File e aree rimosse
- cartelle demo rimosse da `resources/js/Pages`:
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
  - dashboard demo secondarie (`analytics`, `blog`, `crm`, `crypto`, `ecommerce`, `job`, `nft`, `projects`)

## File aggiornati
- `app/Http/Controllers/VelzonRoutesController.php`
- `routes/web.php`
- `resources/js/Components/menu.vue`
- `resources/js/Pages/dashboard/index.vue`
- documentazione operativa e master progress

## File mantenuti volutamente
- `app/Models/User.php`
- auth Fortify / Jetstream
- `resources/js/Pages/Auth/*`
- `resources/js/Pages/Profile/*`
- `resources/js/Pages/sicurezzachiara/ui-reference.vue`
- `resources/js/Pages/PrivacyPolicy.vue`
- `resources/js/Pages/TermsOfService.vue`
- topbar e shell layout Velzon

## Motivazioni sintetiche
- topbar lasciata invariata per vincolo di task
- UI reference preservata come gold standard
- dashboard bootstrap mantenuta come entry point prodotto
- auth/profile mantenuti per solidita' applicativa
- route/controller/view demo rimossi perche' chiaramente fuori perimetro e fuorvianti

## Sidebar finale
- `Dashboard`
- `UI Reference`

## Verifiche
- `npm run build`: positivo
- `php artisan route:list` con PHP 8.2: positivo
- superficie route applicative ridotta a dashboard + UI reference

## Gap aperti
- asset statici demo non trattati in questo task
- dashboard ancora bootstrap e non workspace di dominio reale

## Punto di stato finale
`SC-CLEAN-002` e `SC-CLEAN-003` chiusi. Progetto razionalizzato in modo controllato, topbar invariata, UI reference integra e raggiungibile.
