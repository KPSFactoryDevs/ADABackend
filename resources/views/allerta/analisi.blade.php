@extends('backend.layouts.app')

@section('content')
    <div class="panel panel-default">
        <div class="panel-body">
            <dl class="dl-horizontal">
                <h1 class="text-center">Sistema Allerta Basic</h1>
                @foreach($basic as $anno=>$array)
                    <h2 class="text-center">Anno {{$anno}}</h2>
                    <table class="table table-bordered table-light smaller-table ">
                        <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Indice</th>
                                <th class="text-center" scope="col">Valore</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($array as $data=>$val)
                            <tr>
                                <td class="text-center round-table" style="width: 50%" scope="row"><?=str_replace("_", " ", $data);?></td>
                                <td class="text-center round-table" style="width: 50%" scope="row"><?= $val = ($data == 'PN_NEGATIVO' )? (number_format((float)$val, 2, ',', '.')).' €' : $val?></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endforeach

                <br>

                <h2 style="margin-bottom: 20px" class="text-center">Sistema Allerta Advanced</h2>

                <p style="margin-bottom: 20px">Il modulo avanzato del sistema di allerta mira a valutare lo stato di salute aziendale in maniera approfondita fornendo indicazioni all'utente sulla presenza di anomalie di bilancio,
                    anomalie derivanti dalla gestione degli affidamenti bancari, anomalie nei rapporti con clienti e fornitori, anomalie gestionali ed anomalie derivanti da eventi pregiudizievoli o eventuali rischi.</p>

                <ul class="nav nav-tabs" style="margin-bottom: 15px">
                    <li class="nav-item">
                        <a href="#analisiBilancio" class="nav-link active" data-toggle="tab">Analisi Bilanci</a>
                    </li>
                    <li class="nav-item">
                        <a href="#analisiCR" class="nav-link" data-toggle="tab">Analisi CR</a>
                    </li>
                    <li class="nav-item">
                        <a href="#questionarioASIS" class="nav-link" data-toggle="tab">Questionario AS IS</a>
                    </li>
                    <li class="nav-item">
                        <a href="#questionarioTOBE" class="nav-link" data-toggle="tab">Questionario TO BE</a>
                    </li>
                    <li class="nav-item">
                        <a href="#risultato" class="nav-link" data-toggle="tab">Risultato</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="analisiBilancio">
                        <p> Tramite l'analisi di una serie di indici, esaminiamo la redditività aziendale, la struttura finanziaria e la sostenibilità dei debiti in modo da individuare le aree che potrebbero generare degli alert e sulle quali intervenire.
                            Per ogni indice esaminato riportiamo il giudizio in una scala che comprende 7 livelli di giudizio (Default, Situazione Grave, Alert, Rischio Alert, Fragilità elevata, Fragilità e Solidità). </p>
                        @foreach($advanced as $anno=>$array)
                        <table class="table table-bordered table-light smaller-table ">
                            <caption> Bilancio esaminato: {{$anno}}</caption>
                            <thead class="thead-dark">
                                <tr>
                                <th class="text-center" scope="col">Indice</th>
                                <th class="text-center" scope="col">Giudizio</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($array as $data=>$val)
                                <tr>
                                    <td class="text-center round-table" style="width: 50%" scope="row"><?=str_replace("_", " ", $data);?></td>
                                    <td class="text-center round-table" style="width: 50%" scope="row"> </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @endforeach
                    </div>
                    <div class="tab-pane fade" id="analisiCR">
                        <p> Tramite l'analisi di una serie di indici, esaminiamo la redditività aziendale, la struttura finanziaria e la sostenibilità dei debiti in modo da individuare le aree che potrebbero generare degli alert e sulle quali intervenire.
                            Per ogni indice esaminato riportiamo il giudizio in una scala che comprende 7 livelli di giudizio (Default, Situazione Grave, Alert, Rischio Alert, Fragilità elevata, Fragilità e Solidità). </p>
                        <table class="table table-bordered table-light smaller-table ">
                            <caption> Centrale rischi esaminata: da Luglio 2016 a Agosto 2019</caption>
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Indice</th>
                                <th class="text-center" scope="col">Giudizio</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="round-table" style="width: 50%" scope="row">Valutazione CR Scoring negativa</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">Numerosità e rilevanza degli sconfini</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">Mancato pagamento scadenze o finanziamenti</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">Aumento di richieste di garanzia</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">Aumento di garanzie concesse per altri soggetti</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">Rilevanza e/o aumento di insoluti su anticipo crediti</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">Aumento di richieste di fidi di cassa</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">Richiesta di finanziamenti straordinari</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="questionarioASIS">
                        <p>Attraverso l'esame delle risposte ad una serie di semplici domande, arricchiamo l'analisi di elementi non riscontrabili dal bilancio e dalla centrale rischi.
                            Il questionario AS IS dovrà essere compilato dal CFO, dall'amministratore o da altre figure che conoscano le dinamiche inerenti la gestione dei rapporti con fornitori e clienti, la gestione aziendale, i rapporti con il fisco ed eventuali rischi e rapporti pregiudizievoli.</p>
                        <table class="table table-bordered table-light smaller-table ">
                            <caption> Rapporti con clienti e fornitori</caption>
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Domanda</th>
                                <th class="text-center" scope="col">Risposta</th>
                                <th class="text-center" scope="col">Giustificazione</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class=" round-table" style="width: 33%" scope="row"> Sono presenti fatture e avvisi di pagamento di cui non si è rispettata la scadenza?</td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 33%" scope="row">Ci sono ritardi nei pagamenti ai fornitori superiori a 90 gg per un ammontare superiore a quello dei debiti non scaduti?</td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 33%" scope="row"> I fornitori hanno modificato le condizioni di pagamento delle forniture es. da pagamento dilazionato a pagamento anticipato?</td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 33%" scope="row"> Avete inoltrato richieste di rimodulazione delle scadenze nei pagamenti richiedendo di aumentare la dilazione?</td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                            </tr>
                            </tbody>
                        </table>
                        <table class="table table-bordered table-light smaller-table ">
                            <caption> Rischi erariali e finanziari</caption>
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Domanda</th>
                                <th class="text-center" scope="col">Risposta</th>
                                <th class="text-center" scope="col">Giustificazione</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class=" round-table" style="width: 33%" scope="row">I pagamenti dei debiti verso INPS, Agenzia delle Entrate ed Enti di riscossione sono stati correttamente rispettati? </td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 33%" scope="row"> Sono presenti iscrizioni di ipoteche giudiziare, pegni o altre forme di prelazione sui beni aziendali?</td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 33%" scope="row"> Sono stati ricevuti decreti ingiuntivi o sono state avviate azioni di recupero dei crediti?</td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 33%" scope="row"> L'azienda ha subito il protesto di assegni e cambiali da essa stessa emessi?</td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                                <td class="text-center round-table" style="width: 33%" scope="row"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="questionarioTOBE">

                        <p> Attraverso l'esame delle risposte ad una serie di semplici domendo, arricchiamo l'analisi di elementi non riscontrabili dal bilancio e dalla centrale rischi che riguardano il futuro dell'azienda dal momento in cui si sta effettuando l'analisi ai prossimi 6 mesi.</p>
                        <table class="table table-bordered table-light smaller-table ">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Nelle previsioni aziendali per i prossimi 6 mesi rispetto ai precedenti il fatturato sarà: </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        In crescita
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Costante
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        In diminuzione
                                    </label>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table class="table table-bordered table-light smaller-table ">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Nelle previsioni aziendali per i prossimi 6 mesi rispetto ai precedenti, i costi di gestione (fissi e variabili) saranno </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        In crescita
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Costante
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        In diminuzione
                                    </label>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table class="table table-bordered table-light smaller-table ">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Quanti clienti rappresentano il 70% del fatturato aziendale? </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Meno di 10 clienti
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Fra 10 e 30 clienti
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Più di 30 clienti
                                    </label>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table class="table table-bordered table-light smaller-table ">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">DSCR. Si prevede che i flussi di cassa derivanti dalla gestione operativa per i prossimi 6 mesi riusciranno a coprire gli impegni finanziari aziendali? </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Si, riteniamo che i flussi di cassa ci permetteranno di coprire tutti gli impegni abbondantemente
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Crediamo di si ma potrebbero presentarsi delle lievi deficienze
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        No, crediamo che saremo costretti a chiedere dilazioni o riscadenziamenti oppure attingere a nuova liquidità
                                    </label>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table class="table table-bordered table-light smaller-table ">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Si prevedono ritardi nei pagamenti verso i fornitori e verso l'erario in maniera reiterata e di valori significativi? </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Si
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Si, ma riteniamo di poterli evitare
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        No
                                    </label>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table class="table table-bordered table-light smaller-table ">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Nelle previsioni aziendali per i prossimi 6 mesi rispetto ai precedenti il fatturato sarà: </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        In crescita
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        Costante
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center round-table" scope="row">
                                    <label class="container" style="text-align: left">
                                        <input type="checkbox" >
                                        <span class="checkmark"></span>
                                        In diminuzione
                                    </label>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                    <div class="tab-pane fade" id="risultato">
                        <p> Il risultato del sistema di allerta ADVANCED deriva dalla valutazione delle aree inerenti l'analisi di bilancio, quella della centrale rischi e delle risposte ai questionari AS IS e TO BE. Attraverso un sistema di presi attribuiti a ciascuna macroarea il sistema elabora il giudizio finale.</p>
                        <table class="table table-bordered table-light smaller-table ">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center" scope="col">Area Esaminata</th>
                                <th class="text-center" scope="col">Risultato</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">1. Analisi bilancio</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">2. Analisi Centrale Rischi</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">3. Questionario AS IS</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            <tr>
                                <td class=" round-table" style="width: 50%" scope="row">Profilo rischio AS IS</td>
                                <td class="text-center round-table" style="width: 50%" scope="row"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </dl>
        </div>
@endsection
