<style>
* {
  box-sizing: border-box;
}
table {
    width:100%;
}
.row {
  margin-left:-5px;
  margin-right:-5px;
}

.column {
  float: left;
  width: 50%;
  padding: 5px;
}

/* Clearfix (clear floats) */
.row::after {
  content: "";
  clear: both;
  display: table;
}

table {
  border-collapse: collapse;
  border-spacing: 0;
  width: 100%;
  padding: 0;
  border: 1px solid #ddd;
}

th, td {
  text-align: left;
  padding: 5px;
}

tr:nth-child(even) {
  background-color: #f2f2f2;
}

</style>

     <div class="page">
        <div class="page-main">
            <div class="main-content">
                <div class="container">
                    <div class="page-header d-xl-flex d-block">
                        <div class="page-leftheader">
                            <h2 class="page-title">Centrale Rischi Andamentale | <span class="font-weight-normal text-muted ml-2">Key Performance Softwares S.r.l.</span></h2>
                            <hr>
                            <table>
                                <tr>
                            <th><h3 class="page-title">Inizio del periodo analizzato | <span class="font-weight-normal text-muted ml-2">{{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Inizio']}}</span></h3></th>
                            <th>&nbsp;&nbsp;&nbsp;</th>
                            <th><h3 class="page-title">Fine del periodo analizzato | <span class="font-weight-normal text-muted ml-2">{{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Fine']}}</span></h3></th>
                                <tr>
                            </table>
                            <table>
                             <th><h3 align="left" class="page-title">Data Analisi | {{date('Y-m-d')}}</h3></th>
                             <th>&nbsp;&nbsp;&nbsp;</th>
                             <th><div class="card-title"><h3>Punteggio CR</h3> <span class="font-weight-normal text-muted ml-2">{{number_format((float)str_replace(',', '.', $response['Scoring']['Panoramica']['FinalScore'])*10/10, 2, ',', '.')}} / 10</span><div></th>
                             <th>&nbsp;&nbsp;&nbsp;</th>
                             </table>
                        </div>
                        <div class="page-header d-xl-flex d-block">
                            <div class="page-rightheader ml-md-auto">
                                <div class="d-flex align-items-end flex-wrap my-auto right-content breadcrumb-right">
                                &nbsp;&nbsp;&nbsp;
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-body">

                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-crscoring" role="tabpanel" aria-labelledby="pills-crscoring">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">

                                                <table class="table table-hover">
                                                    <tbody>

                                                    <tr>
                                                        <td><h4>Periodo di riferimento: </h4></td>
                                                        <td class="text-center">{{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Inizio']}} - {{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Fine']}}</td>
                                                    </tr>

                                                    <tr>
                                                        <td><h4>N. Intermediari: </h4></td>
                                                        <td class="text-center">{{$response['Scoring']['Panoramica']['NumeroIntermediari']}}</td>
                                                    </tr>

                                                    <tr>
                                                        <td><h4>N° Posizioni Contestate: </h4></td>
                                                        <td class="text-center">{{$response['Scoring']['Panoramica']['NumeroPosizioniContestate']}}</td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-body">
                                                <h2 align="center">Anomalie utilizzi</h2>
                                                <table class="table table-hover">
                                                    <tbody>

                                                    <tr>
                                                        <td><h4>Tensione Finanziaria Utilizzi Autoliquidanti</h4></td>
                                                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneAutoliquidanti'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <td><h4>Tensione Finanziaria Utilizzi A Revoca</h4></td>
                                                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneRevoca'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white; background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <td><h4>Tensione Finanziaria Utilizzi A Scadenza</h4></td>
                                                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneScadenza'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white; background-color: green'>No</td>
                                                        @endif
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">
                                                <h2 align="center">Anomalie lievi</h2>

                                                <table class="table table-hover">

                                                    <tbody>

                                                    <tr>
                                                        <td><h4>Impagati</h4></td>
                                                        @if($response['Scoring']['AnomalieLievi']['Impagati'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <td><h4>Presenza Sconfini</h4></td>
                                                        @if($response['Scoring']['AnomalieLievi']['Sconfini'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    @if($response['Scoring']['AnomalieLievi']['Sconfini'])

                                                        <tr>
                                                            <td><h4>N° Sconfini Autoliquidanti</h4></td>
                                                            <td class="text-center">
                                                                    {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI AUTOLIQUIDANTI'] }}

                                                            <!--    @if(isset($response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI AUTOLIQUIDANTI']))
                                                                        {{$response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI AUTOLIQUIDANTI']}}
                                                                    @else
                                                                        0
                                                                    @endif -->
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td><h4>N° Sconfini A Revoca</h4></td>
                                                            <td class="text-center">
                                                            {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A REVOCA'] }}

                                                        <!--    @if(isset($response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A REVOCA']))
                                                                    {{$response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A REVOCA']}}
                                                                @else
                                                                    0
                                                                @endif -->
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td><h4>N° Sconfini A Scadenza</h4></td>
                                                            <td class="text-center">
                                                            {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A SCADENZA'] }}

                                                        <!--    @if(isset($response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A SCADENZA']))
                                                                    {{$response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A SCADENZA']}}
                                                                @else
                                                                    0
                                                                @endif -->
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">
                                                <h2 align="center">Anomalie quasi pregiudizievoli</h2>

                                                <table class="table table-hover">

                                                    <tbody>

                                                    <tr>
                                                        <td><h4>Sconfinamenti entro 90gg</h4></td>
                                                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroNovantaGiorni'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <td><h4>Sconfinamenti oltre 90 gg ed entro 180</h4></td>
                                                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroCentoOttantaGiorni'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <td><h4>Sconfinamenti oltre 180gg</h4></td>
                                                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniOltreCentoOttantaGiorni'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">
                                                <h2 align="center">Anomalie pregiudizievoli</h2>

                                                <table class="table table-hover">

                                                    <tbody>

                                                    <tr>
                                                        <td><h4>Garanzie attivate con esito negativo</h4></td>
                                                        @if($response['Scoring']['AnomaliePregiudizievoli']['GaranzieAttivateEsitoNegativo'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <td><h4>Sofferenze</h4></td>
                                                        @if($response['Scoring']['AnomaliePregiudizievoli']['Sofferenze'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <td><h4>Presenza crediti passati a perdita</h4></td>
                                                        @if($response['Scoring']['AnomaliePregiudizievoli']['CreditiPassatiPerdita'])
                                                            <td class='text-center' style='color:white; background-color: red'>Si</td>
                                                        @else
                                                            <td class='text-center' style='color:white;background-color: green'>No</td>
                                                        @endif
                                                    </tr>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                   {{--    <!--  <div class="card">
                                            <div class="card-header  border-0 responsive-header">
                                                <h4 class="card-title" align="center">Punteggio analisi CR per banca</h4>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-hover table-light">
                                                    <thead>
                                                        <tr>
                                                            <th>Banca</th>
                                                            <th>Punteggio</th>
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
                                        </div> -->   --}}
                                    </div>
                                </div>
                            </div>
                            <!-- Inizio Sconfini entro 90 giorni -->
                            <div class="tab-pane fade" id="pills-resocontoanomalie" role="tabpanel" aria-labelledby="pills-resocontoanomalie">
                                <h2 align="center">Resoconto Anomalie</h2>
                                @if(count($response['ResocontoAnomalie']['ListaSconfiniEntroNovantaGiorni']) == 0)
                                <div class="card">
                                    <div class="card-body">
                                        <h3 align="center">Non ci sono sconfini entro 90 giorni</h3>
                                    </div>
                                </div>
                                <hr>
                                @else
                                <div class="card">
                                    <div class="card-header  border-0 responsive-header">
                                        <h3 class="card-title" align="center">Sconfini Entro 90 Giorni</h3>
                                    </div>
                                    <div class="card-body">
                                        <table style="border-collapse: collapse; border: 1px solid black" class="table table-hover table-light">
                                            <thead>
                                                <tr>
                                                    <th style="border: 1px solid black" scope="col">Data</th>
                                                    <th style="border: 1px solid black" scope="col">Banca</th>
                                                    <th style="border: 1px solid black" scope="col">Categoria di Rischio</th>
                                                    <th style="border: 1px solid black" scope="col">Tipo attività</th>
                                                    <th style="border: 1px solid black" scope="col">Importo sconfinamento</th>
                                                    <th style="border: 1px solid black" scope="col">Utilizzo posizione sconfinata</th>
                                                    <th style="border: 1px solid black" scope="col">Prob. Errata Segnalazione</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($response['ResocontoAnomalie']['ListaSconfiniEntroNovantaGiorni'] as $period=>$sconfini)
                                                <tr>
                                                    <td style="border: 1px solid black">{{ $sconfini['data'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['banca'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['categoria'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['tipo_attivita'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['importo_sconfinamento'] }} €</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['utilizzo_posizione_sconfinata'] }} €</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['probabile_errata_segnalazione'] }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                                @if(count($response['ResocontoAnomalie']['ListaSconfiniEntroCentoOttantaGiorni']) == 0)
                                <div class="card">
                                    <div class="card-body">
                                        <h3 align="center">Non ci sono sconfini entro 180 giorni</h3>
                                    </div>
                                </div>
                                <hr>
                                @else
                                <div class="card">
                                    <div class="card-header  border-0 responsive-header">
                                        <h3 class="card-title" align="center">Sconfini Entro 180 Giorni</h3>
                                    </div>
                                    <div class="card-body">
                                        <table style="border-collapse: collapse; border: 1px solid black" class="table table-hover table-light">
                                            <thead>
                                                <tr>
                                                    <th style="border: 1px solid black" scope="col">Data</th>
                                                    <th style="border: 1px solid black" scope="col">Banca</th>
                                                    <th style="border: 1px solid black" scope="col">Categoria di Rischio</th>
                                                    <th style="border: 1px solid black" scope="col">Tipo attività</th>
                                                    <th style="border: 1px solid black" scope="col">Importo sconfinamento</th>
                                                    <th style="border: 1px solid black" scope="col">Utilizzo posizione sconfinata</th>
                                                    <th style="border: 1px solid black" scope="col">Prob. Errata Segnalazione</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($response['ResocontoAnomalie']['ListaSconfiniEntroCentoOttantaGiorni'] as $period=>$sconfini)
                                                <tr>
                                                    <td style="border: 1px solid black">{{ $sconfini['data'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['banca'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['categoria'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['tipo_attivita'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['importo_sconfinamento'] }} €</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['utilizzo_posizione_sconfinata'] }} €</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['probabile_errata_segnalazione'] }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                                @if(count($response['ResocontoAnomalie']['ListaSconfiniOltreCentoOttantaGiorni']) == 0)
                                <div class="card">
                                    <div class="card-body">
                                        <h3 align="center">Non ci sono sconfini oltre 180 giorni</h3>
                                    </div>
                                </div>
                                <hr>
                                @else
                                <div class="card">
                                    <div class="card-header  border-0 responsive-header">
                                        <h3 class="card-title" align="center">Sconfini Oltre 180 Giorni</h3>
                                    </div>
                                    <div class="card-body">
                                        <table style="border-collapse: collapse; border: 1px solid black" class="table table-hover table-light">
                                            <thead>
                                                <tr>
                                                    <th style="border: 1px solid black" scope="col">Data</th>
                                                    <th style="border: 1px solid black" scope="col">Banca</th>
                                                    <th style="border: 1px solid black" scope="col">Categoria di Rischio</th>
                                                    <th style="border: 1px solid black" scope="col">Tipo attività</th>
                                                    <th style="border: 1px solid black" scope="col">Importo sconfinamento</th>
                                                    <th style="border: 1px solid black" scope="col">Utilizzo posizione sconfinata</th>
                                                    <th style="border: 1px solid black" scope="col">Prob. Errata Segnalazione</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($response['ResocontoAnomalie']['ListaSconfiniOltreCentoOttantaGiorni'] as $period=>$sconfini)
                                                <tr>
                                                    <td style="border: 1px solid black">{{ $sconfini['data'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['banca'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['categoria'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['tipo_attivita'] }}</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['importo_sconfinamento'] }} €</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['utilizzo_posizione_sconfinata'] }} €</td>
                                                    <td style="border: 1px solid black">{{ $sconfini['probabile_errata_segnalazione'] }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif

                                @if(empty($response['ResocontoAnomalie']['ListaAnomalie']))
                                <div class="card">
                                    <div class="card-body">
                                        <h3 align="center">Non ci sono anomalie</h3>
                                    </div>
                                </div>
                                <hr>
                                @else
                                <div class="card">
                                    <div class="card-header  border-0 responsive-header">
                                        <h3 class="card-title" align="center">Anomalie</h3>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-hover table-light">
                                            <thead>
                                                <tr>
                                                    <th style="border: 1px solid black" scope="col">Data</th>
                                                    <th style="border: 1px solid black" scope="col">Banca</th>
                                                    <th style="border: 1px solid black" scope="col">Categoria di Rischio</th>
                                                    <th style="border: 1px solid black" scope="col">Tipo Anomalia</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($response['ResocontoAnomalie']['ListaAnomalie'] as $nomeBanca=>$multipleDates)
                                                <tr>
                                                    <td style="border: 1px solid black">{{$multipleDates['date']}}</td>
                                                    <td style="border: 1px solid black">{{$multipleDates['nome_banca']}}</td>
                                                    <td style="border: 1px solid black">{{$multipleDates['categoria']}}</td>
                                                    <td style="border: 1px solid black">{{$multipleDates['anomalia']}}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif

                            </div>
                          {{--  <div class="tab-pane fade" id="pills-analisidiversificazione" role="tabpanel" aria-labelledby="pills-analisidiversificazione">
                                <h2 align="center">Analisi diversificazione</h2>
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="text-center" align="center">Le rilevazioni si riferiscono all'ultimo mese del periodo preso in considerazione</h4>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="card">
                                                <div class="card-header  border-0 responsive-header">
                                                    <h4 class="card-title" align="center">Affidamenti</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="chart-wrapper">
                                                        <div class="h-400 w-500" id="flotPie1"></div>
                                                    </div>
                                                </div>
                                                <div class="card-bottom">
                                                    <div id="pieContainer1">

                                                    </div>
                                                </div>
                                            </div>
                                        </div><!-- col-6 -->
                                        <div class="col-lg-6">
                                            <div class="card">
                                                <div class="card-header border-0 responsive-header">
                                                    <h4 class="card-title">Utilizzato</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="chart-wrapper">
                                                        <div class="h-400 w-500" id="flotPie2"></div>
                                                    </div>
                                                </div>
                                                <div class="card-bottom">
                                                    <div id="pieContainer2">

                                                    </div>
                                                </div>
                                            </div>
                                        </div><!-- col-6 -->
                                    </div>
                                </div>



                           <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <h2 align="center"><?php echo $latestYear.' '.$latestMonth; ?></h2>
                                            <table class="table table-hover table-light">
                                                <thead>
                                                <tr>
                                                    <th style="border: 1px solid black" scope="col">Banca</th>
                                                    <th style="border: 1px solid black" scope="col">Categoria</th>
                                                    <th style="border: 1px solid black" scope="col">Totale Accordato Operativo</th>
                                                    <th style="border: 1px solid black" scope="col">Totale Utilizzato</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($totaleAffidamentiTable as $item)
                                                        <tr>
                                                            <th style="border: 1px solid black">{{$item['nome_banca']}}</th>
                                                            <th style="{{$item['style']}};border: 1px solid black" >{{$item['categoria']}}</th>
                                                            <td style="border: 1px solid black">{{number_format($item['totAccordatoOperativo'],0,',','.')}}€</td>
                                                            <td style="border: 1px solid black">{{number_format($item['totUtilizzato'],0,',','.')}}€</td>
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
                                                    <th style="border: 1px solid black" scope="col">Banca</th>
                                                    <th style="border: 1px solid black" scope="col">Accordato Totale</th>
                                                    <th style="border: 1px solid black" scope="col">Peso sul totale accordato</th>
                                                    <th style="border: 1px solid black" scope="col">Utilizzato totale</th>
                                                    <th style="border: 1px solid black" scope="col">Peso sul totale utilizzato</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($response['AnalisiAffidamenti']['ListaAffidamenti'] as $singleBank => $singleData)
                                                <tr>
                                                    <th style="background-color: darkblue; color:white; border: 1px solid black" scope="row">{{$singleData['nome_banca']}}</th>
                                                    <td style="border: 1px solid black">{{$singleData['totAccordatoOperativo']}}€</td>
                                                    @if(isset($singleData['PesoAccordatoOperativo']))
                                                    <td style="border: 1px solid black">{{$singleData['PesoAccordatoOperativo']}}%</td>
                                                    @else
                                                    <td style="border: 1px solid black">0%</td>
                                                    @endif
                                                    <td style="border: 1px solid black">{{$singleData['totUtilizzato']}}€</td>
                                                    @if(isset($singleData['PesoUtilizzato']))
                                                    <td style="border: 1px solid black">{{$singleData['PesoUtilizzato']}}%</td>
                                                    @else
                                                    <td style="border: 1px solid black">0%</td>
                                                    @endif
                                                </tr>
                                                @endforeach
                                              <tr style="font-size: 1.5rem">
                                                    <th style="font-size: 1.5rem;border: 1px solid black" >Totale</td>
                                                    <td style="border: 1px solid black" >{{number_format($totaleAffidamentiGeneral[0]['totAccordatoOperativo'],0,',','.')}}€</td>
                                                    <td></td>
                                                    <td style="border: 1px solid black" >{{number_format($totaleAffidamentiGeneral[0]['totUtilizzato'],0,',','.')}}€</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-analisiindebitamento" role="tabpanel" aria-labelledby="pills-analisiindebitamento">
                                <h2 align="center">Analisi indebitamento (le cifre riportate in tabella rappresentano i valori medi) </h2>
                                <div class="card">
                                <div class="card-body">
                                    <table class="table table-hover table-light table-bordered">
                                        <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th  scope="col" colspan="3" class="text-center" style="background-color: yellow">Revoca</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <th colspan="1"></th>
                                            <th  colspan="1" style="background-color: darkblue; color:white; border: 1px solid black">Valore Accordato</th>
                                            <th  colspan="1" style="background-color: darkblue; color:white; border: 1px solid black">Utilizzato</th>
                                            <th style="background-color: darkblue; color:white; border: 1px solid black" colspan="1" style="background-color: darkblue; color:white">Sconfinamenti</th>
                                        </tr>

                                        <tr>
                                            <th colspan="1"></th>
                                            @if(isset($response['AnalisiIndebitamento']['RISCHI A REVOCA']))
                                                <td style="border: 1px solid black" colspan="1">{{number_format($response['AnalisiIndebitamento']['RISCHI A REVOCA']['Accordato Operativo'],0,',','.')}} €</td>
                                                <td style="border: 1px solid black" colspan="1">{{number_format($response['AnalisiIndebitamento']['RISCHI A REVOCA']['Utilizzato'],0,',','.')}} €</td>
                                                <td style="border: 1px solid black" colspan="1">{{number_format($response['AnalisiIndebitamento']['RISCHI A REVOCA']['Sconfinamenti'],0,',','.')}} €</td>
                                            @else
                                                <td style="border: 1px solid black" colspan="1">0 €</td>
                                                <td style="border: 1px solid black" colspan="1">0 €</td>
                                                <td style="border: 1px solid black" colspan="1">0 €</td>
                                            @endif
                                        </tr>
                                        </tbody>
                                    </table>
                                    <table class="table table-hover table-light table-bordered">
                                        <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th scope="col" colspan="3" class="text-center" style="background-color: forestgreen; color:white">Scadenza</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <th colspan="1"></th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Valore Accordato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Utilizzato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Sconfinamenti</th>
                                        </tr>

                                        <tr>
                                            <td colspan="1"></td>
                                            @if(isset($response['AnalisiIndebitamento']['RISCHI A SCADENZA']))
                                                <td style="border: 1px solid black" colspan="1">{{number_format($response['AnalisiIndebitamento']['RISCHI A SCADENZA']['Accordato Operativo'],0,',','.')}} €</td>
                                                <td style="border: 1px solid black" colspan="1">{{number_format($response['AnalisiIndebitamento']['RISCHI A SCADENZA']['Utilizzato'],0,',','.')}} €</td>
                                                <td style="border: 1px solid black" colspan="1">{{number_format($response['AnalisiIndebitamento']['RISCHI A SCADENZA']['Sconfinamenti'],0,',','.')}} €</td>
                                            @else
                                                <td style="border: 1px solid black" colspan="1">0 €</td>
                                                <td style="border: 1px solid black" colspan="1">0 €</td>
                                                <td style="border: 1px solid black" colspan="1">0 €</td>
                                            @endif
                                        </tr>
                                        </tbody>
                                    </table>
                                    <table class="table table-hover table-light table-bordered">
                                        <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th scope="col" colspan="3" class="text-center" style="background-color: orangered; color:white">Autoliquidante</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <th colspan="1"></th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Valore Accordato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Utilizzato</th>
                                            <th colspan="1" style="background-color: darkblue; color:white">Sconfinamenti</th>
                                        </tr>

                                        <tr>
                                            <td colspan="1"></td>
                                            @if(isset($response['AnalisiIndebitamento']['RISCHI AUTOLIQUIDANTI']))

                                                <td style="border: 1px solid black" colspan="1">{{number_format($response['AnalisiIndebitamento']['RISCHI AUTOLIQUIDANTI']['Accordato Operativo'],0,',','.')}} €</td>
                                                <td style="border: 1px solid black" colspan="1">{{number_format($response['AnalisiIndebitamento']['RISCHI AUTOLIQUIDANTI']['Utilizzato'],0,',','.')}} €</td>
                                                <td style="border: 1px solid black" colspan="1">@if(isset($response['AnalisiIndebitamento']['RISCHI AUTOLIQUIDANTI']['Sconfinamenti'])){{number_format($response['AnalisiIndebitamento']['RISCHI AUTOLIQUIDANTI']['Sconfinamenti'],0,',','.')}} @else 0 @endif €</td>
                                            @else
                                                <td style="border: 1px solid black"  colspan="1">0 €</td>
                                                <td style="border: 1px solid black" colspan="1">0 €</td>
                                                <td style="border: 1px solid black" colspan="1">0 €</td>

                                            @endif
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                          <div class="card">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card overflow-hidden">
                                            <div class="card-header border-bottom-0">
                                                <div class="card-title">Grafico Rischi a Revoca</div>
                                            </div>
                                            <div class="card-body">
                                                <div class="h-400 w-500" id="flotArea1"></div>
                                            </div>
                                            <div class="card-bottom">
                                                <div id="legendContainer1">

                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- col-6 -->
                                    <div class="col-lg-6">
                                        <div class="card overflow-hidden">
                                            <div class="card-header border-bottom-0">
                                                <div class="card-title">Grafico Rischi a Scadenza</div>
                                            </div>
                                            <div class="card-body">
                                                <div class="h-400 w-500" id="flotArea2"></div>
                                            </div>
                                            <div class="card-bottom">
                                                <div id="legendContainer2">

                                                </div>
                                            </div>
                                            <div class="card-bottom">
                                                <div id="legendContainer2">

                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- col-6 -->
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card overflow-hidden">
                                            <div class="card-header border-bottom-0">
                                                <div class="card-title">Grafico Rischi Autoliquidanti</div>
                                            </div>
                                            <div class="card-body">
                                                <div class="h-400 w-500" id="flotArea3"></div>
                                            </div>
                                            <div class="card-bottom">
                                                <div id="legendContainer3">

                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- col-6 -->
                                    <div class="col-lg-6">
                                        <div class="card overflow-hidden">
                                            <div class="card-header border-bottom-0">
                                                <div class="card-title">Grafico Indebitamento</div>
                                            </div>
                                            <div class="card-body">
                                                <div class="h-400 w-500" id="flotArea4"></div>
                                            </div>
                                            <div class="card-bottom">
                                                <div id="legendContainer4">

                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- col-6 -->
                                </div>

                            </div>
                            </div>

                      <div class="tab-pane fade" id="pills-rischigaranzie" role="tabpanel" aria-labelledby="pills-rischigaranzie">
                                <h2 align="center">Analisi indebitamento</h2>
                                <table class="table table-hover table-bordered table-light">
                                    <thead>
                                    <tr>
                                        <th class="text-center" colspan="3" scope="col" style="background-color: orange">Posizione di rischio</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th class="text-center" colspan="3" style="background-color: gray; color: whitesmoke">Posizioni di rischio gestibili</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Crediti Scaduti</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Crediti Scaduti Impagati</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Incidenza % Impagati</th>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid orange">{{number_format($rischiGaranzie['CreditiScaduti'],0,',','.')}}€</td>
                                        <td style="border: 1px solid orange">{{number_format($rischiGaranzie['CreditiScadutiImpagati'],0,',','.')}}€</td>
                                        <td style="border: 1px solid orange">{{$incidenzaImpagati}}%</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <table class="table table-hover table-bordered table-light">
                                    <thead>
                                    <tr>
                                        <th class="text-center" colspan="2" scope="col" style="background-color: orange">Posizione di rischio</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Scaduti/Sconfinati > 90gg < 180gg</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Scaduti/Sconfinati > 180gg</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <td style="border: 1px solid orange">@if(isset($rischiGaranzie['Oltre90']) && $rischiGaranzie['Oltre90'] !=0) {{number_format($rischiGaranzie['Oltre90'],0,',','.')}}€ @else {{'0 €'}} @endif</td>
                                        <td style="border: 1px solid orange">@if(isset($rischiGaranzie['Oltre180']) && $rischiGaranzie['Oltre180'] !=0) {{number_format($rischiGaranzie['Oltre180'],0,',','.')}}€ @else {{'0 €'}} @endif</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <table class="table table-hover table-bordered table-light">
                                    <thead>
                                    <tr>
                                        <th class="text-center" colspan="3" scope="col" style="background-color: orange">Posizione di rischio</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center" colspan="3" style="background-color: gray; color: whitesmoke">Posizioni pregiudizievoli</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Sofferenze</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Crediti passati a perdita</th>
                                        <th class="text-center" colspan="1" style="background-color: gray; color: whitesmoke">Crediti Contestati</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <td style="border: 1px solid orange">@if(isset($rischiGaranzie['Sofferenze']) && $rischiGaranzie['Sofferenze'] != 0) {{number_format($rischiGaranzie['Sofferenze'],0,',','.')}}€ @else {{'0 €'}} @endif</td>
                                        <td style="border: 1px solid orange">@if(isset($rischiGaranzie['CreditiPassatiPerdita']) && $rischiGaranzie['CreditiPassatiPerdita'] != 0) {{number_format($rischiGaranzie['CreditiPassatiPerdita'],0,',','.')}}€ @else {{'0 €'}} @endif</td>
                                        <td style="border: 1px solid orange">@if(isset($rischiGaranzie['CreditiContestati']) && $rischiGaranzie['CreditiContestati'] != 0) {{number_format($rischiGaranzie['CreditiContestati'],0,',','.')}}€ @else {{'0 €'}} @endif</td>
                                    </tr>
                                    </tbody>
                                </table>

                                <table class="table table-hover table-bordered table-light">
                                    <tbody>
                                        <tr>
                                            <th style="border: 1px solid black">Tot. Valore Garanzie - Info sui garanti</th>
                                            <th style="border: 1px solid black">{{number_format($informazioniGaranti['Tot. Valore Garanzia'],0,',','.')}}€</th>
                                        </tr>
                                        <tr>
                                            <th style="border: 1px solid black">Tot. Importo Garantito - Info sui garanti</th>
                                            <th style="border: 1px solid black">{{number_format($informazioniGaranti['Tot. importo garantito'],0,',','.')}}€</th>
                                        </tr>
                                    </tbody>
                                </table>
                                <table class="table table-hover table-bordered table-light">
                                    <tbody>
                                        <tr>
                                            <th style="border: 1px solid black">Tot. Valore Garanzie - Garanzie Ricevute</th>
                                            <th style="border: 1px solid black">@if(isset($garanzieRicevute['Garanzia'])) {{number_format($garanzieRicevute['Garanzia'],0,',','.')}}€ @endif</th>
                                        </tr>
                                        <tr>
                                            <th style="border: 1px solid black">Tot. Importo Garantito - Garanzie Ricevute</th>
                                            <th style="border: 1px solid black">@if(isset($garanzieRicevute['Garantito'])) {{number_format($garanzieRicevute['Garantito'],0,',','.')}}€ @endif</th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}
