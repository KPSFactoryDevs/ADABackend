@extends('backend.layouts.app')

@section('content')
    <div class="page">
        <div class="page-main">
            <div class="main-content">
                <div class="container">
                    <div class="page-header d-xl-flex d-block">
                        <div class="page-leftheader">
                            <h2 class="page-title">Cr Sintetica | <span class="font-weight-normal text-muted ml-2">Key Performance Softwares S.r.l.</span></h2>
                            <h4 class="page-title">Inizio del periodo analizzato | <span class="font-weight-normal text-muted ml-2">{{$inizioPeriodo}}</span></h4>
                            <h4 class="page-title">Fine del periodo analizzato | <span class="font-weight-normal text-muted ml-2">{{$finePeriodo}}</span></h4>
                            <h4 class="page-title">Punteggio CR | <span class="font-weight-normal text-muted ml-2">{{$scoreCR*10}} / 10</span></h4>
                                <x-forms.get :action="route('admin.pdf.sintetica')" >
                                    <h4 class="page-title"><button data-toggle="tooltip" title="Scarica il report con la tua analisi sintetica cliccando su questo tasto" class="btn btn-sm btn-primary" type="submit">Stampa Analisi Sintetica </button>
                                        <input type="hidden" name="earlierDate" value="{{$earlierDate}}">
                                </x-forms.get>
                            </h4>
                            @if(!empty($missingMonths))<h5 style="color: red">Attenzione! I seguenti mesi non sono presenti nel periodo considerato: @foreach($missingMonths as $meseMancante) {{$meseMancante}} @endforeach, si prega di inserire la centrale rischi di questi mesi.</h5>@endif
                        </div>
                        <div class="page-header d-xl-flex d-block">
                            <div class="page-rightheader ml-md-auto">
                                <div class="d-flex align-items-end flex-wrap my-auto right-content breadcrumb-right">

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-body">
                        <div class="card">
                            <div class="card-body">
                                <div style="padding-left: 15%">
                                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="pills-crscoring-tab" data-toggle="pill" href="#pills-crscoring" role="tab" aria-controls="pills-crscoring" aria-selected="true">CR Scoring</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="pills-resocontoanomalie-tab" data-toggle="pill" href="#pills-resocontoanomalie" role="tab" aria-controls="pills-test" aria-selected="false">Resoconto Anomalie</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="pills-analisidiversificazione-tab" data-toggle="pill" href="#pills-analisidiversificazione" role="tab" aria-controls="pills-analisidiversificazione" aria-selected="false">Analisi degli affidamenti</a>
                                        </li>
                                        <li onclick="asyncCall()" class="nav-item">
                                            <a class="nav-link" id="pills-analisiindebitamento-tab" data-toggle="pill" href="#pills-analisiindebitamento" role="tab" aria-controls="pills-analisiindebitamento" aria-selected="false">Analisi indebitamento</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="pills-rischigaranzie-tab" data-toggle="pill" href="#pills-rischigaranzie" role="tab" aria-controls="pills-rischigaranzie" aria-selected="false">Rischi e garanzie</a>
                                        </li>
                                    </ul>
                                </div>

                            </div>
                        </div>

                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-crscoring" role="tabpanel" aria-labelledby="pills-crscoring">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <p>Questa sezione fornisce una panoramica delle informazioni relative ai dati caricati che contribuiscono alla
                                                    determinazione dello Scoring attribuito alla CR esaminata.</p>
                                                <table class="table table-hover">
                                                    <tbody>

                                                    <tr>
                                                        <td>Periodo di riferimento</td>
                                                        <td class="text-center">{{$inizioPeriodo}} - {{$finePeriodo}}</td>
                                                    </tr>

                                                    <tr>
                                                        <td>N. Intermediari</td>
                                                        <td class="text-center">{{$intermediari}}</td>
                                                    </tr>

                                                    <tr>
                                                        <td>N° Posizioni Contestate</td>
                                                        <td class="text-center">{{$numeroRapportiContestati}}</td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-body">

                                                <table class="table table-hover">
                                                    <h1 class="text-center">Anomalie utilizzi</h1>

                                                    <tbody>

                                                    <tr>
                                                        <td>Tensione Finanziaria Utilizzi Autoliquidanti</td>
                                                        <?php
                                                        if (isset($numeroSconfiniTotali["Tensioni"]['RISCHI AUTOLIQUIDANTI'])){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    <tr>
                                                        <td>Tensione Finanziaria Utilizzi A Revoca</td>
                                                        <?php

                                                        if (isset($numeroSconfiniTotali["Tensioni"]['RISCHI A REVOCA'])){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    <tr>
                                                        <td>Tensione Finanziaria Utilizzi A Scadenza</td>
                                                        <?php
                                                        if (isset($numeroSconfiniTotali["Tensioni"]['RISCHI A SCADENZA'])){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">

                                                <table class="table table-hover">
                                                    <h1 class="text-center">Anomalie lievi</h1>

                                                    <tbody>

                                                    <tr>
                                                        <td>Impagati</td>
                                                        <?php
                                                        if($impagati){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    <tr>
                                                        <td>Presenza Sconfini</td>
                                                        <?php
                                                        if($numeroSconfiniTotali['PresenzaSconfini']){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    @if($numeroSconfiniTotali['PresenzaSconfini'])

                                                        <tr>
                                                            <td>N° Sconfini Autoliquidanti</td>
                                                            <td class="text-center">
                                                                <?php
                                                                if(isset($numeroSconfiniTotali['Categorie']['RISCHI AUTOLIQUIDANTI'])) {
                                                                    echo $numeroSconfiniTotali['Categorie']['RISCHI AUTOLIQUIDANTI'];

                                                                } else {
                                                                    echo 0;
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>N° Sconfini A Revoca</td>
                                                            <td class="text-center">
                                                                <?php
                                                                if(isset($numeroSconfiniTotali['Categorie']['RISCHI A REVOCA'])) {
                                                                    echo $numeroSconfiniTotali['Categorie']['RISCHI A REVOCA'];

                                                                } else {
                                                                    echo 0;
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>N° Sconfini A Scadenza</td>
                                                            <td class="text-center">
                                                                <?php
                                                                if(isset($numeroSconfiniTotali['Categorie']['RISCHI A SCADENZA'])) {
                                                                    echo $numeroSconfiniTotali['Categorie']['RISCHI A SCADENZA'];
                                                                } else {
                                                                    echo 0;
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">

                                                <table class="table table-hover">
                                                    <h1 class="text-center">Anomalie quasi pregiudizievoli</h1>

                                                    <tbody>

                                                    <tr>
                                                        <td>Sconfinamenti entro 90gg</td>
                                                        <?php
                                                        if (!empty($numeroSconfiniTotali['SconfiniEntro90Giorni'])){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    <tr>
                                                        <td>Sconfinamenti > 90 gg e < 180gg</td>
                                                        <?php
                                                        if (!empty($numeroSconfiniTotali['SconfiniOltre90Giorni'])){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    <tr>
                                                        <td>Sconfinamenti oltre 180gg</td>
                                                        <?php
                                                        if (!empty($numeroSconfiniTotali['SconfiniOltre180Giorni'])){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">

                                                <table class="table table-hover">
                                                    <h1 class="text-center">Anomalie pregiudizievoli</h1>

                                                    <tbody>

                                                    <tr>
                                                        <td>Garanzie attivate con esito negativo</td>
                                                        <?php
                                                        if ($garanzieEsitoNegativo > 0){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    <tr>
                                                        <td>Sofferenze</td>
                                                        <?php
                                                        if(count($sofferenze) > 0){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    <tr>
                                                        <td>Presenza crediti passati a perdita</td>
                                                        <?php
                                                        if(count($creditiPassatiPerdita) > 0){
                                                            echo "<td class='text-center' style='color:white; background-color: red'>Si</td>";
                                                        }
                                                        else{
                                                            echo "<td class='text-center' style='color:white;background-color: green'>No</td>";
                                                        }
                                                        ?>
                                                    </tr>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-header  border-0 responsive-header">
                                                <h4 class="card-title">Punteggio analisi CR per banca</h4>
                                            </div>
                                            <div class="card-body">
                                                <p>La tabella seguente mostra gli score attribuiti al comportamento del soggetto rispetto al singolo intermediario creditizio e fornisce indicazioni circa il
                                                    merito creditizio.
                                                    Il punteggio CR complessivo invece tiene conto del comportamento globale del soggetto nei confronti di tutti gli intermediari con i quali ha
                                                    intrattenuto rapporti,, pertanto potrebbe essere diverso rispetto alla media dei punteggi calcolati suii singoli intermediari.</p>
                                                <table class="table table-hover table-light">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">Banca</th>
                                                            <th scope="col">Punteggio</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($banksScoring as $label=>$score)
                                                        <tr>
                                                            <th>{{ $label }}</th>
                                                            <th>{{ $score*10 }} / 10</th>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-resocontoanomalie" role="tabpanel" aria-labelledby="pills-resocontoanomalie">
                                <div class="card">
                                    <div class="card-body">
                                        <p>In questa sezione vengono segnalate, laddove siano presenti, eventuali anomalie nelle rilevazioni della Centrale
                                            dei Rischi alle quali si consiglia di prestare attenzione al fine di verificare se si tratti di una eventuale errata
                                            segnalazione o un comportamento scorretto del soggetto.</p>
                                    </div>
                                </div>
                                @if(count($importiSconfini) == 0)
                                <div class="card">
                                    <div class="card-body">
                                        <p>Le tabelle seguenti mostrano eventuali anomalie legate alla presenza di sconfinamenti in Centrale Rischi, queste
                                            sono divise in tre tabelle a seconda della durata dello sconfino segnalato.</p>
                                        <h3>Non ci sono sconfini</h3>
                                    </div>
                                </div>
                                @else
                                <div class="card">
                                    <div class="card-body">
                                        <p>Le tabelle seguenti mostrano eventuali anomalie legate alla presenza di sconfinamenti in Centrale Rischi, queste
                                            sono divise in tre tabelle a seconda della durata dello sconfino segnalato.</p>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header  border-0 responsive-header">
                                        <h4 class="card-title">Sconfini Entro 90 giorni</h4>
                                    </div>
                                    <div class="card-body">
                                            <table class="table table-hover table-light">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Data</th>
                                                    <th scope="col">Banca</th>
                                                    <th scope="col">Categoria di Rischio</th>
                                                    <th scope="col">Tipo attività</th>
                                                    <th scope="col">Importo sconfinamento</th>
                                                    <th scope="col">Utilizzo posizione sconfinata</th>
                                                    <th scope="col">Prob. Errata Segnalazione</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @isset($sconfiniDivisi['Sconfini entro 90 giorni'])
                                                @foreach($sconfiniDivisi['Sconfini entro 90 giorni'] as $sconfiniIndex=>$sconfiniData)
                                                <tr>
                                                    <td>{{ $sconfiniData['Data'] }}</td>
                                                    <td>{{ $sconfiniData['Banca'] }}</td>
                                                    <td>{{ $sconfiniData['Categoria'] }}</td>
                                                    <td>Cassa</td>
                                                    <td>{{ number_format($sconfiniData['Importo Sconfinamento'],0,',','.') }} €</td>
                                                    <td>{{ number_format($sconfiniData['Utilizzo Posizione Sconfinata'],0,',','.') }} €</td>
                                                    <td>{{ $sconfiniData['Probabile errata segnalazione'] }}</td>
                                                </tr>
                                                @endforeach
                                                @endisset
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header  border-0 responsive-header">
                                        <h4 class="card-title">Sconfini Entro 180 giorni</h4>
                                    </div>
                                    <div class="card-body">
                                            <table class="table table-hover table-light">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Data</th>
                                                    <th scope="col">Banca</th>
                                                    <th scope="col">Categoria di Rischio</th>
                                                    <th scope="col">Tipo attività</th>
                                                    <th scope="col">Importo sconfinamento</th>
                                                    <th scope="col">Utilizzo posizione sconfinata</th>
                                                    <th scope="col">Prob. Errata Segnalazione</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @isset($sconfiniDivisi['Sconfini oltre 90 giorni'])
                                                @foreach($sconfiniDivisi['Sconfini oltre 90 giorni'] as $sconfiniIndex=>$sconfiniData)
                                                <tr>
                                                    <td>{{ $sconfiniData['Data'] }}</td>
                                                    <td>{{ $sconfiniData['Banca'] }}</td>
                                                    <td>{{ $sconfiniData['Categoria'] }}</td>
                                                    <td>Cassa</td>
                                                    <td>{{ number_format($sconfiniData['Importo Sconfinamento'],0,',','.') }} €</td>
                                                    <td>{{ number_format($sconfiniData['Utilizzo Posizione Sconfinata'],0,',','.') }} €</td>
                                                    <td>{{ $sconfiniData['Probabile errata segnalazione'] }}</td>
                                                </tr>
                                                @endforeach
                                                @endisset
                                                @isset($anomalieStatoRapporto['Sconfini entro 180 giorni'])
                                                @foreach($anomalieStatoRapporto['Sconfini entro 180 giorni'] as $sconfiniIndex=>$sconfiniData)
                                                <tr>
                                                    <td>{{ $sconfiniData['Data'] }}</td>
                                                    <td>{{ $sconfiniData['Banca'] }}</td>
                                                    <td>{{ $sconfiniData['Categoria'] }}</td>
                                                    <td>Cassa</td>
                                                    <td>{{ number_format($sconfiniData['Importo Sconfinamento'],0,',','.') }} €</td>
                                                    <td>{{ number_format($sconfiniData['Utilizzo Posizione Sconfinata'],0,',','.') }} €</td>
                                                    <td>{{ $sconfiniData['Probabile errata segnalazione'] }}</td>
                                                </tr>
                                                @endforeach
                                                @endisset
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header  border-0 responsive-header">
                                        <h4 class="card-title">Sconfini Oltre 180 giorni</h4>
                                    </div>
                                    <div class="card-body">


                                            <table class="table table-hover table-light">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Data</th>
                                                    <th scope="col">Banca</th>
                                                    <th scope="col">Categoria di Rischio</th>
                                                    <th scope="col">Tipo attività</th>
                                                    <th scope="col">Importo sconfinamento</th>
                                                    <th scope="col">Utilizzo posizione sconfinata</th>
                                                    <th scope="col">Prob. Errata Segnalazione</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @isset($sconfiniDivisi['Sconfini oltre 180 giorni'])
                                                @foreach($sconfiniDivisi['Sconfini oltre 180 giorni'] as $sconfiniIndex=>$sconfiniData)
                                                <tr>
                                                    <td>{{ $sconfiniData['Data'] }}</td>
                                                    <td>{{ $sconfiniData['Banca'] }}</td>
                                                    <td>{{ $sconfiniData['Categoria'] }}</td>
                                                    <td>Cassa</td>
                                                    <td>{{ number_format($sconfiniData['Importo Sconfinamento'],0,',','.') }} €</td>
                                                    <td>{{ number_format($sconfiniData['Utilizzo Posizione Sconfinata'],0,',','.') }} €</td>
                                                    <td>{{ $sconfiniData['Probabile errata segnalazione'] }}</td>
                                                </tr>
                                                @endforeach
                                                @endisset
                                                @isset($anomalieStatoRapporto['Sconfini oltre 180 giorni'])
                                                @foreach($anomalieStatoRapporto['Sconfini oltre 180 giorni'] as $sconfiniIndex=>$sconfiniData)
                                                <tr>
                                                    <td>{{ $sconfiniData['Data'] }}</td>
                                                    <td>{{ $sconfiniData['Banca'] }}</td>
                                                    <td>{{ $sconfiniData['Categoria'] }}</td>
                                                    <td>Cassa</td>
                                                    <td>{{ number_format($sconfiniData['Importo Sconfinamento'],0,',','.') }} €</td>
                                                    <td>{{ number_format($sconfiniData['Utilizzo Posizione Sconfinata'],0,',','.') }} €</td>
                                                    <td>{{ $sconfiniData['Probabile errata segnalazione'] }}</td>
                                                </tr>
                                                @endforeach
                                                @endisset
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                                @if(empty($informazioniGaranti['Anomalie']))
                                <div class="card">
                                    <div class="card-body">
                                        <p>Le anomalie riscontrabili sono attribuibili per lo più alle cause sotto elencate:</p>
                                        <ul>
                                            <li>Garanzie sovradimensionate quando il rapporto fra Valore della Garanzia e Importo Garantito supera il 200%</li>
                                            <li>Garanzie Inutilizzate quando sono presenti garanzie verso un istituto di credito ma l’utilizzato sul rapporto è pari a zero</li>
                                            <li>Fido Inutilizzato (nel solo caso dei rischi autoliquidanti) quando l’utilizzato è pari a zero in un determinato mese</li>
                                            <li>Utilizzato pari ad accordato nel solo caso dei rischi autoliquidanti quando l’utilizzato è pari all’accordato, in quanto la percentuale di utilizzo
                                                ideale si aggira fra il 50 e il 70%</li>
                                        </ul>
                                        <h3>Non ci sono anomalie</h3>
                                    </div>
                                </div>
                                @else
                                <div class="card">
                                    <div class="card-header  border-0 responsive-header">
                                        <h4 class="card-title">Anomalie</h4>
                                    </div>
                                    <div class="card-body">
                                        <p>Le anomalie riscontrabili sono attribuibili per lo più alle cause sotto elencate:</p>
                                        <ul>
                                            <li>Garanzie sovradimensionate quando il rapporto fra Valore della Garanzia e Importo Garantito supera il 200%;</li>
                                            <li>Garanzie Inutilizzate quando sono presenti garanzie verso un istituto di credito ma l’utilizzato sul rapporto è pari a zero;</li>
                                            <li>Fido Inutilizzato (nel solo caso dei rischi autoliquidanti) quando l’utilizzato è pari a zero in un determinato mese;</li>
                                            <li>Utilizzato pari ad accordato nel solo caso dei rischi autoliquidanti quando l’utilizzato è pari all’accordato, in quanto la percentuale di utilizzo
                                                ideale si aggira fra il 50 e il 70%.</li>
                                        </ul>
                                        <table class="table table-hover table-light">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Data</th>
                                                    <th scope="col">Banca</th>
                                                    <th scope="col">Categoria di Rischio</th>
                                                    <th scope="col">Tipo Anomalia</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($informazioniGaranti['Anomalie'] as $nomeBanca=>$multipleDates)
                                                @foreach($multipleDates as $singleDate=>$multipleTypes)
                                                    @foreach($multipleTypes as $singleType=>$multipleAnomalie)
                                                    @php
                                                    $multipleAnomalie = array_unique($multipleAnomalie);
                                                    @endphp
                                                        @foreach($multipleAnomalie as $singleAnomalia=>$tmp)
                                                            <tr>
                                                                <td>{{$singleDate}}</td>
                                                                <td>{{$nomeBanca}}</td>
                                                                <td>{{$singleType}}</td>
                                                                <td>{{$tmp}}</td>
                                                            </tr>
                                                        @endforeach
                                                    @endforeach
                                                @endforeach
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif





                                @if(empty($sofferenzeTotali))
                                    <div class="card">
                                        <div class="card-body">

                                            <h3>Non ci sono Sofferenze</h3>
                                        </div>
                                    </div>
                                @else
                                    <div class="card">
                                        <div class="card-header  border-0 responsive-header">
                                            <h4 class="card-title">Sofferenze</h4>
                                        </div>
                                        <div class="card-body">

                                            <table class="table table-hover table-light">
                                                <thead>
                                                <tr>
                                                    <th scope="col">Data</th>
                                                    <th scope="col">Banca</th>
                                                    <th scope="col">Categoria di Rischio</th>
                                                    <th scope="col">Sezione</th>
                                                    <th scope="col">Accordato Operativo</th>
                                                    <th scope="col">Utilizzato</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($sofferenzeTotali as $singleKey=>$singlesofferenza)

                                                    <tr>
                                                        <td>{{$singlesofferenza->date}}</td>
                                                        <td>{{$singlesofferenza->nome_banca}}</td>
                                                        <td>{{$singlesofferenza->categoria}}</td>
                                                        <td>{{$singlesofferenza->sezione}}</td>
                                                        <td>{{ number_format((float)$singlesofferenza->accordato_operativo,0,',','.') }} €</td>
                                                        <td>{{ number_format((float)$singlesofferenza->utilizzato,0,',','.') }} €</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                            </div>
                            <div class="tab-pane fade" id="pills-analisidiversificazione" role="tabpanel" aria-labelledby="pills-analisidiversificazione">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="text-center">Le rilevazioni si riferiscono all'ultimo mese del periodo preso in considerazione</h4>
                                        <p>In questa sezione viene mostrata la composizione degli affidamenti presso gli intermediari con i quali il soggetto
                                            intrattiene rapporti in modo da evidenziare se l’esposizione del soggetto è troppo concentrata su alcuni di questi.
                                            I diagrammi a torta illustrano la diversificazione degli affidamenti e degli utilizzi, mentre le tabelle sottostanti
                                            mostrano la scomposizione delle linee di credito per tipologia di rischio e il peso di ciascun intermediario sul totale
                                            degli affidamenti e sul totale utilizzato.</p>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="card">
                                                <div class="card-header  border-0 responsive-header">
                                                    <h4 class="card-title">Utilizzato</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div style="width: 900px; height: 500px;" id="flotPie"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="card">
                                                <div class="card-header  border-0 responsive-header">
                                                    <h4 class="card-title">Accordato</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div style="width: 900px; height: 500px;" id="flotPie1"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="height: 50px !important"></div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                        <h2 class="ml-5"><?php echo $latestYear.' '.$latestMonth; ?></h2>
                                        <table class="table table-hover table-light">
                                            <thead>
                                            <tr>
                                                <th scope="col">Banca</th>
                                                <th scope="col">Categoria</th>
                                                <th scope="col">Totale Accordato Operativo</th>
                                                <th scope="col">Totale Utilizzato</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($totaleAffidamentiTable as $item)
                                                    <tr>
                                                        <th>{{$item['nome_banca']}}</th>
                                                        <th style="{{$item['style']}}" >{{$item['categoria']}}</th>
                                                        <td>{{number_format($item['totAccordatoOperativo'],0,',','.')}}€</td>
                                                        <td>{{number_format($item['totUtilizzato'],0,',','.')}}€</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <hr>
                                    </div>
                                    </div>
                                </div>





                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <table class="table table-hover table-light">
                                                <thead>
                                                <tr>
                                                    <th scope="col">Banca</th>
                                                    <th scope="col">Accordato Totale</th>
                                                    <th scope="col">Peso sul totale accordato</th>
                                                    <th scope="col">Utilizzato totale</th>
                                                    <th scope="col">Peso sul totale utilizzato</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($totAffidamentiConPesiPerBanca as $singleBank => $singleData)
                                                <tr>
                                                    <th scope="row">{{$singleData['nome_banca']}}</th>
                                                    <td>{{number_format($singleData['totAccordatoOperativo'],0,',','.')}}€</td>
                                                    @if(isset($singleData['PesoAccordatoOperativo']))
                                                    <td>{{number_format($singleData['PesoAccordatoOperativo'],2,',','.')}}%</td>
                                                    @else
                                                    <td>0%</td>
                                                    @endif
                                                    <td>{{number_format($singleData['totUtilizzato'],0,',','.')}}€</td>
                                                    @if(isset($singleData['PesoUtilizzato']))
                                                    <td>{{number_format($singleData['PesoUtilizzato'],2,',','.')}}%</td>
                                                    @else
                                                    <td>0%</td>
                                                    @endif
                                                </tr>
                                                @endforeach
                                                <tr style="font-size: 1.5rem">
                                                    <th style="font-size: 1.5rem" >Totale</td>
                                                    <td >{{number_format($totaleAffidamentiGeneral[0]['totAccordatoOperativo'],0,',','.')}}€</td>
                                                    <td></td>
                                                    <td >{{number_format($totaleAffidamentiGeneral[0]['totUtilizzato'],0,',','.')}}€</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-analisiindebitamento" role="tabpanel" aria-labelledby="pills-analisiindebitamento">
                            <div class="card">
                                <div class="card-body">
                                    <p>In questa sezione vengono mostrati i valori medi per il periodo preso in considerazione dei valori di Accordato,
                                        Utilizzato e Sconfinamenti e i corrispondenti grafici che illustrano l’andamento degli affidamenti e degli utilizzi nel
                                        tempo per tipologia di linea di credito.</p>
                                    <table class="table table-hover table-light table-bordered">
                                        <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th scope="col" colspan="3" class="text-center" style="background-color: forestgreen; color:white">Scadenza</th>
                                            <th scope="col" colspan="3" class="text-center" style="background-color: yellow">Revoca</th>
                                            <th scope="col" colspan="3" class="text-center" style="background-color: orangered; color:white">Autoliquidante</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <th colspan="1"></th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Valore Accordato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Utilizzato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Sconfinamenti</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Valore Accordato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Utilizzato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Sconfinamenti</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Valore Accordato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Utilizzato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Sconfinamenti</th>
                                        </tr>

                                        <tr>
                                            <td colspan="1">Media</td>
                                            @if(isset($mediaAnalisiIndebitamento['RISCHI A SCADENZA']))
                                                <td colspan="1">{{number_format($mediaAnalisiIndebitamento['RISCHI A SCADENZA']['Accordato Operativo'],0,',','.')}} €</td>
                                                <td colspan="1">{{number_format($mediaAnalisiIndebitamento['RISCHI A SCADENZA']['Utilizzato'],0,',','.')}} €</td>
                                                <td colspan="1">{{number_format($mediaAnalisiIndebitamento['RISCHI A SCADENZA']['Sconfinamenti'],0,',','.')}} €</td>
                                            @else
                                                <td colspan="1">0 €</td>
                                                <td colspan="1">0 €</td>
                                                <td colspan="1">0 €</td>
                                            @endif
                                            @if(isset($mediaAnalisiIndebitamento['RISCHI A REVOCA']))
                                                <td colspan="1">{{number_format($mediaAnalisiIndebitamento['RISCHI A REVOCA']['Accordato Operativo'],0,',','.')}} €</td>
                                                <td colspan="1">{{number_format($mediaAnalisiIndebitamento['RISCHI A REVOCA']['Utilizzato'],0,',','.')}} €</td>
                                                <td colspan="1">{{number_format($mediaAnalisiIndebitamento['RISCHI A REVOCA']['Sconfinamenti'],0,',','.')}} €</td>
                                            @else
                                                <td colspan="1">0 €</td>
                                                <td colspan="1">0 €</td>
                                                <td colspan="1">0 €</td>
                                            @endif
                                            @if(isset($mediaAnalisiIndebitamento['RISCHI AUTOLIQUIDANTI']))

                                                <td colspan="1">{{number_format($mediaAnalisiIndebitamento['RISCHI AUTOLIQUIDANTI']['Accordato Operativo'],0,',','.')}} €</td>
                                                <td colspan="1">{{number_format($mediaAnalisiIndebitamento['RISCHI AUTOLIQUIDANTI']['Utilizzato'],0,',','.')}} €</td>
                                                <td colspan="1">@if(isset($mediaAnalisiIndebitamento['RISCHI AUTOLIQUIDANTI']['Sconfinamenti'])){{number_format($mediaAnalisiIndebitamento['RISCHI AUTOLIQUIDANTI']['Sconfinamenti'],0,',','.')}} @else 0 @endif €</td>
                                            @else
                                                <td colspan="1">0 €</td>
                                                <td colspan="1">0 €</td>
                                                <td colspan="1">0 €</td>

                                            @endif
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div  class="col-lg-6">
                                            <h2 class="text-center">Indebitamenti</h2>
                                            <div style="height: 600px;" id="flotArea4"></div>
                                        </div>
                                        <div  class="col-lg-6">
                                            <h2 class="text-center" >Rischi Autoliquidanti</h2>
                                            <div style="height: 600px;" id="flotArea3"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div  class="col-lg-6">
                                            <h2 class="text-center">Rischi a Scadenza</h2>
                                            <div style="height: 600px;" id="flotArea2"></div>
                                        </div>
                                        <div  class="col-lg-6">
                                            <h2 class="text-center">Rischi a Revoca</h2>
                                            <div style="height: 600px;" id="flotArea1"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            </div>

                            <div class="tab-pane fade" id="pills-rischigaranzie" role="tabpanel" aria-labelledby="pills-rischigaranzie">
                                <div class="card">
                                    <div class="card-body">
                                        <p>Questa sezione è dedicata alle posizioni di rischio e alle garanzie rilasciate per proprio conto o per conto di terzi. I
                                    valori mostrati sono i valori medi per il periodo preso in considerazione.</p>
                                    </div>
                                </div>
                                <table class="table table-hover table-bordered table-light">
                                    <thead>
                                    <tr>
                                        <th class="text-center" colspan="9" scope="col" style="background-color: orange">Posizioni di rischi</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th class="text-center" colspan="3" style="background-color: gray; color: whitesmoke">Posizioni di rischio gestibili</th>
                                        <th class="text-center" colspan="2" style="background-color: gray; color: whitesmoke">Posizioni quasi pregiudizievoli</th>
                                        <th class="text-center" colspan="3" style="background-color: gray; color: whitesmoke">Posizioni pregiudizievoli</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Crediti Scaduti</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Crediti Scaduti Impagati</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Incidenza % Impagati</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Scaduti/Sconfinati > 90gg < 180gg</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Scaduti/Sconfinati > 180gg</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Sofferenze</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Crediti passati a perdita</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Crediti Contestati</th>
                                    </tr>
                                    <tr>
                                        <td>{{number_format($rischiGaranzie['CreditiScaduti'],0,',','.')}}€</td>
                                        <td>{{number_format($rischiGaranzie['CreditiScadutiImpagati'],0,',','.')}}€</td>
                                        <td>{{$incidenzaImpagati}}%</td>
                                        <td>{{number_format($rischiGaranzie['Oltre90'],0,',','.')}}€</td>
                                        <td>{{number_format($rischiGaranzie['Oltre180'],0,',','.')}}€</td>
                                        <td>{{number_format($rischiGaranzie['Sofferenze'],0,',','.')}}€</td>
                                        <td>{{number_format($rischiGaranzie['CreditiPassatiPerdita'],0,',','.')}}€</td>
                                        <td>{{number_format($rischiGaranzie['CreditiContestati'],0,',','.')}}€</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <table class="table table-hover table-bordered table-light">
                                    <tbody>
                                        <tr>
                                            <th>Tot. Valore Garanzie - Info sui garanti</th>
                                            <th>{{number_format($informazioniGaranti['Tot. Valore Garanzia'],0,',','.')}}€</th>
                                        </tr>
                                        <tr>
                                            <th>Tot. Importo Garantito - Info sui garanti</th>
                                            <th>{{number_format($informazioniGaranti['Tot. importo garantito'],0,',','.')}}€</th>
                                        </tr>
                                    </tbody>
                                </table>
                                <table class="table table-hover table-bordered table-light">
                                    <tbody>
                                        <tr>
                                            <th>Tot. Valore Garanzie - Garanzie Ricevute</th>
                                            <th>@if(isset($garanzieRicevute['Garanzia'])) {{number_format($garanzieRicevute['Garanzia'],0,',','.')}}€ @endif</th>
                                        </tr>
                                        <tr>
                                            <th>Tot. Importo Garantito - Garanzie Ricevute</th>
                                            <th>@if(isset($garanzieRicevute['Garantito'])) {{number_format($garanzieRicevute['Garantito'],0,',','.')}}€ @endif</th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endsection



@section('footerscripts')

<script>

function resolveAfter2Seconds() {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve('resolved');
    }, 1000);
  });
}

function renderCharts(){
    console.log('ciao')

    var affidamentiRevoca = <?php echo(json_encode($affidamentiPerMese["Affidamenti"]["RISCHI A REVOCA"]))?>;
    var affidamentiScadenza = <?php echo(json_encode($affidamentiPerMese["Affidamenti"]["RISCHI A SCADENZA"]))?>;
    var affidamentiAutoliq = <?php echo(json_encode($affidamentiPerMese["Affidamenti"]["RISCHI AUTOLIQUIDANTI"]))?>;
    var indebitamento =  <?php echo(json_encode($affidamentiPerMese["Indebitamento"]))?>;

    if(affidamentiRevoca.length > 1){
        google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart3);

      function drawChart3() {
        var data3 = google.visualization.arrayToDataTable(affidamentiRevoca);

        var options3 = {
          title: '',
          'width' : 600,
          'height' : 500,
          curveType: 'function',
          legend: { position: 'right' }
        };

        var chart3 = new google.visualization.LineChart(document.getElementById('flotArea1'));

        chart3.draw(data3, options3);
      }
    }

    if(affidamentiScadenza.length > 1){
        google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart4);

      function drawChart4() {
        var data4 = google.visualization.arrayToDataTable(affidamentiScadenza);

        var options4 = {
            title: '',
          'width' : 600,
          'height' : 500,
          curveType: 'function',
          legend: { position: 'right' }
        };

        var chart4 = new google.visualization.LineChart(document.getElementById('flotArea2'));

        chart4.draw(data4, options4);
      }
    }

    if(affidamentiAutoliq.length > 1){
        google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart5);

      function drawChart5() {
        var data5 = google.visualization.arrayToDataTable(affidamentiAutoliq);

        var options5 = {
            title: '',
          'width' : 600,
          'height' : 500,
          curveType: 'function',
          legend: { position: 'right' }
        };

        var chart5 = new google.visualization.LineChart(document.getElementById('flotArea3'));

        chart5.draw(data5, options5);
      }
    }



      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart6);

      function drawChart6() {
        var data6 = google.visualization.arrayToDataTable(indebitamento);

        var options6 = {
            title: '',
          'width' : 600,
          'height' : 500,
          curveType: 'function',
          legend: { position: 'right' }
        };

        var chart6 = new google.visualization.LineChart(document.getElementById('flotArea4'));

        chart6.draw(data6, options6);
      }
}

$( document ).ready(function() {

    var accordatoPie = <?php echo(json_encode($accordatoPie)) ?>;
    var utilizzatoPie = <?php echo(json_encode($utilizzatoPie)) ?>;

    google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart1);

      function drawChart1() {

        var data1 = google.visualization.arrayToDataTable(accordatoPie);

        var options1 = {
          title: '',
          is3D: true,
          'width' : 900,
          'height' : 600,
        };

        var chart1 = new google.visualization.PieChart(document.getElementById('flotPie1'));

        chart1.draw(data1, options1);
      }

      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart2);

      function drawChart2() {

        var data2 = google.visualization.arrayToDataTable(utilizzatoPie);

        var options2 = {
          title: '',
          is3D: true,
          'width' : 900,
          'height' : 600,
        };

        var chart2 = new google.visualization.PieChart(document.getElementById('flotPie'));

        chart2.draw(data2, options2);
      }
      renderCharts();
});

async function asyncCall() {
  const result = await resolveAfter2Seconds();
  renderCharts();
}

// asyncCall();

</script>

@endsection
