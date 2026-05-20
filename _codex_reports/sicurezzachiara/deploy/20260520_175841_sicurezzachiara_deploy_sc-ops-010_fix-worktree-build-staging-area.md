# SC-OPS-010 - Fix worktree e build asset staging/area

## Contesto
Dopo l'allineamento Plesk a `46bf218`, l'utente ha segnalato che le modifiche visibili localmente su login, sidebar, topbar e customizer non risultavano online.

## Obiettivo
Capire se il problema fosse:
- codice non committato;
- commit non deployato;
- asset Vite non rigenerati;
- cache browser/CDN.

## Stato attuale
- Le modifiche applicative risultavano gia' nel commit `d48abe3` e quindi nella storia di `46bf218`.
- I record Plesk Git indicavano `46bf218` per staging e area.
- Il manifest pubblico online era pero' ancora quello vecchio:
  - `assets/app-AYu60JJv.js`
  - `assets/app-BiCMwofH.css`
  - `assets/Login-6b5zGJb0.js`
- Il manifest locale corretto era:
  - `assets/app-BflrfamX.js`
  - `assets/app-wj5fxzXV.css`
  - `assets/Login-B9ga3ycw.js`
  - `assets/Login-BAxEExXw.css`
  - `assets/logo-payoff-light-CuvGQFNB.png`

## Assunzioni
- Non risultavano nuove migration da eseguire.
- Gli ambienti devono restare allineati a `origin/main`.
- I file di governance/report locali non devono essere pubblicati come payload runtime.

## Evidenze
- Probe nel document root reale `repo/public` su staging:
  - worktree servito inizialmente a `cd179559cee30587882185c223518d4fdd1e6a60`
  - nonostante il record Plesk Git indicasse un commit piu' recente.
- Il comando Plesk Git `--run-deployment-actions` non ha aggiornato il manifest pubblico.
- Il comando Plesk Git `--deploy` ha restituito `fatal: this operation must be run in a work tree`.
- Esecuzione controllata via scheduled task Plesk:
  - `git pull --ff-only origin main`
  - `scripts/deploy/plesk-post-deploy.sh`
- Dopo l'intervento:
  - staging worktree: `46bf218f7c8be54887fff1888762142ca5880075`
  - area worktree: `46bf218f7c8be54887fff1888762142ca5880075`
  - manifest staging e area aggiornato a:
    - `assets/app-BflrfamX.js`
    - `assets/app-wj5fxzXV.css`
    - `assets/Login-B9ga3ycw.js`
    - `assets/Login-BAxEExXw.css`
    - `assets/logo-payoff-light-CuvGQFNB.png`
  - `https://staging.sicurezzachiara.it/login`: `200`
  - `https://area.sicurezzachiara.it/login`: `200`

## Decisioni
- Non creato nuovo commit runtime: il codice era gia' su `origin/main`.
- Forzato il riallineamento della working copy servita con `git pull --ff-only`.
- Rigenerati gli asset via script post-deploy nei path reali dei due ambienti.
- Rimossi i file sonda pubblici usati per la diagnosi.

## Fasi o step rilevanti
- Step ID: `SC-OPS-010`
- Stato: `chiuso`
- Obiettivo: rendere visibili online le modifiche layout/login/sidebar/topbar/customizer gia' presenti nel codice.
- Perimetro incluso: diagnosi manifest, probe worktree, pull fast-forward, build Vite, cache Laravel, smoke HTTP.
- Perimetro escluso: migration remote, reset distruttivi, commit massivo di governance locale.
- Prerequisiti: accesso Plesk API e scheduled task subscription.
- Done condition: worktree servito e manifest pubblico allineati al build corrente.
- Rischio principale: fidarsi del record Plesk Git senza verificare il worktree realmente servito.
- Output atteso: modifiche UI/funzionali visibili su staging e area.

## Dipendenze
- Plesk scheduled task CLI.
- Script `scripts/deploy/plesk-post-deploy.sh`.
- Repository GitHub `origin/main`.

## Rischi
- Il webhook Plesk Git non e' sufficiente se non aggiorna la working copy servita.
- La strategia deploy va rafforzata sostituendo o affiancando il webhook con un comando controllato che esegua `git pull --ff-only` nel worktree reale e poi lo script post-deploy.

## Proposta operativa
Aggiornare la strategia CD:
1. staging automatico non deve limitarsi al webhook Plesk Git;
2. deve verificare il worktree reale con `git rev-parse HEAD`;
3. deve eseguire build e controllare il manifest pubblico;
4. area resta manuale ma con la stessa procedura operativa.

## Punto di stato finale
Staging e area ora servono il manifest aggiornato e sono allineati a `46bf218`.

Stato finale: `CHIUSO`.
