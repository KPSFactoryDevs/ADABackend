<!DOCTYPE html>
<html>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <title>Report Allerta </title>


    </head>
    <body>
    <style>

        table {
            border-right: dotted black;
            border-left: dotted black;
            width:100%;
            margin-bottom:30px;
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
    </style>

    <h3 style="margin-bottom:30px;">Scoring Generale: {{$dati['pageData']['FinalScore']}}</h3>

    <hr>


        <table>
            <tr>
                <th colspan="12" class="heading">Analisi per Area d'interesse</th>
            </tr>
            @foreach($dati['GeneralScore'] as $key => $singleStatoPatrimonialeAttivo)
                <tr>
                    <td colspan="6" class="dati_impresa">{{ str_replace('_', ' ', $key) }}</td>
                    <td colspan="6" class="dati_impresa bgcolor-dati-impresa">{{ $singleStatoPatrimonialeAttivo }}</td>
                </tr>
            @endforeach
        </table>





    <h4>Scoring Analisi Bilancio: {{(float)str_replace(",", ".", $dati['pageData']['ValutazioneGeneraleBilancio']['Score'])*10}} /10</h4>


    <table>
        <tr>
            <th colspan="18" class="heading">Analisi di Bilancio</th>
        </tr>
        <tr>
            <th colspan="6">Indice</th>
            <th colspan="6">Valore</th>
            <th colspan="6">Giudizio</th>
        </tr>

        @foreach($dati['pageData']['ValutazioneGeneraleBilancio']['Giudizi'] as $key => $singleStatoPatrimonialeAttivo)
            @if($singleStatoPatrimonialeAttivo != false)
            <tr>
                <td colspan="6" class="dati_impresa">{{ str_replace('_', ' ', $key) }}</td>
                <td colspan="6" class="dati_impresa bgcolor-dati-impresa">{{ $dati['pageData']['bilancioData']['Indici']['Advanced'][$key]}}%</td>
                <td colspan="6" class="dati_impresa bgcolor-dati-impresa">{{ $singleStatoPatrimonialeAttivo['Giudizio'] }}</td>
            </tr>
            @endif
            @endforeach


    </table>


    <h3 style="margin-bottom:30px;margin-top:100px;">Analisi Centrale Rischi</h3>

    <table>
        <tr>
            <th colspan="12" class="heading">Analisi Centrale Rischi</th>
        </tr>
        <tr>
            <th class="heading" colspan="10"><b>Scoring Centrale Rischi Andamentale</b></th>
            <th colspan="2"><b>Esito</b></th>
        </tr>



        <tr>
            <td class="dati_impresa" colspan="10">Valutazione Negativa Del CR Scoring Che Deriva Dall'analisi Sintetica Della CR</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][1])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <th class="heading" colspan="10"><b>Sconfini E Ritardi Nei Pagamenti</b></th>
            <th><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Sconfini Significativi E/O Ripetuti Nel Corso Degli Ultimi 12 Mesi</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][2])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Mancato Pagamento Di Finanziamenti O Di Altre Scadenze</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][3])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <th class="heading"><b>Aumento Delle Garanzie</b></th>
            <th><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa">Aumento Delle Richieste Di Garanzie Su Beni Aziendali</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][4])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Aumento Delle Garanzie Concesse Su Esposizioni Di Altri Soggetti</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][5])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <th class="heading"><b>Insoluti Portafoglio Anticipi</b></th>
            <th><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa">Aumento Significativo O Peso Elevato Di Incidenza Insoluti Su Anticipo Crediti</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][6])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <th class="heading"><b>Aumento Affidamenti E Utilizzi</b></th>
            <th><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa">Aumento Significativo Delle Richieste Di Affidamenti Di Cassa</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][7])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Richiesta Finanziamenti Straordinari</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][8])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Crescita Continua E Rilevante Di Utilizzi Per Liquidità Di Cassa</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][9])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Crescita Continua E Rilevante Di Utilizzi Per Smobilizzo Crediti Commerciali O Tensione Finanziaria</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][10])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>

        <tr>
            <th class="heading"><b>Rientro Linee Anticipi, Cassa E Firma</b></th>
            <th><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa">Rientri Nelle Linee Di Cassa</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][11])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Rientri Nelle Linee Anticipi Sbf/Fatture	</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][12])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Rientri Nelle Linee Di Crediti Per Firma</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][13])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>


        <tr>
            <th class="heading"><b>Segnalazioni Pregiudizievoli</b></th>
            <th><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa">Presenza Sconfinamenti Fra 90gg E 180gg Oppure Oltre I 180gg</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][14])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Presenza Di Garanzie Attivate Con Esito Negativo</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][15])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa">Presenza Di Sofferenze O Crediti Passati A Perdita	</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][16])
                    Si
                @else
                    No
                @endif
            </td>
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
            <td class="dati_impresa">Sono presenti fatture e avvisi di pagamento di cui non si è rispettata la scadenza?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Ci sono ritardi nei pagamenti ai fornitori superiori a 90 giorni per un ammontare superiore a quello dei debiti non scaduti?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">I fornitori hanno modificato le condizioni di pagamento delle forniture (ad es. pagamento dilazionato a pagamento anticipato)?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Avete inoltrato richieste di rimodulazione delle scadenze nei pagamenti richiedendo di aumentare la dilazione?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Sono state modificate le condizioni di incasso da parte dei clienti con un conseguente allungamento dei tempi di incasso?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Sono stati registrati mancati incassi per importi considerevoli superiori a 90gg?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Sono presenti contenziosi in atto con clienti o fornitori?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Sono presenti criticità con clienti derivanti da non conformità o ritardi?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
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
            <td class="dati_impresa">Sono presenti fatture e avvisi di pagamento di cui non si è rispettata la scadenza?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Ci sono ritardi nei pagamenti ai fornitori superiori a 90 giorni per un ammontare superiore a quello dei debiti non scaduti?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">I fornitori hanno modificato le condizioni di pagamento delle forniture (ad es. pagamento dilazionato a pagamento anticipato)?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Avete inoltrato richieste di rimodulazione delle scadenze nei pagamenti richiedendo di aumentare la dilazione?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Sono state modificate le condizioni di incasso da parte dei clienti con un conseguente allungamento dei tempi di incasso?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Sono stati registrati mancati incassi per importi considerevoli superiori a 90gg?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Sono presenti contenziosi in atto con clienti o fornitori?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
        <tr>
            <td class="dati_impresa">Sono presenti criticità con clienti derivanti da non conformità o ritardi?</td>
            <td class="dati_impresa">No</td>
            <td class="dati_impresa"></td>
        </tr>
    </table>




    </body>
</html>
