# SicurezzaChiara - SC-PLAN-001 - Programma di implementazione dominio-first

## Contesto
Il repository SicurezzaChiara non va piu' trattato come demo grezza del template.
La base attuale e' gia' bootstrapata e razionalizzata in modo controllato:
- Velzon preservato come base UI
- UI reference page interna da considerare gold standard
- sidebar minima gia' coerente
- topbar lasciata volutamente invariata
- dashboard bootstrap minima

Questa fase non implementa moduli di business.
L'obiettivo e' produrre un programma operativo forte, verificabile e realmente somministrabile a task Codex successivi.

## Obiettivo
Definire il programma di implementazione del nuovo SicurezzaChiara su stack Laravel + PostgreSQL + Vue:
- dominio-first
- non appiattito sulla precedente implementazione storica
- organizzato in fasi realistiche
- traducibile in step operativi con dipendenze chiare
- con MVP vendibile netto e perimetro rinviato esplicito

## Stato attuale assunto come base reale
- repository applicativo gia' ordinato
- route applicative essenziali
- UI reference pronta come standard visivo
- nessun focus attuale su cleanup estesi del template
- nessun focus attuale su redesign UI

## Assunzioni forti
- target principale: consulenti / RSPP multi-azienda
- target secondario: azienda con vista semplice e consultiva
- multi-tenant base necessario gia' nel disegno MVP
- il cuore del prodotto e' il profilo rischio
- dashboard, registri e scadenze devono derivare dal motore di rischio
- il DVR e' un output coerente della governance del rischio, non il centro del sistema

## Principio architetturale guida
L'ordine corretto non e':
`dashboard -> scadenze -> documenti -> dati`

L'ordine corretto e':
`contesto aziendale -> sorgenti del rischio -> profilo rischio -> misure -> monitoraggio -> output`

Questo evita di costruire prima contenitori visuali o documentali che resterebbero vuoti, ridondanti o fondati su logiche fragili.

## Macro-moduli logici del dominio

### 1. Tenant, identita' e perimetro consulente
- tenant
- utenti
- ruoli base
- appartenenza consulente -> tenant
- aziende clienti del tenant

### 2. Contesto aziendale
- aziende
- sedi/unita' operative
- reparti o aree organizzative leggere
- lavoratori

### 3. Sorgenti del rischio
- mansioni
- macchinari
- luoghi di lavoro
- associazioni lavoratore <-> mansione
- associazioni sede/reparto <-> luogo/macchinario quando utile

### 4. Cataloghi e motore profilo rischio
- catalogo rischi
- regole di deduzione del rischio dalle sorgenti
- profilo rischio aziendale
- profilo rischio del lavoratore
- stato di copertura o scopertura

### 5. Misure e mitigazioni
- modello generale di misura
- specializzazioni operative:
  - formazione
  - visite mediche
  - DPI
  - misure organizzative/tecniche
- collegamento misura <-> rischio

### 6. Monitoraggio operativo
- scadenze
- verifiche
- criticita'
- segnali di non copertura
- viste operative consulente

### 7. Output e report
- DVR
- riepiloghi di presidio
- esportazioni documentali iniziali

### 8. Hardening applicativo
- audit trail
- notifiche
- ruoli piu' fini
- viste azienda consultive

## Programma di implementazione per fasi

### Fase 1 - Fondamenta operative del dominio
Obiettivo:
costruire il perimetro minimo su cui il rischio puo' essere modellato senza ancora implementare il motore completo.

Contenuto:
- tenant base
- aziende clienti
- sedi
- lavoratori
- mansioni
- macchinari
- luoghi di lavoro
- relazioni minime tra queste entita'

Motivazione:
senza contesto aziendale e sorgenti del rischio, il resto del sistema diventa decorativo o hardcoded.

### Fase 2 - Cataloghi rischio e motore profilo rischio
Obiettivo:
tradurre il contesto raccolto in un profilo rischio strutturato e verificabile.

Contenuto:
- catalogo rischi
- tassonomia minima rischi/categorie
- regole di deduzione da mansioni, macchinari e luoghi
- profilo rischio aziendale e lavoratore
- stati di copertura iniziali

Motivazione:
questo e' il cuore del prodotto e deve precedere dashboard, scadenze e DVR.

### Fase 3 - Misure e registri di presidio
Obiettivo:
modellare come il rischio viene mitigato e presidiato.

Contenuto:
- modello generale misure
- registri formazione
- registri visite mediche
- registri DPI
- misure organizzative o tecniche
- collegamenti rischio <-> misura

Motivazione:
senza profilo rischio non sappiamo quali misure servono; senza misure non possiamo misurare copertura e criticita'.

### Fase 4 - Scadenze, criticita' e workspace operativo
Obiettivo:
esporre il presidio nel tempo per il consulente.

Contenuto:
- motore scadenze
- alert di non copertura
- liste operative
- dashboard e workspace coerenti con UI reference

Motivazione:
qui il prodotto inizia a mostrare valore quotidiano tangibile, ma solo se alimentato dai moduli precedenti.

### Fase 5 - Output DVR e reporting iniziale
Obiettivo:
fare emergere il DVR dai dati reali del sistema.

Contenuto:
- composizione dati DVR
- export PDF iniziale
- riepiloghi aziendali

Motivazione:
il DVR deve essere conseguenza del sistema, non suo sostituto.

### Fase 6 - Hardening MVP vendibile
Obiettivo:
rendere il sistema piu' sicuro, gestibile e presentabile commercialmente.

Contenuto:
- ruoli base consolidati
- audit trail essenziale
- viste azienda consultive
- rifinitura permessi e validazioni

### Fase 7 - Estensioni fase 2/3
Obiettivo:
aprire evoluzioni senza sporcare l'MVP.

Contenuto:
- notifiche evolute
- workflow piu' complessi
- analytics avanzate
- portale cliente piu' ricco
- integrazioni esterne

## Ordine corretto di implementazione
Il primo modulo reale consigliato non e' dashboard, non e' scadenze e non e' DVR.

Il primo modulo reale corretto e':
`Fondamenta tenant + anagrafiche dominio + sorgenti del rischio`

Perimetro pratico del primo modulo:
- tenant base
- aziende
- sedi
- lavoratori
- mansioni
- macchinari
- luoghi
- relazioni essenziali

Perche' partire da qui:
- definisce il contesto reale su cui il rischio viene dedotto
- evita di congelare tardi relazioni chiave tra aziende, lavoratori e sorgenti del rischio
- permette di costruire subito il linguaggio comune del dominio
- riduce il rischio di dashboard vuote o costruite su mock permanenti
- prepara il motore profilo rischio come passo successivo naturale

Perche' non partire da altri moduli:
- dashboard: sarebbe una shell visuale senza fondazione semantica
- scadenze: dipendono dall'esistenza di misure e stati da monitorare
- DVR: rischia di riportare il progetto verso una logica documentale anziche' operativa
- formazione / visite / DPI da soli: diventerebbero silos prima di sapere da quali rischi derivano

## MVP rigoroso

### Entra nel primo MVP vendibile
- multi-tenant base per consulente con gestione di piu' aziende
- aziende, sedi, lavoratori, mansioni, macchinari, luoghi
- catalogo rischi minimo ma strutturato
- deduzione iniziale del profilo rischio da sorgenti principali
- registri misure per formazione, visite, DPI e misure tecniche/organizzative
- collegamento rischio <-> misura
- stato copertura / non copertura
- scadenze operative principali
- dashboard/workspace minimo coerente con il motore di rischio
- primo output DVR/PDF derivato dai dati

### Non entra nell'MVP anche se interessante
- workflow approvativi complessi
- notifiche multicanale avanzate
- analytics avanzate e benchmark tra aziende
- portale cliente ricco e self-service
- versioning sofisticato dei documenti
- automazioni enterprise o microservizi
- integrazioni esterne non essenziali

### Da rinviare volutamente a fase 2 o 3
- auditing dettagliato esteso
- notifiche configurabili per ruolo, tenant e canale
- pianificazione corsi avanzata
- gestione documentale estesa oltre DVR e output essenziali
- regole di rischio molto sofisticate o motori configurabili dall'utente finale
- viste executive e KPI comparativi evoluti

## Step operativi Codex-friendly

### SC-DOM-001 - Fondamenta tenant e perimetro consulente
- Obiettivo: fissare il perimetro multi-tenant base e la relazione consulente -> aziende clienti
- Perimetro incluso:
  - tenant
  - membership utente
  - aziende clienti
  - sedi
- Perimetro escluso:
  - motore rischio
  - misure
  - dashboard evoluta
- Prerequisiti:
  - piano SC-PLAN-001 validato
- Done condition:
  - schema e relazioni fondative coerenti
  - CRUD amministrativi minimi di base disponibili
- Rischio principale:
  - modellare male il perimetro tenant e dover rifare i legami piu' avanti
- Output atteso:
  - base anagrafica tenant/aziende/sedi pronta per i moduli successivi

### SC-DOM-002 - Lavoratori e anagrafiche operative
- Obiettivo: introdurre i lavoratori come soggetti osservati del rischio
- Perimetro incluso:
  - anagrafica lavoratori
  - collegamento a sede/azienda
  - stato attivo di presidio minimo
- Perimetro escluso:
  - storico avanzato
  - visite/formazione/DPI
- Prerequisiti:
  - `SC-DOM-001`
- Done condition:
  - lavoratore assegnabile e leggibile nel contesto aziendale
- Rischio principale:
  - anticipare attributi secondari e complicare il modello troppo presto
- Output atteso:
  - base lavoratori coerente con il futuro profilo rischio

### SC-DOM-003 - Mansioni, macchinari e luoghi
- Obiettivo: modellare le tre sorgenti del rischio
- Perimetro incluso:
  - cataloghi interni tenant per mansioni
  - macchinari
  - luoghi di lavoro
  - relazioni con azienda, sede e lavoratori dove necessarie
- Perimetro escluso:
  - deduzione del rischio
  - misure di mitigazione
- Prerequisiti:
  - `SC-DOM-001`
  - `SC-DOM-002`
- Done condition:
  - le tre sorgenti sono gestibili e associabili in modo coerente
- Rischio principale:
  - creare entita' isolate senza una semantica relazionale comune
- Output atteso:
  - base strutturale per alimentare il motore profilo rischio

### SC-DOM-004 - Catalogo rischi minimo e tassonomia
- Obiettivo: definire il primo catalogo rischio riusabile e stabile
- Perimetro incluso:
  - categorie rischio minime
  - rischio normalizzato
  - livelli/stati minimi utili all'MVP
- Perimetro escluso:
  - regole configurabili dall'utente
  - tassonomie troppo profonde
- Prerequisiti:
  - `SC-DOM-003`
- Done condition:
  - catalogo rischio pronto a essere agganciato alle sorgenti
- Rischio principale:
  - importare vecchie tassonomie storiche senza ripulirle concettualmente
- Output atteso:
  - catalogo rischio MVP formalizzato

### SC-DOM-005 - Motore profilo rischio MVP
- Obiettivo: dedurre il profilo rischio da mansioni, macchinari e luoghi
- Perimetro incluso:
  - regole di deduzione base
  - profilo rischio azienda
  - profilo rischio lavoratore
  - stati coperto / scoperto iniziali
- Perimetro escluso:
  - scoring sofisticato
  - regole dinamiche utente-configurabili
- Prerequisiti:
  - `SC-DOM-003`
  - `SC-DOM-004`
- Done condition:
  - il sistema produce un profilo rischio consistente e interrogabile
- Rischio principale:
  - introdurre logiche opache o troppo intelligenti troppo presto
- Output atteso:
  - cuore dominio funzionante per il presidio successivo

### SC-DOM-006 - Modello misure e collegamento rischio -> presidio
- Obiettivo: introdurre il concetto generale di misura come risposta operativa al rischio
- Perimetro incluso:
  - entita' misura generica
  - collegamento misura <-> rischio
  - stato attuata / non attuata / da verificare
- Perimetro escluso:
  - registri specialistici completi
- Prerequisiti:
  - `SC-DOM-005`
- Done condition:
  - ogni rischio puo' essere collegato ad almeno una misura gestibile
- Rischio principale:
  - modellare separatamente DPI, formazione e visite senza un asse comune
- Output atteso:
  - fondazione unificata dei registri di mitigazione

### SC-DOM-007 - Registri specialistici: formazione, visite, DPI
- Obiettivo: specializzare le misure nei registri principali dell'MVP
- Perimetro incluso:
  - formazione
  - visite mediche
  - DPI
  - misure organizzative/tecniche essenziali
- Perimetro escluso:
  - workflow avanzati e pianificazioni complesse
- Prerequisiti:
  - `SC-DOM-006`
- Done condition:
  - i principali tipi di misura sono registrabili e leggibili nel profilo rischio
- Rischio principale:
  - costruire tre silos scollegati dal modello misure comune
- Output atteso:
  - registri operativi MVP integrati nel dominio

### SC-DOM-008 - Scadenze, criticita' e workspace operativo
- Obiettivo: trasformare stato e misure in monitoraggio operativo consulente
- Perimetro incluso:
  - scadenze principali
  - criticita'
  - alert di non copertura
  - dashboard/workspace minimo
- Perimetro escluso:
  - analytics avanzate
  - notifiche multicanale evolute
- Prerequisiti:
  - `SC-DOM-007`
- Done condition:
  - il consulente vede cosa e' coperto, scoperto, in scadenza e critico
- Rischio principale:
  - fare UI appariscente ma non realmente derivata dai dati dominio
- Output atteso:
  - primo workspace operativo utile e vendibile

### SC-DOM-009 - Output DVR e reporting iniziale
- Obiettivo: fare emergere il DVR dai dati reali del sistema
- Perimetro incluso:
  - aggregazione dati profilo rischio
  - output DVR iniziale
  - export/report essenziali
- Perimetro escluso:
  - editor documentale complesso
  - gestione documentale estesa
- Prerequisiti:
  - `SC-DOM-008`
- Done condition:
  - esiste un primo DVR coerente con i dati del sistema
- Rischio principale:
  - tornare a una logica documento-centrica
- Output atteso:
  - primo output documentale realmente derivato dal dominio

### SC-DOM-010 - Hardening MVP
- Obiettivo: consolidare permessi, audit minimo e usabilita' commerciale
- Perimetro incluso:
  - ruoli base
  - audit trail essenziale
  - rifiniture accesso consultivo azienda
- Perimetro escluso:
  - governance enterprise avanzata
- Prerequisiti:
  - `SC-DOM-009`
- Done condition:
  - MVP presentabile e governabile in modo credibile
- Rischio principale:
  - introdurre complessita' autorizzativa prematura
- Output atteso:
  - MVP vendibile piu' robusto

## Dipendenze principali
- `SC-DOM-001` e' fondativo per tutto il resto
- `SC-DOM-003` e `SC-DOM-004` sono prerequisiti del motore profilo rischio
- `SC-DOM-005` sblocca misure, dashboard e DVR
- `SC-DOM-006` e `SC-DOM-007` sbloccano monitoraggio e scadenze reali
- `SC-DOM-008` deve precedere `SC-DOM-009`, altrimenti il DVR nascerebbe senza presidio operativo leggibile

## Decisioni da congelare presto
- modello multi-tenant base
- perimetro delle entita' fondative: azienda, sede, lavoratore, mansione, macchinario, luogo
- semantica del catalogo rischi MVP
- modello comune di misura e relazione rischio -> misura

## Decisioni da lasciare elastiche
- profondita' della tassonomia rischio oltre MVP
- granularita' finale dei ruoli
- livello di sofisticazione del motore regole
- ampiezza del reporting documentale
- notifiche e canali

## Piano non condizionato dalla storia precedente

### Elementi utili della precedente esperienza
- comprensione del dominio della sicurezza sul lavoro
- conoscenza delle principali misure operative
- sensibilita' verso scadenze, coperture e criticita'
- consapevolezza che il DVR da solo non basta a governare il presidio reale

### Elementi che NON devono condizionare il nuovo progetto
- vecchie strutture tecniche CI4
- vecchi accorpamenti tra moduli dovuti a limiti implementativi
- vecchie tassonomie non riviste concettualmente
- sequenze di implementazione centrate sul documento invece che sul motore di rischio
- CRUD storici scambiati per bounded context corretti

### Ripartenze pulite consigliate
- modellare esplicitamente il dominio prima delle viste operative
- introdurre un asse comune misura/rischio fin dall'inizio
- trattare il multi-tenant come decisione fondativa e non come retrofit
- costruire dashboard e scadenze solo dopo che il dominio genera segnali reali
- mantenere il DVR come output terminale del sistema

## Rischi principali di progettazione
- partire da moduli vistosi ma non fondativi
- importare senza filtro la semantica del progetto storico
- creare silos separati tra formazione, visite e DPI
- trattare il DVR come centro del prodotto
- modellare tardi le relazioni tenant/azienda/lavoratore/sorgenti del rischio

## Proposta operativa
La prossima ripartenza consigliata e' aprire un task esecutivo centrato su:

`SC-DOM-001 + SC-DOM-002 + SC-DOM-003 (fondamenta tenant, aziende, lavoratori, mansioni, macchinari, luoghi)`

con focus su:
- schema logico
- relazioni
- naming coerente
- perimetro UI minimo ma gia' aderente alla UI reference

Questa scelta massimizza solidita' di dominio e riuso e prepara il motore profilo rischio senza aprire subito troppi filoni.

## Stato finale
- fase di pianificazione completata
- nessuna implementazione business eseguita in questo task
- repository lasciato in stato `PRE-DEPLOY / NO-DEPLOY`

