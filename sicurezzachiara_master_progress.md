# SicurezzaChiara Master Progress

## Scopo
Plancia operativa del bootstrap, della razionalizzazione controllata, della pianificazione e dell'esecuzione fondativa del nuovo SicurezzaChiara su base Velzon Laravel + Vue.

## Legenda stati
- `backlog`
- `ready`
- `in_corso`
- `bloccato`
- `validato`
- `chiuso`
- `no_go`

## Principi permanenti
- distinguere sempre dominio, architettura applicativa e template
- preservare Velzon come base UI
- applicare `minimum visual change / maximum functional clarity`
- preservare la UI reference come gold standard
- topbar invariata per default
- stop prima del deploy

## Principi di dominio confermati
- SicurezzaChiara e' un SaaS per consulenti della sicurezza
- il rischio viene governato a partire da `mansioni`, `macchinari`, `luoghi di lavoro`
- le misure chiave includono `visite mediche`, `formazione`, `DPI`
- dashboard, registri e scadenze devono derivare dal profilo rischio
- il DVR e' un output vivo del sistema e non il centro del prodotto

## Stato del repository assunto come base reale
- template Velzon preservato come base UI
- UI reference page presente e da preservare
- sidebar minima gia' razionalizzata e ora estesa al modulo aziende
- topbar volutamente invariata
- dashboard bootstrap presente ma minima
- il dominio applicativo contiene il primo slice esecutivo `SC-DOM-001`

## Step operativi chiusi

### SC-UI-001 - Audit leggero dei pattern utili del template
- Stato: `chiuso`
- Esito sintetico: pattern UI Velzon classificati e documentati

### SC-UI-002 - Creazione pagina interna di riferimento UI/UX SicurezzaChiara
- Stato: `chiuso`
- Esito sintetico: creata la reference page `/sicurezzachiara/ui-reference`

### SC-UI-003 - Hardening leggero della reference page
- Stato: `chiuso`
- Esito sintetico: pagina resa navigabile, integrata e verificata

### SC-CLEAN-001 - Audit del perimetro da razionalizzare
- Stato: `chiuso`
- Tipo: `audit`
- Obiettivo: classificare model, controller, view e sidebar in keep/remove/hide/defer
- Esito sintetico:
  - model quasi privo di rumore
  - controller e view demo concentravano il rumore principale
  - sidebar demo risultava il principale elemento fuorviante lato UX

### SC-CLEAN-002 - Pulizia controllata di model, controller e view
- Stato: `chiuso`
- Tipo: `implementazione`
- Obiettivo: ridurre il rumore demo del perimetro applicativo senza rompere la base utile
- Esito sintetico:
  - `VelzonRoutesController` semplificato
  - route applicative ridotte a dashboard + UI reference
  - rimosse le principali directory demo sotto `resources/js/Pages`

### SC-CLEAN-003 - Razionalizzazione della sidebar
- Stato: `chiuso`
- Tipo: `hardening`
- Obiettivo: lasciare una sidebar minima, sobria e coerente con lo stato bootstrap del prodotto
- Esito sintetico:
  - sidebar ridotta a `Dashboard` e `UI Reference`
  - topbar lasciata invariata

### SC-PLAN-001 - Programma di implementazione dominio-first
- Stato: `chiuso`
- Tipo: `planning`
- Obiettivo: definire fasi, moduli, dipendenze, MVP e ordine corretto di implementazione del nuovo SicurezzaChiara
- Esito sintetico:
  - definita una sequenza centrata su fondamenta tenant + anagrafiche dominio + motore profilo rischio
  - esclusa l'idea di partire da dashboard, scadenze o DVR come primi moduli
  - chiarito il perimetro rigoroso dell'MVP e dei rinvii di fase 2/3

### SC-DMODEL-001 - Affinamento del modello di dominio fondativo
- Stato: `chiuso`
- Tipo: `planning`
- Obiettivo: congelare il modello concettuale di tenant, aziende, sedi, lavoratori, mansioni, macchinari e luoghi prima dell'implementazione
- Esito sintetico:
  - separati chiaramente core standard, contesto aziendale, valutazione professionale e stato operativo finale
  - chiarita la distinzione tra cataloghi e istanze operative delle sorgenti del rischio
  - raffinati i primi step esecutivi per ridurre il rischio di rifacimenti sul modello fondativo

### SC-DOM-001 - Tenant, utenti, aziende e sedi
- Stato: `chiuso`
- Tipo: `implementazione`
- Obiettivo: materializzare il primo blocco fondativo del contesto operativo con tenant, membership utente, aziende e sedi
- Perimetro incluso:
  - tenant
  - membership utente al tenant
  - ruolo base membership
  - aziende
  - sedi
  - ownership `tenant -> aziende -> sedi`
  - migrazioni
  - model Eloquent
  - route e UI minima operative
- Perimetro escluso:
  - topbar
  - motore rischio
  - lavoratori
  - mansioni
  - macchinari
  - luoghi
  - registri
  - DVR
  - ACL avanzate
  - notifiche
  - cleanup template
- Done condition:
  - schema persistente creato e migrato
  - bootstrap tenant utente funzionante
  - aziende e sedi gestibili in UI
  - sidebar aggiornata in modo minimo
  - build e test mirati positivi
- Esito sintetico:
  - introdotti `Tenant`, `TenantMembership`, `Company`, `CompanySite`
  - aggiunto contesto tenant corrente leggero e bootstrap automatico per utenti nuovi/esistenti
  - attivate pagine aziende e gestione minima sedi coerenti con Velzon

### SC-OPS-001 - Audit operativo repository, GitHub e Plesk
- Stato: `chiuso`
- Tipo: `ops`
- Obiettivo: predisporre il progetto per repository Git remoto nuovo e per futuro deploy automatico via Plesk/Git
- Perimetro incluso:
  - audit Git locale
  - inizializzazione repository
  - remote target verso archivio nuovo
  - README e `.env.example`
  - documentazione GitHub/Plesk
  - workflow CI GitHub
  - script e action file per Plesk deploy
- Perimetro escluso:
  - deploy effettivo
  - push remoto
  - creazione materiale di business
  - modifiche dominio applicativo
- Done condition:
  - repository Git locale attivo
  - branch `main` attivo
  - remote `origin` impostato verso `sicurezzachiara-neo`
  - documentazione operativa pronta
  - blocco residuo sulla creazione remota esplicitato
- Esito sintetico:
  - repository Git locale inizializzato su `main`
  - `origin` predisposto verso `https://github.com/malvasiliberato/sicurezzachiara-neo.git`
  - aggiunti README progetto, security policy, Dependabot, CI GitHub e materiale Plesk
  - creazione effettiva del repository GitHub non eseguibile in questo ambiente per mancanza di client/API autenticati alla creazione repo

## Elementi mantenuti volutamente
- `app/Models/User.php`
- auth Fortify / Jetstream
- profile Jetstream
- `PrivacyPolicy.vue`
- `TermsOfService.vue`
- topbar e shell layout Velzon
- UI reference page
- dashboard bootstrap minima

## Gap aperti
- il modello dati di dominio e' avviato ma non ancora esteso oltre il contesto fondativo
- la tassonomia del catalogo rischi e delle misure va ancora formalizzata nei prossimi step esecutivi
- restano da tradurre in schema persistente le entita' di override e stato finale del rischio
- non esiste ancora la modellazione di lavoratori ed esposizioni operative
- il comando `php artisan` richiede PHP 8.2 esplicito finche' il PATH non viene corretto
- il repository GitHub remoto `sicurezzachiara-neo` va ancora creato lato GitHub prima del primo push

## Prossima ripartenza consigliata
Prima del push iniziale:
- creare su GitHub il repository `malvasiliberato/sicurezzachiara-neo`
- eseguire il primo push del branch `main`
- configurare in Plesk il repository remoto e le additional deployment actions

Poi aprire `SC-DOM-002` sul perimetro:
- lavoratori
- appartenenza all'azienda
- sede prevalente
- base per esposizioni operative future

Subito dopo conviene affrontare `SC-DOM-003A/003B` per cataloghi e istanze operative di mansioni, macchinari e luoghi.
