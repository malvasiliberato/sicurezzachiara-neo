# SicurezzaChiara - SC-DMODEL-001 - Domain modeling fondativo

## Contesto
Il repository SicurezzaChiara dispone gia' di:
- base Velzon Laravel + Vue bootstrapata
- reference page UI/UX da preservare come gold standard
- sidebar minima coerente
- topbar volutamente invariata
- piano di implementazione dominio-first gia' formalizzato

Prima di avviare i primi step esecutivi reali, serve congelare meglio il modello di dominio fondativo.
Questo task non implementa moduli business: chiarisce il disegno concettuale che dovra' guidare `SC-DOM-001/002/003`.

## Obiettivo
Produrre un domain modeling operativo per il blocco iniziale del dominio, con focus su:
- tenant
- utenti/consulenti
- aziende
- sedi
- lavoratori
- mansioni
- macchinari
- luoghi
- relazioni e cardinalita'
- distinzione cataloghi vs istanze operative
- layering tra core, contesto, valutazione professionale e stato operativo finale

## Stato attuale
- il programma di implementazione generale e' gia' corretto
- il primo blocco implementativo candidato resta quello fondativo
- manca ancora una definizione piu' rigorosa delle ownership e dei livelli di verita' del rischio

## Principi di dominio non negoziabili
- SicurezzaChiara governa il rischio nel tempo, non produce solo documenti
- mansioni, macchinari e luoghi sono sorgenti del rischio
- il core puo' fornire base standard, mapping e suggerimenti
- il consulente deve avere sempre pieno potere di aggiunta, esclusione, rafforzamento, riduzione e personalizzazione
- il software assiste la valutazione professionale, non la sostituisce
- il dominio deve distinguere chiaramente:
  - conoscenza core / standard
  - contesto aziendale
  - valutazione professionale
  - stato operativo finale assunto dal sistema

## Modello delle entita' fondative

### Tenant
Significato:
contenitore organizzativo principale del SaaS. Rappresenta il perimetro operativo di un consulente o di una struttura consulenziale.

Perche' esiste:
- separazione dati tra clienti/consulenti
- base per ownership, configurazioni, cataloghi tenant-level e accessi

Collegamenti:
- ha molti utenti
- ha molte aziende clienti
- puo' possedere cataloghi tenant-level di mansioni, tipologie macchinari, tipologie luoghi e regole locali

Appartenenza:
- istanza operativa tenant-level

Nota:
il tenant non coincide con la singola azienda cliente. Il caso d'uso dominante e' un consulente che governa piu' aziende nello stesso tenant.

### Utente / Consulente
Significato:
attore applicativo che accede al sistema. Nel target principale e' il consulente che governa aziende del tenant.

Perche' esiste:
- autenticazione
- autorizzazione
- responsabilita' sulle valutazioni professionali

Collegamenti:
- appartiene a uno o piu' tenant tramite membership
- opera su piu' aziende del tenant secondo permessi
- e' il soggetto che crea o conferma override e personalizzazioni

Appartenenza:
- istanza operativa tenant-level tramite membership

Nota:
non conviene modellare subito un'entita' separata "Consulente" diversa da `User` se non emergono attributi realmente autonomi. All'inizio basta `User + membership + role`.

### Azienda
Significato:
cliente finale governato dal consulente. E' l'entita' principale su cui viene costruito il presidio del rischio.

Perche' esiste:
- aggrega sedi, lavoratori, macchinari e profilo rischio aziendale
- rappresenta il perimetro gestionale e documentale del cliente

Collegamenti:
- appartiene a un tenant
- ha molte sedi
- ha molti lavoratori
- puo' avere macchinari assegnati direttamente o tramite sedi
- possiede configurazioni e personalizzazioni aziendali

Appartenenza:
- istanza operativa tenant-level

### Sede / Unita' operativa
Significato:
sotto-perimetro fisico o organizzativo dell'azienda in cui si collocano luoghi, lavoratori e macchinari.

Perche' esiste:
- evita di trattare l'azienda come contenitore monolitico
- permette di localizzare rischi, luoghi e assegnazioni operative

Collegamenti:
- appartiene a un'azienda
- ha molti luoghi
- puo' avere molti lavoratori assegnati
- puo' avere molti macchinari presenti

Appartenenza:
- istanza operativa aziendale

Nota:
la sede va pensata come "contenitore operativo reale", non solo come anagrafica postale.

### Lavoratore
Significato:
soggetto esposto al rischio e destinatario di parte delle misure di presidio.

Perche' esiste:
- il profilo rischio non e' solo aziendale ma anche individuale o per gruppi omogenei
- formazione, visite e DPI hanno forte ancoraggio sul lavoratore

Collegamenti:
- appartiene a un'azienda
- puo' essere assegnato a una sede prevalente
- ha una o piu' assegnazioni di mansione
- puo' avere esposizioni operative a luoghi e macchinari

Appartenenza:
- istanza operativa aziendale

Nota:
non conviene inglobare tutte le esposizioni in campi diretti del lavoratore; servono relazioni dedicate e temporalmente estendibili.

### Mansione
Significato:
profilo di attivita' o ruolo operativo che costituisce una sorgente primaria di rischio.

Perche' esiste:
- collega attivita' lavorativa e rischi tipici
- fa da ponte tra organizzazione del lavoro e motore di rischio

Collegamenti:
- puo' esistere come catalogo standard/core
- puo' esistere come catalogo tenant-level adattato
- puo' essere assegnata a uno o piu' lavoratori tramite istanze di assegnazione

Appartenenza:
- catalogo standard oppure catalogo tenant-level
- non appartiene direttamente al singolo lavoratore; l'assegnazione al lavoratore e' un'entita' operativa separata

### Macchinario
Significato:
fonte materiale di esposizione o pericolo. Non e' solo inventario, ma sorgente di rischio.

Perche' esiste:
- parte rilevante del rischio deriva dalle caratteristiche delle attrezzature presenti e usate

Collegamenti:
- puo' esistere come tipologia/catalogo
- puo' esistere come macchina concreta in una azienda o sede
- puo' essere collegato a lavoratori che lo usano o vi sono esposti

Appartenenza:
- tipologia: catalogo standard o tenant-level
- macchina concreta: istanza operativa aziendale o di sede

### Luogo
Significato:
ambiente o area di lavoro che partecipa al rischio in base a caratteristiche fisiche, ambientali e operative.

Perche' esiste:
- consente di far emergere rischi ambientali e contestuali non riducibili a mansione o macchinario

Collegamenti:
- puo' esistere come categoria/tipologia
- puo' esistere come luogo concreto in una sede
- puo' essere collegato a lavoratori che operano o transitano in quel luogo

Appartenenza:
- tipologia: catalogo standard o tenant-level
- luogo concreto: istanza operativa di sede

## Relazioni e cardinalita'

### Consulente -> aziende
Proposta:
- molti-a-molti tramite membership o permessi operativi

Logica:
- un consulente puo' governare piu' aziende
- una stessa azienda potrebbe essere visibile a piu' utenti del tenant

Ownership:
- l'azienda appartiene al tenant
- l'utente ha accesso all'azienda tramite relazioni di autorizzazione, non di ownership

### Azienda -> sedi
Proposta:
- uno-a-molti

Logica:
- una azienda puo' avere zero, una o molte sedi
- una sede appartiene a una sola azienda

Ownership:
- forte ownership azienda -> sede

### Azienda -> lavoratori
Proposta:
- uno-a-molti

Logica:
- un lavoratore appartiene a una sola azienda nel modello MVP
- eventuali scenari piu' complessi si gestiranno in seguito con assegnazioni o storico

Ownership:
- forte ownership azienda -> lavoratore

### Lavoratore -> mansioni
Proposta:
- molti-a-molti tramite `assegnazione_mansione`

Logica:
- un lavoratore puo' avere piu' mansioni
- una mansione puo' essere assegnata a molti lavoratori
- l'assegnazione e' la vera istanza operativa, dove in futuro potranno vivere date, prevalenza e note

Ownership:
- la mansione e' catalogo
- l'assegnazione appartiene al contesto aziendale/lavoratore

### Sede -> luoghi
Proposta:
- uno-a-molti

Logica:
- una sede contiene molti luoghi concreti
- un luogo concreto appartiene a una sola sede

Ownership:
- forte ownership sede -> luogo concreto

### Azienda/sede -> macchinari
Proposta:
- azienda -> macchinari concreti: uno-a-molti
- sede -> macchinari concreti: opzionale uno-a-molti

Logica:
- la macchina concreta appartiene all'azienda
- puo' essere localizzata in una sede specifica

Ownership:
- ownership primaria azienda -> macchina concreta
- assegnazione a sede come localizzazione operativa, non come ownership principale

### Lavoratore -> luoghi
Proposta:
- molti-a-molti tramite `esposizione_luogo` o `assegnazione_luogo_operativo`

Logica:
- un lavoratore puo' operare in piu' luoghi
- un luogo puo' esporre piu' lavoratori

Ownership:
- il luogo appartiene alla sede
- l'esposizione appartiene al contesto operativo del lavoratore

### Lavoratore -> macchinari
Proposta:
- molti-a-molti tramite `uso_macchinario` o `esposizione_macchinario`

Logica:
- un lavoratore puo' usare piu' macchinari
- un macchinario puo' essere usato da piu' lavoratori

Ownership:
- il macchinario concreto appartiene all'azienda
- la relazione d'uso appartiene al contesto operativo del lavoratore

## Relazioni utili e relazioni da evitare

### Utili
- `lavoratore <-> mansione` come assegnazione esplicita
- `lavoratore <-> luogo` come esposizione o frequenza operativa
- `lavoratore <-> macchinario` come uso o esposizione
- `macchinario -> sede` come localizzazione
- `luogo -> tipologia luogo`
- `macchinario concreto -> tipologia macchinario`

### Da evitare
- mettere nel lavoratore liste denormalizzate di luoghi e macchinari in campi blob o JSON senza relazione semantica
- fondere mansione e assegnazione mansione nella stessa entita'
- trattare sede e luogo come sinonimi
- trattare macchinario tipologico e macchina concreta come stessa entita'

## Sorgenti del rischio

### Mansioni
Le mansioni non sono semplici anagrafiche decorative.
Sono sorgenti attive del motore di rischio e rappresentano il primo asse di deduzione.

Contributo:
- suggeriscono rischi tipici professionali
- orientano misure attese su gruppi di lavoratori

### Macchinari
I macchinari sono sorgenti attive del rischio.
La loro presenza e il loro uso aggiungono rischi che non devono essere assorbiti artificialmente nella mansione.

Contributo:
- introducono rischi da attrezzatura, interazione, manutenzione, uso
- possono valere a livello aziendale o di specifici lavoratori esposti

### Luoghi
I luoghi sono sorgenti attive del rischio.
Esprimono rischi ambientali, contestuali o organizzativi.

Contributo:
- aggiungono rischi legati all'ambiente effettivo
- possono agire su lavoratori che operano in quegli spazi anche con la stessa mansione

### Partecipazione cumulativa
Le tre sorgenti devono contribuire in modo cumulativo e leggibile:
- una mansione puo' suggerire un primo blocco di rischi
- un luogo puo' aggiungere o modificare il profilo
- un macchinario puo' aggiungere ulteriori esposizioni

Il motore non deve ridurre tutto a una sola origine.
Serve tracciare l'origine di ogni suggerimento o componente del rischio.

### Come evitare duplicazioni e ambiguita'
- ogni rischio suggerito deve conservare la o le sorgenti che lo hanno attivato
- non si deve deduplicare troppo presto cancellando l'informazione di origine
- il rischio finale puo' essere unico, ma deve essere spiegabile da quali sorgenti lo alimentano

## Cataloghi vs istanze reali

## Principio
Per mansioni, macchinari e luoghi il modello corretto e' a due livelli minimi:
- livello catalogo/tipologia
- livello istanza operativa reale

### Mansioni
Catalogo:
- mansione core standard
- mansione tenant-level personalizzata

Istanza reale:
- assegnazione della mansione a un lavoratore o a un gruppo operativo

Scelta consigliata:
- non creare mansioni direttamente dentro il lavoratore
- usare `mansione_catalogo` + `assegnazione_mansione`

### Macchinari
Catalogo:
- tipologia macchinario standard
- tipologia tenant-level adattata

Istanza reale:
- macchina concreta presente in azienda o sede

Scelta consigliata:
- distinguere `macchinario_tipo` da `macchinario_asset`
- collegare i lavoratori alla macchina concreta o all'esposizione d'uso

### Luoghi
Catalogo:
- tipologia luogo o categoria di ambiente
- versione tenant-level personalizzata se serve

Istanza reale:
- luogo concreto esistente in una sede

Scelta consigliata:
- distinguere `luogo_tipo` da `luogo_operativo`
- evitare di usare la sede come surrogato del luogo

## Livelli di verita' del rischio

### 1. Rischio suggerito dal core
Origine:
- conoscenza standard del sistema
- mapping core tra tipologie e rischi

Ruolo:
- base automatizzabile e coerente
- non vincolante

### 2. Rischio derivato dal contesto aziendale
Origine:
- istanze reali presenti nell'azienda
- assegnazioni di mansione
- luoghi effettivi
- macchinari effettivi

Ruolo:
- traduce il core nel caso concreto
- puo' attivare o rafforzare suggerimenti

### 3. Rischio valutato / personalizzato dal consulente
Origine:
- decisione professionale esplicita

Possibilita' richieste:
- aggiungere rischi non suggeriti
- escludere rischi suggeriti ma non pertinenti
- declassare o rafforzare la rilevanza
- personalizzare motivazioni e note
- adattare misure suggerite

Ruolo:
- e' il punto in cui la valutazione professionale prende il controllo del modello

### 4. Rischio finale effettivo assunto dal sistema
Origine:
- risultato consolidato tra suggerimenti, contesto e decisioni professionali

Ruolo:
- base operativa per misure, coperture, dashboard, scadenze e DVR

## Proposta concreta di layering

### Layer 1 - Core standard
Contiene:
- cataloghi standard
- mapping standard sorgente -> rischio
- suggerimenti standard rischio -> misure

Caratteristica:
- riusabile e centralizzato
- mai vincolante in modo rigido

### Layer 2 - Contesto tenant / azienda
Contiene:
- aziende, sedi, lavoratori
- cataloghi tenant-level
- istanze operative reali
- assegnazioni ed esposizioni

Caratteristica:
- rappresenta il caso reale del cliente
- rende concreto cio' che il core suggerisce in astratto

### Layer 3 - Valutazione professionale del consulente
Contiene:
- override
- conferme
- esclusioni
- integrazioni manuali
- motivazioni e note

Caratteristica:
- punto di controllo professionale
- il consulente ha sempre l'ultima parola

### Layer 4 - Stato operativo finale
Contiene:
- rischio finale assunto
- misure finali attese o applicate
- stato di copertura
- criticita' e scadenze

Caratteristica:
- e' la verita' operativa usata dal prodotto
- deve restare spiegabile rispetto ai layer precedenti

## Ruolo degli override del consulente

## Decisione strutturale
L'override del consulente non deve essere modellato come eccezione marginale.
Deve essere parte nativa del dominio.

## Effetti richiesti sul modello
- ogni suggerimento deve poter essere:
  - accettato
  - modificato
  - escluso
  - integrato
- ogni rischio finale deve poter riportare almeno:
  - origine core
  - contributo del contesto
  - intervento professionale
  - stato finale assunto

## Compatibilita' futura con audit trail
Non serve ora un audit trail avanzato completo, ma il modello deve nascere compatibile con:
- autore della decisione
- motivazione
- timestamp
- tipo di intervento

## Decisioni da congelare subito
- multi-tenant come ownership primaria di aziende e cataloghi tenant-level
- distinzione tra cataloghi e istanze operative per mansioni, macchinari e luoghi
- uso di relazioni esplicite per assegnazioni ed esposizioni operative
- separazione strutturale tra suggerimento core, contesto, override consulente e stato finale
- centralita' delle tre sorgenti del rischio come input attivi del motore
- lavoratore appartenente a una sola azienda nel modello MVP
- sede come contenitore operativo reale, distinto da luogo

## Decisioni da lasciare elastiche
- profondita' della tassonomia delle mansioni
- profondita' della tassonomia di luoghi e macchinari
- modalita' esatta con cui calcolare intensita' o priorita' del rischio
- granularita' finale dei ruoli utente
- dettaglio dello storico temporale di assegnazioni ed esposizioni
- livello di sofisticazione dell'audit trail

## Impatto sugli step esecutivi

### Valutazione generale
`SC-DOM-001/002/003` restano concettualmente validi, ma conviene rifinirli per allinearsi meglio al modello emerso.

### Raffinamento proposto di SC-DOM-001
Titolo consigliato:
`Tenant, utenti, aziende e sedi`

Perimetro:
- tenant
- membership utenti
- aziende
- sedi
- ownership e confini di accesso

Motivo del raffinamento:
- chiarire subito che aziende e sedi appartengono al tenant via azienda
- evitare di rimandare troppo la semantica delle ownership

### Raffinamento proposto di SC-DOM-002
Titolo consigliato:
`Lavoratori ed esposizioni operative di base`

Perimetro:
- lavoratori
- appartenenza azienda
- sede prevalente
- struttura per assegnazioni operative future

Motivo del raffinamento:
- il lavoratore va pensato subito come nodo esposto del rischio, non come semplice anagrafica HR

### Raffinamento proposto di SC-DOM-003
Titolo consigliato:
`Cataloghi e istanze operative di mansioni, macchinari e luoghi`

Perimetro:
- livello catalogo/tipologia
- livello istanza reale
- relazioni con lavoratori, sedi e aziende

Motivo del raffinamento:
- e' il punto piu' delicato del dominio iniziale
- conviene modellarlo bene subito per non rifare il motore rischio piu' avanti

### Split opzionale consigliato
Se si vuole ridurre rischio implementativo, `SC-DOM-003` puo' essere spezzato in:
- `SC-DOM-003A` cataloghi e tipologie
- `SC-DOM-003B` istanze operative e relazioni di assegnazione/esposizione

Questa scelta puo' essere utile se il team vuole validare prima il linguaggio del dominio e poi la sua concretizzazione operativa.

## Proposta operativa aggiornata
La prossima ripartenza consigliata e':
1. `SC-DOM-001` tenant, utenti, aziende, sedi
2. `SC-DOM-002` lavoratori ed esposizioni operative di base
3. `SC-DOM-003A` cataloghi di mansioni, tipologie macchinari, tipologie luoghi
4. `SC-DOM-003B` assegnazioni e istanze operative: mansioni assegnate, macchinari concreti, luoghi concreti, esposizioni lavoratore

Questa versione e' preferibile al vecchio blocco unico perche':
- separa meglio ownership e contesto
- congela presto il confine tra catalogo e realta' operativa
- prepara il motore rischio con dati semanticamente leggibili

## Punti ancora aperti
- se i cataloghi tenant-level debbano clonare o estendere il core
- quanto rendere esplicita gia' in MVP la nozione di gruppo omogeneo di lavoratori
- quanto dettaglio temporale introdurre nelle assegnazioni operative iniziali
- se il primo stato finale del rischio debba essere materializzato come entita' dedicata o come vista consolidata iniziale

## Prossima ripartenza consigliata
Aprire il prossimo task esecutivo su:
- ownership tenant -> aziende -> sedi
- modello lavoratore come nodo di esposizione
- distinzione cataloghi vs istanze per le tre sorgenti del rischio

Solo dopo questo blocco conviene entrare nel catalogo rischi e nel motore profilo rischio.

## Stato finale
- domain modeling fondativo affinato e formalizzato
- nessuna implementazione business eseguita
- repository lasciato in stato `PRE-DEPLOY / NO-DEPLOY`

