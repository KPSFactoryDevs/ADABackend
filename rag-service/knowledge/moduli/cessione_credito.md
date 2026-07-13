# Modulo Cessione del Credito

## Descrizione
Il modulo Cessione del Credito (Factoring) permette di gestire i clienti e le fatture per operazioni di cessione dei crediti commerciali. Le aziende possono caricare le fatture dei propri clienti e inviarle per una valutazione del rischio creditizio.

## Come Funziona

### Gestione Clienti
1. I clienti vengono creati automaticamente quando si importano fatture da Fatture in Cloud, oppure possono essere creati manualmente.
2. Ogni cliente può avere associati:
   - **Fatture** (XML FatturaPA o PDF)
   - **Bilancio** del cliente
   - **Centrale Rischi** del cliente
   - **Altri documenti** (visure camerali, etc.)

### Upload Fatture
- **Fattura XML**: upload del file XML in formato FatturaPA. ADA estrae automaticamente cedente, cessionario, importi e dettaglio righe.
- **Fattura PDF**: upload del PDF della fattura. ADA usa l'intelligenza artificiale (GPT-4o Vision) per estrarre i dati dalla fattura scansionata.

### Valutazione del Rischio
Per ogni cliente, ADA mostra:
- **Score bilancio**: punteggio derivato dall'analisi del bilancio del cliente
- **Score CR**: punteggio derivato dall'analisi della Centrale Rischi del cliente
- **Fatturato**: totale fatturato verso il cliente

### Invio per Valutazione
L'utente può selezionare un cliente e "inviarlo per valutazione". Il sistema prepara un pacchetto con tutti i documenti del cliente (fatture, bilancio, CR) e lo invia via email all'ufficio crediti per la valutazione finale.

## Flusso Operativo
1. Importa le fatture dei clienti (XML o PDF)
2. Per ogni cliente, carica bilancio e CR (opzionale ma consigliato)
3. Verifica gli score automatici
4. Seleziona i clienti idonei alla cessione
5. Invia la pratica per valutazione

## Stato della Valutazione
Ogni cliente ha uno stato:
- **In attesa** — documenti caricati, non ancora inviato
- **Inviato** — pratica inviata per valutazione
- **Approvato** — cessione approvata
- **Rifiutato** — cessione rifiutata
