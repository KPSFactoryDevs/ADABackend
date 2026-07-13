# Modulo Sistema di Allerta

## Descrizione
Il Sistema di Allerta è uno strumento previsto dal Codice della Crisi d'Impresa (D.Lgs. 14/2019) per la rilevazione precoce degli indicatori di crisi. ADA implementa un questionario strutturato e analisi automatiche per valutare lo stato di salute dell'impresa.

## Come Funziona
Il sistema richiede due input principali:
1. **Un bilancio** (analisi degli indici finanziari)
2. **Una Centrale Rischi** (analisi dell'esposizione creditizia)

Con questi due documenti, ADA calcola automaticamente gli indicatori di allerta.

## Questionario di Autovalutazione (ASIS)
Il questionario ASIS è uno strumento di autovalutazione che l'utente compila per verificare aspetti qualitativi dell'azienda:
- Rapporti commerciali e con i fornitori
- Gestione aziendale e organizzazione
- Eventi pregiudizievoli
- Rischi caratteristici del settore
- Prospettive future (forward looking)

Ogni sezione produce un punteggio da "Robustezza" a "Fragilità elevata".

## Indicatori Calcolati

### Da Bilancio
- DSCR (Debt Service Coverage Ratio)
- Sostenibilità degli oneri finanziari
- Adeguatezza patrimoniale
- Liquidità e cashflow

### Da Centrale Rischi
- Tensione finanziaria
- Sconfinamenti persistenti
- Concentrazione bancaria
- Segnalazioni negative

### Allerte Specifiche
- **Agenzia delle Entrate**: verifica debiti fiscali rilevanti
- **INPS**: verifica debiti previdenziali
- **Retribuzioni**: verifica sostenibilità del costo del lavoro
- **Fornitori**: verifica eventuali ritardi nei pagamenti

## Giudizi
Ogni area produce un giudizio su 5 livelli:
1. **Robustezza** — Situazione eccellente
2. **Resilienza** — Buona situazione, qualche attenzione
3. **Vulnerabilità** — Situazione da monitorare
4. **Fragilità** — Situazione critica, intervento necessario
5. **Fragilità elevata** — Situazione molto critica, rischio concreto di crisi

## Forward Looking
L'analisi Forward Looking valuta le prospettive future dell'azienda sulla base di:
- Piano industriale
- Proiezioni di fatturato e margini
- Scenario di mercato
- Capacità di generare cassa futura

## Report PDF
Il report completo del Sistema di Allerta include:
- Risultati del questionario
- Indici calcolati con giudizio
- Grafico radar delle aree di rischio
- Raccomandazioni operative
