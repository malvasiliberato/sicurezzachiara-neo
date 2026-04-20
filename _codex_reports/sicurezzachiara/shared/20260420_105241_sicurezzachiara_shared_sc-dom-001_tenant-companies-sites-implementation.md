# SicurezzaChiara - SC-DOM-001 - Tenant, utenti, aziende e sedi

## Contesto
Il repository parte da una base Velzon Laravel + Vue gia' bootstrapata e razionalizzata.
Sono gia' disponibili:
- UI reference page come gold standard visivo
- sidebar minima coerente
- topbar invariata
- piano dominio-first
- domain modeling fondativo

Questo task apre il primo step esecutivo reale del dominio, senza introdurre ancora motore rischio, lavoratori, mansioni, macchinari o luoghi.

## Obiettivo
Materializzare il primo blocco fondativo del contesto operativo:
- tenant
- membership utente al tenant
- aziende
- sedi

con relazioni, ownership, accesso minimo e UI operativa coerenti con Velzon.

## Stato iniziale
- il dominio applicativo non aveva ancora modelli dedicati oltre `User`
- il routing applicativo esponeva solo dashboard e UI reference
- il database non conteneva ancora tenant, aziende e sedi
- la sidebar non esponeva moduli di dominio reali

## Domain assumptions applicate
- il tenant e' il contenitore organizzativo principale del SaaS
- il tenant non coincide con la singola azienda cliente
- l'utente accede al tenant tramite membership
- l'ownership dei dati e l'autorizzazione utente restano distinte
- l'azienda appartiene al tenant
- la sede appartiene all'azienda
- `User + membership + role` e' sufficiente per questa fase
- serve un contesto tenant attivo leggero per rendere navigabile il dominio

## Schema implementato

### Entita' introdotte
- `Tenant`
- `TenantMembership`
- `Company`
- `CompanySite`

### Catena di ownership
- `users.current_tenant_id -> tenants.id`
- `tenant_memberships.tenant_id -> tenants.id`
- `tenant_memberships.user_id -> users.id`
- `companies.tenant_id -> tenants.id`
- `company_sites.company_id -> companies.id`

### Scelte chiave
- il tenant ha `owner_user_id` per distinguere ownership primaria e membership
- la membership ha `role` minimo (`owner`, predisposizione futura ad altri ruoli)
- il tenant corrente viene risolto in modo leggero tramite `CurrentTenantResolver`
- nuovi utenti registrati ricevono automaticamente un workspace tenant iniziale
- utenti esistenti senza tenant vengono bootstrapati tramite `TenantBootstrapSeeder`
- la sede principale viene mantenuta coerente azzerando le altre sedi `is_headquarters` della stessa azienda quando necessario

## Migrazioni create
- `2026_04_20_103000_create_tenants_table.php`
- `2026_04_20_103100_add_current_tenant_id_to_users_table.php`
- `2026_04_20_103200_create_tenant_memberships_table.php`
- `2026_04_20_103300_create_companies_table.php`
- `2026_04_20_103400_create_company_sites_table.php`

## Model e logica applicativa introdotti

### Model
- `app/Models/Tenant.php`
- `app/Models/TenantMembership.php`
- `app/Models/Company.php`
- `app/Models/CompanySite.php`
- aggiornato `app/Models/User.php`

### Support e bootstrap
- `app/Support/CurrentTenantResolver.php`
- `app/Actions/Tenant/CreateTenantWorkspace.php`
- aggiornato `app/Actions/Fortify/CreateNewUser.php`
- aggiornato `app/Http/Middleware/HandleInertiaRequests.php`

### Request validation
- `app/Http/Requests/StoreCompanyRequest.php`
- `app/Http/Requests/UpdateCompanyRequest.php`
- `app/Http/Requests/StoreCompanySiteRequest.php`
- `app/Http/Requests/UpdateCompanySiteRequest.php`

### Controller
- `app/Http/Controllers/CompanyController.php`
- `app/Http/Controllers/CompanySiteController.php`

## Rotte introdotte
- `GET /aziende`
- `GET /aziende/create`
- `POST /aziende`
- `GET /aziende/{company}`
- `GET /aziende/{company}/edit`
- `PUT|PATCH /aziende/{company}`
- `POST /aziende/{company}/sedi`
- `GET /aziende/{company}/sedi/{site}/modifica`
- `PUT /aziende/{company}/sedi/{site}`

## UI introdotta

### Sidebar
Aggiornata in modo minimo con voce:
- `Aziende`

Topbar non toccata.

### Pagine Vue introdotte
- `resources/js/Pages/sicurezzachiara/companies/Index.vue`
- `resources/js/Pages/sicurezzachiara/companies/Create.vue`
- `resources/js/Pages/sicurezzachiara/companies/Edit.vue`
- `resources/js/Pages/sicurezzachiara/companies/Show.vue`
- `resources/js/Pages/sicurezzachiara/company-sites/Edit.vue`
- partial:
  - `.../companies/Partials/CompanyForm.vue`
  - `.../companies/Partials/SiteForm.vue`

### Percorsi UI minimi disponibili
- elenco aziende: `/aziende`
- nuova azienda: `/aziende/create`
- dettaglio azienda: `/aziende/{id}`
- modifica azienda: `/aziende/{id}/edit`
- modifica sede: `/aziende/{company}/sedi/{site}/modifica`

### Coerenza UI
- layout Velzon preservato
- PageHeader e grammatica card/table/form coerenti con la UI reference
- nessun redesign introdotto
- topbar invariata

## Seeder e supporto locale
- `database/seeders/TenantBootstrapSeeder.php`
- `database/seeders/DatabaseSeeder.php` aggiornato

Uso:
- bootstrap automatico tenant per utenti esistenti senza membership
- nessun dato demo invasivo inserito

## Verifiche eseguite
- `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe vendor\bin\pint`
- `npm run build`
- `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan route:list`
- `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan migrate --force`
- `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan db:seed --class=TenantBootstrapSeeder --force`
- `C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test tests\Feature\SicurezzaChiaraTenantBootstrapTest.php tests\Feature\SicurezzaChiaraCompanyManagementTest.php`

## Test introdotti
- `tests/Feature/SicurezzaChiaraTenantBootstrapTest.php`
- `tests/Feature/SicurezzaChiaraCompanyManagementTest.php`

Copertura minima ottenuta:
- registrazione utente con bootstrap tenant automatico
- creazione azienda dentro il tenant corrente
- creazione sede dentro l'azienda del tenant corrente

## File coinvolti principali
- `app/Models/User.php`
- `app/Models/Tenant.php`
- `app/Models/TenantMembership.php`
- `app/Models/Company.php`
- `app/Models/CompanySite.php`
- `app/Support/CurrentTenantResolver.php`
- `app/Actions/Tenant/CreateTenantWorkspace.php`
- `app/Actions/Fortify/CreateNewUser.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Controllers/CompanyController.php`
- `app/Http/Controllers/CompanySiteController.php`
- `routes/web.php`
- `resources/js/Components/menu.vue`
- `resources/js/Pages/dashboard/index.vue`
- `resources/js/app.js`
- `database/migrations/*SC-DOM-001*`
- `database/seeders/TenantBootstrapSeeder.php`

## Rischi / gap aperti
- il contesto tenant attivo e' volutamente leggero e non offre ancora cambio tenant multi-workspace
- i ruoli membership restano minimi e non ancora tradotti in policy avanzate
- aziende e sedi non sono ancora agganciate a lavoratori ed esposizioni operative
- permessi piu' fini e audit trail restano rinviati
- il percorso `create/edit` delle resource routes resta di naming Laravel standard; la semantica applicativa resta comunque coerente

## Proposta del prossimo step
Aprire `SC-DOM-002` su:
- lavoratori
- appartenenza all'azienda
- sede prevalente
- base per esposizioni operative future

Subito dopo conviene affrontare `SC-DOM-003A/003B` per:
- cataloghi di mansioni, tipologie macchinari, tipologie luoghi
- istanze operative e relazioni di esposizione

## Stato finale
- `SC-DOM-001` implementato
- repository coerente con Velzon e UI reference
- nessun allargamento di perimetro a rischio, registri o DVR
- stato finale `PRE-DEPLOY / NO-DEPLOY`

