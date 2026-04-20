# AGENTS.md

## SicurezzaChiara - regole permanenti operative

### Contesto sintetico
SicurezzaChiara nasce su stack Laravel + Vue + PostgreSQL a partire da Velzon.
Il prodotto non e' un software che "scrive il DVR", ma una piattaforma che governa il rischio aziendale in modo continuo, strutturato e verificabile.

Sorgenti del rischio:
- mansioni
- macchinari
- luoghi di lavoro

Misure principali:
- visite mediche
- corsi di formazione
- DPI
- misure organizzative o tecniche

### Stack e principi permanenti
- backend: Laravel
- frontend: Vue + Inertia
- database: PostgreSQL
- UI base: Velzon
- regola UI permanente: Velzon-first
- regola UI permanente: minimum visual change / maximum functional clarity
- divieto permanente: non introdurre layout, librerie UI o linguaggi visivi alieni rispetto a Velzon

### Distinzione obbligatoria
Principi di dominio:
- SicurezzaChiara governa il rischio nel tempo
- il profilo rischio e' il cuore del dominio
- dashboard, registri e scadenze devono essere conseguenza del motore di rischio
- il DVR e' un output vivo del sistema

Template e implementazione storica:
- Velzon e' base visiva e repertorio di pattern
- la reference page UI/UX interna va considerata gold standard del progetto
- il template non va bonificato in modo cieco
- l'esperienza storica del precedente progetto serve come apprendimento di dominio, non come vincolo tecnico
- non si progetta come migrazione lineare del vecchio CI4

Principio non negoziabile di governance:
- il consulente ha sempre l'ultima parola sulla valutazione del rischio
- il core puo' suggerire, automatizzare e precompilare
- il sistema non deve mai trasformare il catalogo standard in vincolo rigido
- il dominio deve distinguere chiaramente conoscenza core, contesto aziendale, valutazione professionale e stato operativo finale

### Governance per step
Ogni step deve includere almeno:
- Step ID
- stato
- obiettivo
- perimetro incluso
- perimetro escluso
- prerequisiti
- done condition
- rischio principale
- output atteso

Stati ammessi:
- `backlog`
- `ready`
- `in_corso`
- `bloccato`
- `validato`
- `chiuso`
- `no_go`

Famiglie di Step ID da presidiare:
- `SC-UI-*` per il gold standard UI
- `SC-CLEAN-*` per le razionalizzazioni controllate
- `SC-PLAN-*` per programma di implementazione, ordine moduli e decisioni fondative
- `SC-DOM-*` per i moduli reali di dominio
- `SC-DMODEL-*` per affinamento del modello di dominio prima dell'implementazione
- `SC-OPS-*` per audit operativo repository, GitHub e deploy

### Perimetro incluso
- audit prudente del template e dei pattern UI
- reference page interna UI/UX
- razionalizzazione controllata del rumore demo quando realmente utile
- pianificazione dominio-first del nuovo prodotto
- implementazione progressiva di moduli reali orientati al flusso operativo del consulente
- aggiornamento documentazione e reportistica tecnica

### Perimetro escluso
- cleanup distruttivo del template
- redesign del template
- deploy o rilascio
- interventi gratuiti sulla topbar
- microservizi o sofisticazioni enterprise premature

### Regole di pianificazione e implementazione
- ragionare come nuovo prodotto, non come semplice migrazione storica
- preferire task piccoli, verificabili e orientati al flusso operativo reale
- non aprire troppi filoni in parallelo
- costruire prima i moduli fondativi da cui dipendono dashboard, registri, scadenze e DVR
- congelare presto solo le decisioni strutturali ad alto impatto
- mantenere elastiche le scelte che possono evolvere senza costo architetturale eccessivo
- distinguere sempre tra cataloghi standard e istanze operative reali
- prevedere fin dall'inizio la compatibilita' con override e personalizzazioni del consulente
- nel bootstrap esecutivo del dominio usare un contesto tenant attivo leggero ma esplicito
- preservare la catena di ownership `tenant -> aziende -> sedi` senza confonderla con l'autorizzazione utente

### Regole UI permanenti
- partire sempre da Velzon
- preservare topbar, layout, card grammar, tab, form blocks e componenti condivisi
- preservare la UI reference come gold standard visivo e operativo
- applicare cambiamenti visivi minimi e solo se migliorano chiarezza funzionale
- nessun linguaggio visivo alieno rispetto al template

### Regole per task e verifica
- mantenere build, routing e asset pipeline funzionanti
- aggiornare `sicurezzachiara_master_progress.md` a ogni task che modifica stato o perimetro
- creare sempre report strutturati in `_codex_reports`
- fermarsi solo prima di eventuali attivita' di deploy

### Report obbligatori in `_codex_reports`
Usare sempre `_codex_reports` per audit, piani, cleanup controllati, reference page, hardening, verifiche e riepiloghi.

Regole naming:
- timestamp iniziale completo: `YYYYMMDD_HHMMSS`
- solo minuscole, numeri, underscore e trattini
- niente spazi
- ordinamento cronologico certo

Struttura minima da usare:
- `_codex_reports/sicurezzachiara/shared`
- `_codex_reports/sicurezzachiara/deploy`

Ogni report deve esplicitare almeno:
- contesto
- obiettivo
- stato attuale
- assunzioni
- evidenze
- decisioni
- fasi o step rilevanti
- dipendenze
- rischi
- proposta operativa
- punto di stato finale

### Master progress
La fonte di verita' sintetica del progetto e':

`sicurezzachiara_master_progress.md`

Va letta prima di lavorare e aggiornata quando cambia:
- stato step
- perimetro
- decisioni
- blocchi
- prossima ripartenza

### Regola finale
Su questo progetto devi:
1. lavorare per step tracciabili
2. distinguere sempre dominio, architettura e template
3. preservare Velzon come base visiva
4. preservare la UI reference come gold standard
5. lasciare la topbar invariata salvo eccezioni motivate
6. aggiornare report e master progress
7. progettare il nuovo prodotto senza farti vincolare da scelte storiche contingenti
8. fermarti prima del deploy
