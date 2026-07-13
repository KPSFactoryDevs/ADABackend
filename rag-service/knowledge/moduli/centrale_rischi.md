# Modulo Centrale Rischi

## Descrizione
Il modulo Centrale Rischi (CR) permette di importare e analizzare i dati della Centrale Rischi di Banca d'Italia. La Centrale Rischi contiene informazioni sull'esposizione creditizia dell'azienda presso il sistema bancario.

## Come Importare la Centrale Rischi
1. Vai alla sezione **Centrale Rischi** dalla sidebar.
2. Clicca su **Importa CR**.
3. Carica il file PDF della Centrale Rischi (scaricato dalla Banca d'Italia).
4. ADA estrae automaticamente tutti i dati: istituti bancari, categorie di credito, importi accordati, utilizzati e garanzie.

## Analisi Disponibili

### Analisi Andamentale
L'analisi andamentale mostra l'evoluzione nel tempo dell'esposizione creditizia:
- **Accordato**: il limite di credito concesso dalle banche
- **Utilizzato**: quanto effettivamente utilizzato
- **Garanzie**: garanzie personali e reali
- Puoi filtrare per periodo (trimestrale, semestrale, annuale) e per banca specifica.

### Analisi Dettagliata (Trimestrale)
Mostra i dati disaggregati per:
- Singolo istituto bancario
- Categoria di credito (rischi autoliquidanti, a scadenza, a revoca, di firma, di cassa)
- Stato del credito (in bonis, ristrutturato, incaglio, sofferenza)

### Indicatori CR Monitorati
- **Tensione finanziaria**: rapporto tra utilizzato e accordato (segnale di stress se > 80%)
- **Sconfinamenti**: utilizzo oltre il limite accordato
- **Segnalazioni**: eventuali segnalazioni negative (sofferenze, incagli)
- **Richieste di prima informazione**: quante banche hanno richiesto informazioni (indice di shopping creditizio)
- **Garanzie**: copertura delle esposizioni con garanzie reali o personali

### Riepilogo CR
Un riepilogo sintetico che mostra:
- Totale accordato operativo
- Totale utilizzato
- Percentuale di utilizzo
- Numero di istituti segnalanti
- Presenza di segnalazioni negative

## Predefinito
Come per i bilanci, puoi impostare una CR come "predefinita" — verrà usata automaticamente nel sistema di allerta e nei report.

## Report PDF
Il report CR include:
- Tabella riepilogativa delle esposizioni per banca
- Grafici andamentali dell'accordato vs utilizzato
- Evidenza di criticità (sconfinamenti, sofferenze)
- Analisi per categoria di credito
