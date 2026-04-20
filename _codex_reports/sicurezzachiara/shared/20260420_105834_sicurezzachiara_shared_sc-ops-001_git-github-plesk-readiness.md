# SicurezzaChiara - SC-OPS-001 - Audit operativo Git, GitHub e Plesk

## Contesto
Il progetto SicurezzaChiara e' gia' stato bootstrapato a livello UI e dominio fondativo.
Questa fase non riguarda il dominio applicativo ma la predisposizione del repository per:
- versionamento Git corretto
- futuro remoto GitHub
- compatibilita' con deploy automatico via Plesk/Git

Vincolo esplicito:
- non usare `malvasiliberato/sicurezzachiara`
- predisporre un archivio nuovo e separato

## Obiettivo
Lasciare il progetto in stato repository-ready e deploy-ready, con setup pulito, sicuro e documentato.

## Stato iniziale auditato
- assenza di directory `.git`
- `.gitignore` Laravel gia' corretto
- `.env` escluso dal versionamento
- `.gitattributes` gia' presente
- `README.md` ancora generico Laravel
- `.env.example` non allineato allo stack reale PostgreSQL
- nessuna documentazione GitHub/Plesk dedicata
- nessun workflow CI GitHub
- nessuno script di post-deploy Plesk

## Decisioni operative adottate
- inizializzare il repository locale su branch `main`
- predisporre `origin` verso `https://github.com/malvasiliberato/sicurezzachiara-neo.git`
- non toccare il repository storico `malvasiliberato/sicurezzachiara`
- mantenere `public/build` fuori da Git e assumere build lato server o pipeline separata
- documentare esplicitamente il blocco residuo sulla creazione remota del repository

## Setup Git locale eseguito
- `git init -b main`
- `git remote add origin https://github.com/malvasiliberato/sicurezzachiara-neo.git`

Esito:
- repository locale Git creato
- branch corrente `main`
- remote `origin` configurato verso il nuovo archivio target

## File introdotti o aggiornati

### Hardening repository
- `README.md`
- `.env.example`
- `SECURITY.md`
- `.github/dependabot.yml`
- `.github/workflows/ci.yml`

### Deploy e documentazione
- `docs/deploy/GITHUB_AND_PLESK.md`
- `scripts/deploy/plesk-post-deploy.sh`
- `deployment/plesk/additional-deploy-actions.txt`

### Documentazione di progetto
- `AGENTS.md`
- `AI_CONTEXT.md`
- `sicurezzachiara_master_progress.md`

## Contenuti introdotti

### README
- sostituito il README Laravel generico
- descrizione reale del progetto
- setup locale corretto
- indicazioni Git
- collegamenti ai materiali di deploy

### .env.example
- allineato a PostgreSQL
- mailer impostato a `log`
- valori coerenti con lo stack effettivo del progetto

### Security policy
- introdotto `SECURITY.md`
- canale di segnalazione privato
- richiamo esplicito ai rischi di isolamento tenant

### GitHub readiness
- workflow CI GitHub con:
  - PHP 8.2
  - PostgreSQL service
  - Composer install
  - npm ci
  - migrate
  - test
  - build Vite
- Dependabot per Composer e npm

### Plesk readiness
- documentazione operativa dedicata
- script di post-deploy versionato
- file pronto per Additional Deployment Actions

## Verifiche eseguite
- `git branch --show-current`
- `git remote -v`
- `bash -n scripts/deploy/plesk-post-deploy.sh`

## Blocco reale residuo
La creazione effettiva del repository remoto GitHub `malvasiliberato/sicurezzachiara-neo` non e' stata eseguita in questo ambiente perche':
- non e' presente `gh`
- non e' disponibile un tool con capability di create-repository su GitHub
- non e' disponibile una credenziale API GitHub utilizzabile per creare il repository via shell

Quindi:
- il lato locale e' pronto
- il lato remoto va ancora creato direttamente su GitHub

## Proposta operativa immediata
1. Creare su GitHub il repository `malvasiliberato/sicurezzachiara-neo`
2. Verificare che l'URL remoto resti:
   - `https://github.com/malvasiliberato/sicurezzachiara-neo.git`
3. Eseguire il primo push del branch `main`
4. Collegare il repository in Plesk e copiare il contenuto di:
   - `deployment/plesk/additional-deploy-actions.txt`

## Rischi / gap aperti
- il push iniziale non e' ancora eseguibile finche' il repository remoto non esiste
- va confermata in Plesk la disponibilita' di `php`, `composer`, `node`, `npm`
- se Node non fosse disponibile in produzione, servira' una strategia asset alternativa

## Stato finale
- progetto predisposto correttamente per Git locale, GitHub remoto futuro e Plesk/Git
- nessun deploy eseguito
- stato finale `PRE-DEPLOY / NO-DEPLOY`

