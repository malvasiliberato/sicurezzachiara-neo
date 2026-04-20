# AI_CONTEXT.md

## SicurezzaChiara - contesto operativo AI

### Stato del repository
Repository bootstrap basato su template Velzon `default` in stack Laravel + Vue + Inertia.
Il progetto non e' piu' una demo grezza: la base e' stata bootstrapata, razionalizzata e ora contiene il primo slice esecutivo reale del dominio.

La base attuale presenta:
- topbar Velzon invariata
- sidebar minima con accesso a dashboard, aziende e UI reference
- dashboard bootstrap interna volutamente minima
- reference page interna UI/UX da considerare gold standard
- route applicative con dashboard, UI reference e modulo aziende
- auth, profile, privacy policy e terms ancora presenti
- database PostgreSQL attivo

### Obiettivo della fase corrente
Predisporre correttamente il progetto per repository Git remoto su GitHub e per futuro autodeploy via Plesk/Git.

Questa fase:
- audit operativo del repository
- inizializzazione Git locale
- hardening documentale per GitHub e Plesk
- non riapre cleanup estesi del template
- non ridisegna la UI
- non tocca la topbar

### Principi di dominio da tenere presenti
- SicurezzaChiara governa il rischio aziendale in modo continuo
- il rischio emerge da mansioni, macchinari e luoghi di lavoro
- le misure includono visite mediche, formazione, DPI e misure organizzative/tecniche
- dashboard, registri e scadenze devono derivare dal motore di rischio e non essere silos separati
- il DVR e' un output vivo derivato dai dati del sistema
- il consulente deve poter integrare, rafforzare, ridurre, confermare o personalizzare il profilo rischio
- il sistema deve distinguere conoscenza core, contesto aziendale, valutazione professionale e stato operativo finale

### Assunzioni di prodotto forti
- target principale: consulenti / RSPP che gestiscono piu' aziende
- target secondario: aziende con esperienza piu' semplice e consultiva
- multi-tenant base necessario gia' nel disegno MVP
- il profilo rischio e' il cuore del dominio
- il primo modulo reale va scelto per valore fondativo, non per impatto visuale
- i cataloghi standard non possono bloccare la valutazione professionale del consulente

### Principi permanenti UI
- Velzon-first
- minimum visual change / maximum functional clarity
- nessun linguaggio visivo alieno rispetto al template
- topbar invariata per default
- la UI reference e' il gold standard per i task futuri

### Distinzione necessaria
Dominio:
- si progetta il nuovo prodotto partendo dal flusso operativo reale del consulente
- le decisioni vanno prese in funzione della tenuta del profilo rischio, non della comodita' di demo o CRUD isolati

Template e storico:
- Velzon resta base visiva
- il repository non va trattato come semplice template da ripulire di continuo
- l'esperienza storica del precedente progetto e' utile come apprendimento di dominio
- il nuovo progetto non va appiattito su vecchie strutture tecniche o tassonomie contingenti

### Stato operativo attuale
- `SC-UI-001` chiuso
- `SC-UI-002` chiuso
- `SC-UI-003` chiuso
- `SC-CLEAN-001` chiuso
- `SC-CLEAN-002` chiuso
- `SC-CLEAN-003` chiuso
- `SC-PLAN-001` chiuso
- `SC-DMODEL-001` chiuso
- `SC-DOM-001` chiuso
- `SC-OPS-001` chiuso
- fase corrente completata: setup repository/deploy

### Verifiche recenti
- `npm run build`: esito positivo
- `php artisan route:list`: esito positivo usando `C:\\laragon\\bin\\php\\php-8.2.0-Win32-vs16-x64\\php.exe`
- `php artisan migrate --force`: esito positivo
- `php artisan db:seed --class=TenantBootstrapSeeder --force`: esito positivo
- `php artisan test tests\\Feature\\SicurezzaChiaraTenantBootstrapTest.php tests\\Feature\\SicurezzaChiaraCompanyManagementTest.php`: esito positivo
- `git init -b main`: esito positivo
- `git remote add origin https://github.com/malvasiliberato/sicurezzachiara-neo.git`: esito positivo lato locale
- `bash -n scripts/deploy/plesk-post-deploy.sh`: esito positivo
- nota ambiente: il `php` di default di shell punta a PHP 8.1.10 e non va usato per questo repository

### Done condition della fase SC-OPS-001
La fase `SC-OPS-001` e' completa quando:
- il progetto e' un repository Git locale su `main`
- il remote target e' predisposto verso un repository nuovo e separato
- `.env.example`, README e documentazione deploy sono coerenti con lo stack reale
- esiste materiale operativo per Plesk Git deploy
- il blocco residuo sulla creazione del remoto GitHub e' documentato in modo esplicito
- documentazione e report obbligatori sono aggiornati
- lo stato finale e' chiaramente `PRE-DEPLOY / NO-DEPLOY`

### Regole documentali
Aggiornare sempre:
- `AGENTS.md`
- `AI_CONTEXT.md`
- `sicurezzachiara_master_progress.md`

Creare report in:
- `_codex_reports/sicurezzachiara/shared`
- `_codex_reports/sicurezzachiara/deploy`

### Stop condition
Fermarsi prima di qualsiasi deploy o rilascio.
