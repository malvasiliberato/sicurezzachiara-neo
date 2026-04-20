# GitHub e Plesk - setup operativo consigliato

## Obiettivo
Preparare il progetto per:
- repository Git remoto su GitHub
- versionamento corretto
- deploy automatico futuro via Plesk/Git

## Audit sintetico iniziale
Stato rilevato prima del setup:
- nessuna directory `.git`
- `.gitignore` Laravel gia' sano
- `.env` correttamente escluso dal versionamento
- `README.md` ancora generico Laravel
- `.env.example` non allineato allo stack reale PostgreSQL
- nessuna documentazione GitHub/Plesk dedicata
- nessun workflow CI GitHub

## Stato desiderato dopo il setup
- repository Git locale inizializzato su `main`
- documentazione aggiornata
- file sensibili esclusi
- CI GitHub pronta
- script post-deploy Plesk versionato
- istruzioni operative chiare per collegare un nuovo remoto GitHub

## Nome repository remoto
Non usare il repository storico:
- `malvasiliberato/sicurezzachiara`

Creare invece un repository nuovo e distinto, per esempio:
- `malvasiliberato/sicurezzachiara-platform`

## Procedura Git locale
Nel progetto:

```powershell
git init -b main
git add .
git status
```

Se vuoi collegare il nuovo remoto dopo averlo creato su GitHub:

```powershell
git remote add origin https://github.com/malvasiliberato/sicurezzachiara-platform.git
git branch -M main
git push -u origin main
```

## Collegamento a GitHub
Questo setup prepara il repository, ma la creazione del remoto GitHub richiede:
- repository creato nel tuo account
- URL remoto finale
- credenziali Git valide sulla macchina che fara' il push

## CI GitHub
Il workflow `.github/workflows/ci.yml` esegue:
- composer install
- npm ci
- migrate su PostgreSQL
- test Laravel
- build Vite

Serve per validare i push prima del deploy Plesk.

## Plesk + Git - approccio consigliato
Usare il repository Git remoto come sorgente.

Prerequisiti sul server Plesk:
- PHP 8.2+
- Composer disponibile
- Node.js disponibile se vuoi buildare asset sul server
- accesso database PostgreSQL
- Git extension attiva

Secondo la documentazione Plesk, le Additional Deployment Actions possono eseguire comandi shell o script durante il deploy Git. Vedi:
- [Plesk KB - Additional Deployment Actions](https://www.plesk.com/kb/docs/using-remote-git-hosting-enable-additional-deployment-actions/)
- [Plesk Obsidian - Laravel Toolkit](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/laravel-toolkit.80010/)
- [Plesk CLI - Git repositories](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/git-git-repositories.75956/)

## Script post-deploy
Il file versionato:
- `scripts/deploy/plesk-post-deploy.sh`

esegue in ordine:
- pulizia cache applicativa
- install dipendenze Composer produzione
- eventuale build frontend se `npm` e' disponibile
- migrazioni forzate
- `storage:link` se manca
- cache Laravel di produzione

## Additional Deployment Actions
Il file:
- `deployment/plesk/additional-deploy-actions.txt`

contiene il comando pronto da copiare in Plesk.

## Scelte deliberate
- `public/build` resta escluso da Git: il deploy presuppone build lato server oppure pipeline separata
- nessun segreto viene versionato
- nessun deploy effettivo viene eseguito in questa fase

## Rischi residui
- il remoto GitHub non viene creato automaticamente in questo ambiente
- serve confermare sul Plesk target la disponibilita' di `node`, `npm`, `composer` e `php`
- se Node non fosse disponibile in produzione, bisognera' decidere una strategia asset diversa

## Checklist pre-push
- verificare `.env` non tracciato
- verificare `.env.example` coerente
- verificare `npm run build`
- verificare `php artisan test`
- verificare migrazioni ok

## Stato finale
Setup locale e documentale pronto per:
- GitHub repository remoto nuovo
- deploy Git-based futuro via Plesk

Nessun deploy eseguito in questa fase.
