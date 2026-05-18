# SicurezzaChiara - runbook GitHub + Plesk

## Stato reale attuale

Questo repository non e' piu' in sola fase di preparazione deploy.

Stato confermato:
- `main` contiene gia' il perimetro MVP pubblicato
- `origin/main` e' allineato
- `staging.sicurezzachiara.it` e' online
- `area.sicurezzachiara.it` e' online
- `area.sicurezzachiara.it` e' l'endpoint principale MVP
- i database remoti sono gia' stati creati e riallineati dal locale

Questo documento non descrive piu' un bootstrap teorico, ma il runbook operativo minimo da usare per rendere ripetibile il deploy.

## Obiettivo del runbook

Rendere governabile il deploy Git-based su Plesk senza:
- introdurre deploy automatici distruttivi
- eseguire migration non confermate
- affidare la creazione del primo admin a password hardcoded nel repository

## Branch e sorgente di deploy

- branch applicativo di riferimento: `main`
- document root attesa sul server: `repo/public`
- sorgente applicativa attesa lato Plesk:
  - clone Git del repository nel path del sottodominio

## Ambienti attivi

### staging
- URL: `https://staging.sicurezzachiara.it`
- uso: verifica tecnica e prove controllate
- `APP_ENV` consigliato: `staging`
- `APP_DEBUG` consigliato: `false`

### area
- URL: `https://area.sicurezzachiara.it`
- uso: endpoint principale MVP
- `APP_ENV` consigliato: `production`
- `APP_DEBUG` consigliato: `false`

## Prerequisiti server

- PHP 8.2+ compatibile Laravel 12
- Composer disponibile
- Node.js e npm disponibili
- PostgreSQL disponibile
- Git extension Plesk o shell server disponibile
- certificato SSL valido
- redirect HTTPS attivo

## Strategia deploy raccomandata

Strategia raccomandata:
- Git deploy Plesk con build lato server

Motivazione:
- evita di versionare `public/build`
- e' coerente con lo stato reale usato negli ambienti online
- mantiene piu' semplice il ciclo `pull -> install -> build -> cache`

## Script post-deploy

File:
- `scripts/deploy/plesk-post-deploy.sh`

Uso raccomandato:

```bash
PATH="/opt/plesk/php/8.3/bin:/usr/local/bin:$PATH" COMPOSER_ALLOW_SUPERUSER=1 NPM_CI_FLAGS="--legacy-peer-deps" RUN_MIGRATIONS=0 bash ./scripts/deploy/plesk-post-deploy.sh .
```

### Cosa fa lo script

- `artisan optimize:clear`
- `composer install --no-dev --prefer-dist --optimize-autoloader`
- `npm ci --legacy-peer-deps`
- `npm run build`
- `storage:link` se necessario
- cache Laravel:
  - `config:cache`
  - `route:cache`
  - `view:cache`
  - `event:cache`
- `queue:restart` non bloccante

### Cosa NON fa di default

- non esegue migration remote automaticamente
- non esegue seed automaticamente
- non crea admin automaticamente

Per eseguire le migration nello script va passato esplicitamente:

```bash
RUN_MIGRATIONS=1
```

Per il primo staging/area e per i deploy controllati, la procedura raccomandata resta:
- post-deploy script senza migration
- verifica `.env`
- backup DB
- `php artisan migrate --force` eseguito manualmente e consapevolmente

## Additional Deployment Actions

File:
- `deployment/plesk/additional-deploy-actions.txt`

Valore raccomandato:

```bash
NPM_CI_FLAGS="--legacy-peer-deps" RUN_MIGRATIONS=0 bash ./scripts/deploy/plesk-post-deploy.sh .
```

Questo valore e' adatto sia a staging sia ad area come default prudente.

Nota operativa confermata sul server reale:
- il `PATH` di default dell'utente deploy non espone sempre `php`
- il fallback in script cerca automaticamente `/opt/plesk/php/8.3/bin/php`
- resta comunque consigliato passare il `PATH` esplicito anche nelle additional deploy actions

## Procedura deploy consigliata

### 1. Verifica pre-deploy

- `git rev-parse HEAD`
- `php artisan test` o suite mirata
- `npm run build`
- verifica file `.env`
- verifica DB target corretto
- backup DB target

### 2. Aggiornamento codice remoto

Opzione A:
- pull Git tramite Plesk

Opzione B:
- shell remota nel path `repo`
- `git fetch origin`
- `git checkout main`
- `git pull --ff-only origin main`

### 3. Post-deploy applicativo

Eseguire:

```bash
PATH="/opt/plesk/php/8.3/bin:/usr/local/bin:$PATH" COMPOSER_ALLOW_SUPERUSER=1 NPM_CI_FLAGS="--legacy-peer-deps" RUN_MIGRATIONS=0 bash ./scripts/deploy/plesk-post-deploy.sh .
```

### 4. Migration controllata

Solo dopo backup DB e verifica `.env`:

```bash
php artisan migrate --force
```

Non usare migration automatiche in `area` senza:
- backup DB
- verifica `.env`
- decisione esplicita di finestra operativa

### 5. Seed controllato

#### Lookup e cataloghi core

Sicuri come base:
- `Ateco2025Seeder`
- `ComuniElencoSeeder`
- `SicurezzaChiaraCoreJobRolesSeeder`
- `SicurezzaChiaraCoreEquipmentTypesSeeder`
- `SicurezzaChiaraCoreWorkplaceTypesSeeder`
- `SicurezzaChiaraCoreRiskCategoriesSeeder`
- `SicurezzaChiaraCoreRisksSeeder`
- `SicurezzaChiaraCoreSourceRiskMappingsSeeder`

#### Seed baseline/showcase

- `SicurezzaChiaraBaselineSeeder`: utile per ambiente locale o staging guidato
- `SicurezzaChiaraShowcaseSeeder`: utile per demo/showcase controllato

Classificazione operativa:
- baseline/core seed = sicuro e necessario per ambienti locali o staging controllato
- system admin seed = manuale e protetto da env dedicate
- showcase seed = solo demo/staging

Questi seed non vanno lanciati automaticamente in area senza decisione esplicita.

#### DatabaseSeeder

`DatabaseSeeder` e' volutamente prudente:
- carica lookup statici e cataloghi core
- non crea piu' automaticamente il system admin
- in `local` puo' caricare la baseline
- non lancia mai automaticamente il showcase seed

## Strategia primo admin

Strada raccomandata:
- comando esplicito, non password hardcoded nel repository

Comando:

```bash
php artisan sicurezzachiara:ensure-system-admin email@example.com --name="Nome Admin" --password="PASSWORD_TEMPORANEA"
```

Alternativa consentita:
- `SystemAdminSeeder` solo se il server espone esplicitamente:
  - `SC_SYSTEM_ADMIN_EMAIL`
  - `SC_SYSTEM_ADMIN_NAME`
  - `SC_SYSTEM_ADMIN_PASSWORD`

Se le variabili non sono presenti, il seeder salta l'operazione.

Classificazione ufficiale:
- baseline/core seed = sicuro per locale e staging controllato
- system admin seed = manuale e protetto da env
- showcase seed = solo demo/staging

## Variabili `.env` rilevanti

Minime da verificare:
- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `DB_CONNECTION=pgsql`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `CACHE_DRIVER`
- `SESSION_DRIVER`
- `QUEUE_CONNECTION`

Variabili opzionali per bootstrap admin:
- `SC_SYSTEM_ADMIN_EMAIL`
- `SC_SYSTEM_ADMIN_NAME`
- `SC_SYSTEM_ADMIN_PASSWORD`

Variabili opzionali per seed demo:
- `SC_BASELINE_USER_PASSWORD`
- `SC_SHOWCASE_USER_PASSWORD`

Nessun segreto deve essere committato nel repository.

## Warning noti

- il progetto usa una combinazione frontend che richiede:
  - `npm ci --legacy-peer-deps`
- la build puo' mostrare il warning noto su:
  - `lottie-web` / `eval`
- il warning non e' attualmente bloccante

## Backup e rollback

### Backup minimo prima di ogni migration remota

- dump DB
- copia del file `.env`
- annotazione commit Git deployato

### Stop condition

Fermarsi se:
- `composer install` fallisce
- `npm ci --legacy-peer-deps` fallisce
- `npm run build` fallisce
- login applicativo non funziona
- `php artisan migrate --force` fallisce

### Rollback minimo

- ripristino DB
- ritorno al commit precedente stabile
- ripristino `.env`
- riesecuzione cache Laravel

## Cosa resta da decidere

- se mantenere il dataset showcase attuale come base operativa di area
- se ripulire o alleggerire i tenant locali clonati in remoto
- quando promuovere eventualmente il dominio root

## Stato finale

Runbook riallineato allo stato reale:
- deploy gia' avvenuti su staging e area
- procedura resa piu' prudente e ripetibile
- nessun deploy eseguito da questo documento
