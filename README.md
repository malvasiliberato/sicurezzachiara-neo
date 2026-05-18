# SicurezzaChiara

Piattaforma SaaS per consulenti della sicurezza costruita su stack Laravel + Vue + PostgreSQL, con base UI Velzon preservata come shell applicativa.

## Stato del progetto
Il repository non e' una demo generica del template:
- base Velzon preservata come linguaggio UI
- UI reference page interna come gold standard visivo
- primo slice di dominio gia' implementato:
  - tenant
  - membership utenti
  - aziende
  - sedi

## Stack
- PHP 8.2+
- Laravel 12
- Vue 3 + Inertia
- PostgreSQL
- Vite

## Setup locale

### 1. Dipendenze
```powershell
composer install
npm ci --legacy-peer-deps
```

### 2. Ambiente
```powershell
Copy-Item .env.example .env
```

Aggiornare poi almeno:
- `APP_URL`
- `DB_*`
- `MAIL_*`

### 3. Chiave applicativa e database
```powershell
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan key:generate
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan migrate
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan db:seed
```

Il `DatabaseSeeder` popola anche i cataloghi statici versionati:
- `database/data/ateco_2025.csv`
- `database/data/comuni_elenco.csv`

Per rieseguire solo il caricamento dei due cataloghi statici:
```powershell
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan db:seed --class=Database\\Seeders\\Ateco2025Seeder
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan db:seed --class=Database\\Seeders\\ComuniElencoSeeder
```

Per popolare anche il dataset baseline locale:
```powershell
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan db:seed --class=Database\\Seeders\\SicurezzaChiaraBaselineSeeder
```

Note:
- `SicurezzaChiaraBaselineSeeder` e' seed di baseline/core per ambiente locale o staging controllato
- i suoi utenti demo non hanno password hardcoded; se serve una password nota, impostare `SC_BASELINE_USER_PASSWORD` in `.env`

Per il dataset showcase:
```powershell
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan db:seed --class=Database\\Seeders\\SicurezzaChiaraShowcaseSeeder
```

Note:
- `SicurezzaChiaraShowcaseSeeder` e' `demo/staging only`
- non deve essere eseguito automaticamente in `area` o in produzione
- i suoi utenti demo non hanno password hardcoded; se serve una password nota, impostare `SC_SHOWCASE_USER_PASSWORD` in `.env`

Per creare o aggiornare il primo admin senza password nel repository:
```powershell
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan sicurezzachiara:ensure-system-admin email@example.com --name="Nome Admin" --password="PASSWORD_TEMPORANEA"
```

Per usare il seeder admin in modo manuale e protetto da env:
- impostare `SC_SYSTEM_ADMIN_EMAIL`
- impostare `SC_SYSTEM_ADMIN_NAME`
- impostare `SC_SYSTEM_ADMIN_PASSWORD`
- eseguire:

```powershell
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan db:seed --class=Database\\Seeders\\SystemAdminSeeder
```

Note:
- `SystemAdminSeeder` non parte automaticamente dal `DatabaseSeeder`
- non contiene password hardcoded
- senza env dedicate il seeder salta l'operazione

### 4. Frontend
```powershell
npm run build
```

Nota operativa:
- il progetto richiede `npm ci --legacy-peer-deps` anche negli ambienti Plesk gia' online

## Moduli gia' disponibili
- Dashboard bootstrap: `/`
- UI Reference: `/sicurezzachiara/ui-reference`
- Aziende: `/aziende`

## Git e repository remoto

### Branch base
- branch predefinito consigliato: `main`

### Regole base
- non versionare `.env`
- non versionare `vendor/`, `node_modules/`, `public/build/`
- non versionare segreti, token o dump locali

### Repository remoto consigliato
Per evitare collisioni con l'archivio storico `malvasiliberato/sicurezzachiara`, creare un nuovo repository separato, ad esempio:
- `malvasiliberato/sicurezzachiara-platform`

Oppure una variante equivalente, purche' distinta dal repository storico.

## Deploy futuro via Plesk + Git

### Obiettivo
Il progetto e' predisposto per un deploy Git-based su Plesk ed e' gia' online su:
- `https://staging.sicurezzachiara.it`
- `https://area.sicurezzachiara.it`

L'endpoint principale MVP e':
- `https://area.sicurezzachiara.it`

Il runbook operativo aggiornato e' qui:
- [docs/deploy/GITHUB_AND_PLESK.md](docs/deploy/GITHUB_AND_PLESK.md)

Il deploy resta governato da:
- script di post-deploy versionato
- istruzioni operative dedicate
- CI GitHub per validare push e PR

### File utili
- [docs/deploy/GITHUB_AND_PLESK.md](docs/deploy/GITHUB_AND_PLESK.md)
- [scripts/deploy/plesk-post-deploy.sh](scripts/deploy/plesk-post-deploy.sh)
- [deployment/plesk/additional-deploy-actions.txt](deployment/plesk/additional-deploy-actions.txt)
- [app/Console/Commands/EnsureSystemAdminUserCommand.php](app/Console/Commands/EnsureSystemAdminUserCommand.php)

## Verifiche consigliate prima del push
```powershell
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe vendor\bin\pint
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe artisan test
npm run build
```

## Note operative
- il `php` di shell puo' puntare a una versione diversa: usare PHP 8.2 esplicito
- la topbar Velzon va mantenuta invariata salvo necessita' motivate
- il deploy non fa parte dei task applicativi ordinari: si chiude sempre in `PRE-DEPLOY / NO-DEPLOY` finche' non richiesto esplicitamente
