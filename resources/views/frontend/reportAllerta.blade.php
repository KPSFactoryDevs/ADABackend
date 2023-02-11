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
            font-size: 14px;
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


    <h3 style="margin-bottom:30px;margin-top:100px;">Scoring Centrale Rischi {{str_replace(",",".",$dati['pageData']['scoreCR'])*10}}/10</h3>

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
            <th colspan="2"><b>Esito</b></th>
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
            <td class="dati_impresa" colspan="10">Mancato Pagamento Di Finanziamenti O Di Altre Scadenze</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][3])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <th class="heading" colspan="10"><b>Aumento Delle Garanzie</b></th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Aumento Delle Richieste Di Garanzie Su Beni Aziendali</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][4])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Aumento Delle Garanzie Concesse Su Esposizioni Di Altri Soggetti</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][5])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <th class="heading" colspan="10"><b>Insoluti Portafoglio Anticipi</b></th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Aumento Significativo O Peso Elevato Di Incidenza Insoluti Su Anticipo Crediti</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][6])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <th class="heading" colspan="10"><b>Aumento Affidamenti E Utilizzi</b></th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Aumento Significativo Delle Richieste Di Affidamenti Di Cassa</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][7])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Richiesta Finanziamenti Straordinari</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][8])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Crescita Continua E Rilevante Di Utilizzi Per Liquidità Di Cassa</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][9])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Crescita Continua E Rilevante Di Utilizzi Per Smobilizzo Crediti Commerciali O Tensione Finanziaria</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][10])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>

        <tr>
            <th class="heading" colspan="10"><b>Rientro Linee Anticipi, Cassa E Firma</b></th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Rientri Nelle Linee Di Cassa</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][11])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Rientri Nelle Linee Anticipi Sbf/Fatture	</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][12])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Rientri Nelle Linee Di Crediti Per Firma</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][13])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>


        <tr>
            <th class="heading" colspan="10"><b>Segnalazioni Pregiudizievoli</b></th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Presenza Sconfinamenti Fra 90gg E 180gg Oppure Oltre I 180gg</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][14])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Presenza Di Garanzie Attivate Con Esito Negativo</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][15])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Presenza Di Sofferenze O Crediti Passati A Perdita	</td>
            <td class="dati_impresa" colspan="2">
                @if($dati['pageData']['crAlerts'][16])
                    Si
                @else
                    No
                @endif
            </td>
        </tr>
    </table>




    <h3 style="margin-bottom:30px;margin-top:100px;">Questionari AS IS (Situazione Aziendale Corrente)</h3>



    <h5> Anomalie Dei Pagamenti Verso Controparti Commerciali</h5>


    <table>
        <tr>
            <th class="heading" colspan="10">Valutazione Negativa Dell'analisi Sintetica CR</th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Sono presenti fatture e avvisi di pagamento di cui non si è rispettata la scadenza?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['1-1']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['1-1']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Ci sono ritardi nei pagamenti ai fornitori superiori a 90 giorni per un ammontare superiore a quello dei debiti non scaduti?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['1-2']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['1-2']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">I fornitori hanno modificato le condizioni di pagamento delle forniture (ad es. pagamento dilazionato a pagamento anticipato)?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['1-3']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['1-3']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Avete inoltrato richieste di rimodulazione delle scadenze nei pagamenti richiedendo di aumentare la dilazione?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['1-4']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['1-4']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa"  colspan="10">Sono state modificate le condizioni di incasso da parte dei clienti con un conseguente allungamento dei tempi di incasso?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['1-5']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['1-5']['Details']}}</b></td>
        </tr>



        <tr>
            <td class="dati_impresa" colspan="10">Sono stati registrati mancati incassi per importi considerevoli superiori a 90gg?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['1-6']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['1-6']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Sono presenti contenziosi in atto con clienti o fornitori?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['1-7']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['1-7']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Sono presenti criticità con clienti derivanti da non conformità o ritardi?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['1-8']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['1-8']['Details']}}</b></td>
        </tr>

    </table>










    <h5> Anomalie Gestionali</h5>


    <table>
        <tr>
            <th class="heading" colspan="10">Parametro Analizzato</th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Sono stati registrate perdite di fette di mercato, commesse o clienti importanti?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-1']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-1']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Si sono verificate perdite di membri della direzione o di figure di responsabilità strategiche senza una loro sostituzione?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-2']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-2']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Sono presenti problemi con la gestione del personale?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-3']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-3']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Ci sono ritardi nei pagamenti relativi alle retribuzioni superiori a 60gg per un ammontare maggiore della metà dell'ammontare mensile della retribuzione?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-4']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-4']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa"  colspan="10">Sono presenti nel mercato nuove aziende concorrenti che possono mettere in difficoltà la nostra azienda?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-5']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-5']['Details']}}</b></td>
        </tr>



        <tr>
            <td class="dati_impresa" colspan="10">Sono state richieste dilazioni alle banche su finanziamenti in essere?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-6']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-6']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Vi è la possibilità di incorrere in problemi inerenti l'approviggionamento di prodotti fondamentali o in aumenti drastici dei prezzi di acquisto delle materie prime?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-7']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-7']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Sono state registrate variazioni nell'assetto societario?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-8']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-8']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Le scelte gestionali portate avanti dall'amministratore (o consiglio di amministrazione) risultano in contrasto con la mission aziendale e con la vision della direzione?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-9']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-9']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Vi è possibilità che si verifichino eventi catastrofici per i quali non si dispone di una adeguata copertura assicurativa?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['2-10']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['2-10']['Details']}}</b></td>
        </tr>
    </table>








    <h5> Minacce Erariali e Rischi Caratteristici</h5>


    <table>
        <tr>
            <th class="heading" colspan="10">Parametro Analizzato</th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Sono presenti mancati pagamenti verso Agenzia delle Entrate ed Enti di riscossione per oltre 6 mesi</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['3-1']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['3-1']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Sono presenti mancati pagamenti verso INPS e INAIL per oltre 6 mesi?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['3-2']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['3-2']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Vi sono procedimenti legali o regolamentari in corso la cui sorte negativa potrebbe comportare richieste di risarcimento alle quali l'impresa potrebbe non far fronte?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['3-3']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['3-3']['Details']}}</b></td>
        </tr>

        <tr>
            <td class="dati_impresa" colspan="10">Sono entrate in atto modifiche di leggi o regolamenti o politiche governative che potrebbero influenzare negativamente l'impresa?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['3-4']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['3-4']['Details']}}</b></td>
        </tr>
    </table>






    <h5> Anomalie Dei Pagamenti Verso Controparti Commerciali</h5>

    <table>
        <tr>
            <th class="heading" colspan="10">Parametro Analizzato</th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Sono presenti iscrizioni di ipoteche giudiziarie, pegni e forme tecniche di prelazioni sui beni aziendali?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['4-1']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['4-1']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Sono stati ricevuti decreti ingiuntivi ed atti ricognitivi di avvio di azioni per il recupero di crediti?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['4-2']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['4-2']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">L'azienda ha subito il protesto di assegni e cambiali?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['4-3']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['4-3']['Details']}}</b></td>
        </tr>

        <tr>
            <td class="dati_impresa" colspan="10">Sono in atto azioni volte alla liquidazione dell'azienda o alla cessazione dell'attività?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['4-4']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['4-4']['Details']}}</b></td>
        </tr>

        <tr>
            <td class="dati_impresa" colspan="10">Vi sono istanze di fallimento avanzate dai creditori aziendali?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['4-5']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['4-5']['Details']}}</b></td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">Si è verificato il default o il fallimento di garanti e default o fallimento dei garanti legati all'azienda (rischio infragruppo)?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayQuestionarioAsIs']['4-6']['Result']}}</td>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="12" style="background:lightgrey;">Spiegazioni: <b>{{$dati['pageData']['arrayQuestionarioAsIs']['4-6']['Details']}}</b></td>
        </tr>
    </table>



    <h3 style="margin-bottom:30px;margin-top:100px;">Questionario TO BE (Situazione Aziendale Futura)</h3>

    <h5>Al fine di valutare l'azienda attraverso un approccio previsionale è necessario rispondere alle seguenti domande.</h5>
    <table>
        <tr>
            <th class="heading" colspan="10">Domanda</th>
            <th colspan="2"><b>Esito</b></th>
        </tr>
        <tr>
            <td class="dati_impresa" colspan="10">Per i prossimi 6 mesi l'azienda prevede un fatturato, rispetto al semestre precedente:</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>



        <tr>
            <td class="dati_impresa" colspan="10">L'azienda prevede di chiedere nuovi finanziamenti nei prossimi 6 mesi?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">L'azienda prevede costi di gestione (fissi e variabili) per i prossimi 6 mesi rispetto al semestre precedente:</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>

        <tr>
            <td class="dati_impresa" colspan="10">Su quanti clienti è concentrato il fatturato dei prossimi 6 mesi?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>

        <tr>
            <td class="dati_impresa"  colspan="10">L'azienda utilizza un sistema di pianificazione e controllo di gestione?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>



        <tr>
            <td class="dati_impresa" colspan="10">L'azienda prevede costi straordinari per i prossimi 6 mesi? (es. manutenzioni starordinarie, minusvalenze da conferimenti aziendali, da ristrutturazione, da espropri, da cessione di beni o contenziosi, oneri per le multe ecc.)</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">L' prevede di utilizzare al limite (o oltre) le disponibilità per liquidità di cassa nei prossimi 6 mesi?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>



        <tr>
            <td class="dati_impresa" colspan="10">L'azienda prevede flussi di cassa della gestione operativa (ricavi esigibili - costi da sostenere nei prossimi 6 mesi) sufficienti a coprire gli impegni finanziari (quote capitali sui finanziamenti e oneri finanziari) dei prossimi 6 mesi (DSCR)?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>


        <tr>
            <td class="dati_impresa" colspan="10">L'azienda prevede che le disponibilità di cassa attuali e le entrate (ordinarie + straordinarie) dei prossimi 6 mesi saranno in grado di coprire tutte le passività/uscite (impegni finanziari e commerciali) dei prossimi 6 mesi (margine di tesorerie positivo)?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>

        <tr>
            <td class="dati_impresa" colspan="10">L'azienda prevede tempi medi di pagamento ai fornitori (uscite) inferiori ai tempi medi di incasso dai clienti (entrate)?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>

        <tr>
            <td class="dati_impresa" colspan="10">L'azienda prevede reiterati e significativi ritardi nei pagamenti verso terzi (fornitori, dipendenti, erario, enti previdenziali, finanziamenti) nei prossimi 6 mesi?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>

        <tr>
            <td class="dati_impresa" colspan="10">L'azienda prevede di utilizzare al limite (o oltre) le disponibilità del castelletto anticipi nei prossimi 6 mesi?</td>
            <td class="dati_impresa" colspan="2">{{$dati['pageData']['arrayForwardLookingToBe']['forwardLooking1']}}</td>
        </tr>

    </table>





    </body>
</html>
