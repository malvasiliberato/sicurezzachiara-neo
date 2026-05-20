# SC-POSTMVP-OPS-008 - Deploy brand/customizer completato e strategia controllata

## Contesto
Deploy richiesto esplicitamente dopo il lotto UI/loghi/login/customizer del 2026-05-20.

Il primo tentativo via SSH era rimasto bloccato per assenza di privilegi server sufficienti. Dopo la verifica del canale Plesk, il deploy e' stato gestito tramite estensione Git di Plesk e API del pannello, senza esporre credenziali o webhook nel repository.

## Obiettivo
Pubblicare su `staging.sicurezzachiara.it` e `area.sicurezzachiara.it` il commit applicativo con:
- loghi ufficiali `brand2`
- payoff login ufficiale
- customizer con pagina iniziale e densita
- fix pagina iniziale post-login
- icona grigia sui template scuri

Definire inoltre una strategia controllata per i deploy successivi dopo aggiornamento di `origin/main`.

## Stato attuale
- Commit runtime pubblicato: `d48abe3 feat: refine branded shell and login preferences`.
- `origin/main` aggiornato a `d48abe368f5062a3bce688223b0ddfab8244fc94`.
- Deploy Plesk completato su `staging`.
- Deploy Plesk completato su `area`.
- Strategia controllata documentata in `docs/deploy/GITHUB_AND_PLESK.md`.
- Workflow GitHub Actions per trigger staging aggiunto in `.github/workflows/deploy-staging.yml`.

## Assunzioni
- Il deploy target resta doppio: `staging` e `area`.
- `staging` e' l'ambiente che puo' ricevere aggiornamento automatico controllato dopo CI verde.
- `area` resta ambiente principale da promuovere manualmente dopo smoke su `staging`.
- Non risultano nuove migration nel lotto pubblicato.
- Le migration remote restano manuali dopo backup DB e decisione esplicita.

## Evidenze
- `npm run build`: completato correttamente prima del commit runtime.
- `php artisan test tests/Feature/AuthenticationTest.php tests/Feature/SicurezzaChiaraOperationalWorkspaceTest.php`: `11 passed / 98 assertions`.
- `git push origin main`: completato per il commit runtime `d48abe3`.
- Plesk REST API raggiungibile e operativa.
- Estensione Git Plesk interrogata e aggiornata tramite CLI esposta da API.
- `staging.sicurezzachiara.it` configurato su repository Plesk `sc-staging-none.git`, branch `main`, remote `https://github.com/malvasiliberato/sicurezzachiara-neo.git`, deployment mode `auto`, post-deploy actions abilitate.
- `area.sicurezzachiara.it` configurato su repository Plesk `sc-area-manual.git`, branch `main`, remote `https://github.com/malvasiliberato/sicurezzachiara-neo.git`, deployment mode `manual`, post-deploy actions abilitate.
- Ultimo commit Plesk su `staging`: `d48abe368f5062a3bce688223b0ddfab8244fc94`.
- Ultimo commit Plesk su `area`: `d48abe368f5062a3bce688223b0ddfab8244fc94`.
- `https://staging.sicurezzachiara.it/login`: `200`.
- `https://area.sicurezzachiara.it/login`: `200`.
- `manifest.json` live rigenerato su entrambi gli ambienti con asset remoti aggiornati per `Login.vue` e `app.js`.

## Decisioni
- Pubblicato il lotto runtime gia' commitato, senza lanciare migration remote automatiche.
- Normalizzata la configurazione Git Plesk:
  - staging: automatico, usato come ambiente di verifica controllata;
  - area: manuale, usato come promozione consapevole.
- Aggiunto workflow GitHub Actions `deploy-staging` che chiama il webhook Plesk di staging solo dopo CI verde su `main` o manual dispatch.
- Il webhook Plesk non viene committato: deve vivere nel secret GitHub `PLESK_STAGING_WEBHOOK_URL`.

## Fasi o step rilevanti
- Step ID: `SC-POSTMVP-OPS-008`
- Stato: `chiuso`
- Obiettivo: pubblicare `d48abe3` su staging e area e impostare una strategia di deploy controllato.
- Perimetro incluso: pull/deploy Plesk, build lato server, post-deploy actions, smoke HTTP, runbook deploy, workflow staging.
- Perimetro escluso: seed, deploy distruttivi, migration automatiche non necessarie, esposizione di credenziali o webhook nel repository.
- Prerequisiti: accesso Plesk operativo e repository GitHub aggiornato.
- Done condition: entrambi gli ambienti allineati a `d48abe3`, login `200`, manifest live aggiornato, strategia successiva documentata.
- Rischio principale: deploy automatico diretto su `area` senza verifica preventiva.
- Output atteso: staging auto dopo CI verde, area manuale dopo smoke.

## Dipendenze
- Secret GitHub richiesto: `PLESK_STAGING_WEBHOOK_URL`.
- Workflow CI esistente: `.github/workflows/ci.yml`.
- Workflow deploy staging: `.github/workflows/deploy-staging.yml`.
- Runbook: `docs/deploy/GITHUB_AND_PLESK.md`.

## Rischi
- Se il secret GitHub non viene configurato, il workflow `deploy-staging` fallira' in modo esplicito senza deploy.
- Se la CI viene rinominata, va aggiornata la chiave `workflows: [ci]` nel workflow deploy.
- Le migration restano volutamente fuori dall'automatismo: un deploy con nuove migration richiede finestra operativa e backup DB.
- Il repository locale contiene report e documenti di governance non sempre destinati al publish runtime: mantenere staging selettivo.

## Proposta operativa
Strategia da usare da ora in avanti:
1. sviluppare localmente e verificare con test/build;
2. committare e pushare su `main`;
3. lasciare che GitHub Actions esegua `ci`;
4. se `ci` e' verde, `deploy-staging` chiama il webhook Plesk e aggiorna `staging`;
5. eseguire smoke su `staging`;
6. promuovere `area` manualmente da Plesk solo dopo esito positivo.

Per completare l'automazione staging, configurare in GitHub il secret `PLESK_STAGING_WEBHOOK_URL` con il webhook del repository Plesk di staging.

## Punto di stato finale
Deploy completato su `staging` e `area` al commit `d48abe3`.

Stato finale: `CHIUSO`, con strategia controllata pronta in repository e unico prerequisito operativo residuo nel secret GitHub per il trigger automatico staging.
