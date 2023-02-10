<!DOCTYPE html>
<html>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <title>Report Allerta</title>
    </head>
    <body>

    <h3 style="margin-bottom:30px;">Scoring Generale: {{$dati['pageData']['FinalScore']}}</h3>

    <hr>
    <h3 style="margin-bottom:30px;">Scoring per Sezione</h3>

        <table>
            @foreach($dati['GeneralScore'] as $key => $singleStatoPatrimonialeAttivo)
                <tr>
                    <td colspan="6" class="dati_impresa">{{ str_replace('_', ' ', $key) }}</td>
                    <td colspan="6" class="dati_impresa bgcolor-dati-impresa">{{ $singleStatoPatrimonialeAttivo }}</td>
                </tr>
            @endforeach
        </table>


    <h3 style="margin-bottom:30px;margin-top:50px;">Analisi Bilancio</h3>


    <h4>Scoring Analisi Bilancio: {{$dati['pageData']['ValutazioneGeneraleBilancio']['Score']}}</h4>


    <table>
        <tr>
            <th colspan="6">Indice</th>
            <th colspan="6">Valore</th>
            <th colspan="6">Giudizio</th>
        </tr>
        <tr>
        @foreach($dati['pageData']['ValutazioneGeneraleBilancio']['Giudizi'] as $key => $singleStatoPatrimonialeAttivo)
            @if($singleStatoPatrimonialeAttivo != false)
            <tr>
                <td colspan="6" class="dati_impresa">{{ str_replace('_', ' ', $key) }}</td>
                <td colspan="6" class="dati_impresa bgcolor-dati-impresa">{{ $singleStatoPatrimonialeAttivo['Scoring'] }}</td>
                <td colspan="6" class="dati_impresa bgcolor-dati-impresa">{{ $singleStatoPatrimonialeAttivo['Giudizio'] }}</td>
            </tr>
            @endif
            @endforeach
        </tr>

    </table>


    <h3 style="margin-bottom:30px;margin-top:50px;">Analisi Centrale Rischi</h3>

    <table>
        <tr>
            <th><b>Scoring CR Andamentale</b></th>

        </tr>
        <tr>
            <td>Valutazione Negativa Del CR Scoring Che Deriva Dall'analisi Sintetica Della CR</td>
            <td>No</td>
        </tr>
        <tr>
            <td>Sconfini E Ritardi Nei Pagamenti</td>
        </tr>
        <tr>
            <td>Sconfini Significativi E/O Ripetuti Nel Corso Degli Ultimi 12 Mesi</td>
            <td>Si</td>
        </tr>
        <tr>
            <td>Mancato Pagamento Di Finanziamenti O Di Altre Scadenze</td>
            <td>Si</td>
        </tr>
        <tr>
            <td>Aumento Delle Garanzie</td>
        </tr>
        <tr>
            <td>Aumento Delle Richieste Di Garanzie Su Beni Aziendali</td>
            <td>Si</td>
        </tr>
        <tr>
            <td>Aumento Delle Garanzie Concesse Su Esposizioni Di Altri Soggetti</td>
            <td>No</td>
        </tr>
        <tr>
            <td>Insoluti Portafoglio Anticipi</td>
        </tr>
        <tr>
            <td>Aumento Significativo O Peso Elevato Di Incidenza Insoluti Su Anticipo Crediti</td>
            <td>No</td>
        </tr>
        <tr>
            <td>Aumento Affidamenti E Utilizzi</td>
        </tr>
        <tr>
            <td>Aumento Significativo Delle Richieste Di Affidamenti Di Cassa</td>
            <td>Si</td>
        </tr>
        <tr>
            <td>Richiesta Finanziamenti Straordinari</td>
            <td>No</td>
        </tr>
        <tr>
            <td>Crescita Continua E Rilevante Di Utilizzi Per Liquidità Di Cassa</td>
            <td>No</td>
        </tr>
        <tr>
            <td>Crescita Continua E Rilevante Di Utilizzi Per Smobilizzo Crediti Commerciali O Tensione Finanziaria</td>
            <td>Si</td>
        </tr>
        <tr>
            <td>Rientro Linee Anticipi, Cassa E Firma</td>
        </tr>
    </table>


    <h3 style="margin-bottom:30px;margin-top:50px;">Questionario AS IS</h3>

    <h5> Anomalie Dei Pagamenti Verso Controparti Commerciali</h5>
    <table>
        <tr>

            <th>Valutazione Negativa Del CR Scoring Che Deriva Dall'analisi Sintetica Della CR</th>
            <th>Esito</th>
            <th>Spiegazioni</th>
        </tr>
        <tr>
            <td>Sono presenti fatture e avvisi di pagamento di cui non si è rispettata la scadenza?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Ci sono ritardi nei pagamenti ai fornitori superiori a 90 giorni per un ammontare superiore a quello dei debiti non scaduti?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>I fornitori hanno modificato le condizioni di pagamento delle forniture (ad es. pagamento dilazionato a pagamento anticipato)?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Avete inoltrato richieste di rimodulazione delle scadenze nei pagamenti richiedendo di aumentare la dilazione?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Sono state modificate le condizioni di incasso da parte dei clienti con un conseguente allungamento dei tempi di incasso?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Sono stati registrati mancati incassi per importi considerevoli superiori a 90gg?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Sono presenti contenziosi in atto con clienti o fornitori?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Sono presenti criticità con clienti derivanti da non conformità o ritardi?</td>
            <td>No</td>
            <td></td>
        </tr>
    </table>



    <h3 style="margin-bottom:30px;margin-top:50px;">Questionario TO BE</h3>

    <h5>Anomalie Dei Pagamenti Verso Controparti Commerciali</h5>
    <table>
        <tr>
            <th>Valutazione Negativa Del CR Scoring Che Deriva Dall'analisi Sintetica Della CR</th>
            <th>Esito</th>
            <th>Spiegazioni</th>
        </tr>
        <tr>
            <td>Sono presenti fatture e avvisi di pagamento di cui non si è rispettata la scadenza?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Ci sono ritardi nei pagamenti ai fornitori superiori a 90 giorni per un ammontare superiore a quello dei debiti non scaduti?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>I fornitori hanno modificato le condizioni di pagamento delle forniture (ad es. pagamento dilazionato a pagamento anticipato)?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Avete inoltrato richieste di rimodulazione delle scadenze nei pagamenti richiedendo di aumentare la dilazione?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Sono state modificate le condizioni di incasso da parte dei clienti con un conseguente allungamento dei tempi di incasso?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Sono stati registrati mancati incassi per importi considerevoli superiori a 90gg?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Sono presenti contenziosi in atto con clienti o fornitori?</td>
            <td>No</td>
            <td></td>
        </tr>
        <tr>
            <td>Sono presenti criticità con clienti derivanti da non conformità o ritardi?</td>
            <td>No</td>
            <td></td>
        </tr>
    </table>



    <style>

        table {
            border-right: dotted black;
            border-left: dotted black;
            width:100%;
        }

        th, td  {
            border-top: dotted black;
            border-bottom: dotted black;
            padding: 5px;
        }

        .dati_impresa {
            font-size: 12px;
            font-weight: 400!important;
        }

        .heading {
            text-align: center;
            padding: 5px;
            font-size: 12px;
        }
        .header-info {
            font-size: 10px;
            vertical-align: top;
        }
        .bgcolor-dati-impresa {
            background-color: #ccffff;
        }
        .size-12 {
            font-size: 12px;
        }
        .bgcolor-alert {
            background-color: #99ff66;
        }
    </style>

    </body>
</html>
