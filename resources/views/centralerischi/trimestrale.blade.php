@extends('backend.layouts.app')

@section('content')


<div class="container">
    <div class="row mt-3">
        <div class="card">
            <div class="card-title"> <a data-toggle="tooltip" title="Scarica il report con la tua analisi dettagliata cliccando su questo tasto" class="btn btn-primary" href="{{route('admin.pdf.dettagliata')}}">Stampa Analisi Dettagliata</a></div>
            <div class="card-body">
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li onclick="asyncCall1()" style="width: 25%" class="nav-item">
                        <a style="margin: auto; width: 100%" class="nav-link active" id="pills-trimestre1-tab" data-toggle="pill" href="#pills-trimestre1" role="tab" aria-controls="pills-trimestre1" aria-selected="true">{{substr($lastTwelveMonths[11]['mese'],0,3).' '.($lastTwelveMonths[11]['anno']-2000)}} - {{substr($lastTwelveMonths[9]['mese'],0,3).' '.($lastTwelveMonths[9]['anno']-2000)}}</a>
                    </li>
                    <li onclick="asyncCall2()" style="width: 25%" class="nav-item">
                        <a style="margin: auto; width: 100%" class="nav-link" id="pills-trimestre2-tab" data-toggle="pill" href="#pills-trimestre2" role="tab" aria-controls="pills-trimestre2" aria-selected="true">{{substr($lastTwelveMonths[8]['mese'],0,3).' '.($lastTwelveMonths[8]['anno']-2000)}} - {{substr($lastTwelveMonths[6]['mese'],0,3).' '.($lastTwelveMonths[6]['anno']-2000)}}</a>
                    </li>
                    <li onclick="asyncCall3()" style="width: 25%" class="nav-item">
                        <a style="margin: auto; width: 100%" class="nav-link" id="pills-trimestre3-tab" data-toggle="pill" href="#pills-trimestre3" role="tab" aria-controls="pills-trimestre3" aria-selected="true">{{substr($lastTwelveMonths[5]['mese'],0,3).' '.($lastTwelveMonths[5]['anno']-2000)}} - {{substr($lastTwelveMonths[3]['mese'],0,3).' '.($lastTwelveMonths[3]['anno']-2000)}}</a>
                    </li>
                    <li onclick="asyncCall4()" style="width: 25%" class="nav-item">
                        <a style="margin: auto; width: 100%" class="nav-link" id="pills-trimestre4-tab" data-toggle="pill" href="#pills-trimestre4" role="tab" aria-controls="pills-trimestre4" aria-selected="true">{{substr($lastTwelveMonths[2]['mese'],0,3).' '.($lastTwelveMonths[2]['anno']-2000)}} - {{substr($lastTwelveMonths[0]['mese'],0,3).' '.($lastTwelveMonths[0]['anno']-2000)}}</a>
                    </li>
                </ul>
                <hr>

                <div class="tab-content" id="pills-tabContent">
                    @for($i = 0; $i < count($trimestri); $i++)
                    <script>
                        async function asyncCall<?php echo($i+1); ?>() {
                          const result = await resolveAfter2Seconds();
                          drawChart<?php echo($i+1); ?>();
                        }
                        
                        function resolveAfter2Seconds() {
                          return new Promise(resolve => {
                            setTimeout(() => {
                              resolve('resolved');
                            }, 200);
                          });
                        }
                        </script>
                    <div class="tab-pane fade @if($i==0) show active @endif" id="pills-trimestre{{$i+1}}" role="tabpanel" aria-labelledby="pills-trimestre{{$i+1}}">
                        <div class="card mt-3">
                            <div class="card-body">
                                <script>
                                    google.charts.load('current', {'packages':['corechart']});
                                     google.charts.setOnLoadCallback(drawChart<?php echo($i+1); ?>);
                               
                                     function drawChart<?php echo($i+1); ?>() {
                                        //pies
                                       var data = google.visualization.arrayToDataTable(
                                           <?php echo (json_encode($affidamentiPerCategoria[$i]['General'])) ?>
                                       );
                               
                                       var options = {
                                         title: "Struttura Affidamenti"
                                       };
                               
                                       var chart = new google.visualization.PieChart(document.getElementById('piechart<?=$i?>'));
                               
                                       chart.draw(data, options);

                                       //end pies

                                       //stacked bar charts
                                       
                                       var testData1 = [<?php echo (json_encode($affidamentiPerCategoria[$i]["Banche"])) ?>]
                                    

                                       var data2 = google.visualization.arrayToDataTable(testData1[0]);
                                    
                                        var options2 = {
                                            width: 600,
                                            height: 400,
                                            legend: { position: 'top', maxLines: 3 },
                                            bar: { groupWidth: '75%' },
                                            isStacked: true,
                                        };
                               
                                        var chart2 = new google.visualization.ColumnChart(document.getElementById('barchart<?=$i?>'));

                                        chart2.draw(data2, options2);
                                       //end bar charts
                                    }
                               
                               </script>
                                <div id="piechart<?=$i?>" style="width: 100%; height: 500px;"></div>
                                <hr>
                                <div id="barchart<?=$i?>" style="margin-left: 20%"></div>
                                <hr>
                                <h1>Modalità utilizzo linee di credito</h1>
                                <div class="table-responsive">
									<table class="table table-hover card-table table-vcenter text-nowrap mb-0">
										<thead>
											<tr>
                                                <th>Tipologia di rischio</th>
												@foreach($affidamentiPerCategoria[$i]['Utilizzo'] as $singleMonth=>$stuff)
                                                <th>{{$singleMonth}}</th>
                                                @endforeach
											</tr>
										</thead>
										<tbody>
                                            @php 
                                            $categories = array('RISCHI A REVOCA','RISCHI A SCADENZA','RISCHI AUTOLIQUIDANTI');
                                            @endphp
                                            @foreach($categories as $singleCategory)
                                            <tr>
                                                <th scope="row">{{$singleCategory}}</th>
                                                @foreach($affidamentiPerCategoria[$i]['Utilizzo'] as $singleMonth=>$utilizzatoData)
                                                @if(isset($utilizzatoData[$singleCategory]))
                                                    <td>{{number_format($utilizzatoData[$singleCategory],2,'.',',')}} %
                                                        @if($singleCategory == 'RISCHI AUTOLIQUIDANTI')
                                                            @if((float)number_format($utilizzatoData[$singleCategory],2,'.',',') < 40)
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif((float)number_format($utilizzatoData[$singleCategory],2,'.',',') >= 40 && (float)number_format($utilizzatoData[$singleCategory],2,'.',',') <= 70)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>    
                                                            @elseif((float)number_format($utilizzatoData[$singleCategory],2,'.',',') > 70 && (float)number_format($utilizzatoData[$singleCategory],2,'.',',') < 95)
                                                                <span style="{{$styles['orange']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif((float)number_format($utilizzatoData[$singleCategory],2,'.',',') >= 95)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>                                                     
                                                            @endif
                                                        @elseif($singleCategory == 'RISCHI A SCADENZA')
                                                            @if((float)number_format($utilizzatoData[$singleCategory],2,'.',',') == 100)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>   
                                                            @elseif((float)number_format($utilizzatoData[$singleCategory],2,'.',',') > 100)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>    
                                                            @elseif((float)number_format($utilizzatoData[$singleCategory],2,'.',',') < 100)
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @endif
                                                        @elseif($singleCategory == 'RISCHI A REVOCA')
                                                            @if((float)number_format($utilizzatoData[$singleCategory],2,'.',',') < 40 || ((float)number_format($utilizzatoData[$singleCategory],2,'.',',') > 70 && (float)number_format($utilizzatoData[$singleCategory],2,'.',',') < 90))
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif((float)number_format($utilizzatoData[$singleCategory],2,'.',',') >= 40 && (float)number_format($utilizzatoData[$singleCategory],2,'.',',') <= 70)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>    
                                                            @elseif((float)number_format($utilizzatoData[$singleCategory],2,'.',',') >= 90 && (float)number_format($utilizzatoData[$singleCategory],2,'.',',') < 100)
                                                                <span style="{{$styles['orange']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif((float)number_format($utilizzatoData[$singleCategory],2,'.',',') >= 100)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>   
                                                            @endif                                                      
                                                        @endif                                        
                                                    </td>
                                                @else
                                                    <td>0%</td>
                                                @endif
                                                @endforeach
                                            </tr>
                                            @endforeach
                                        </tbody>
									</table>
								</div>
                                <hr>
                                <h1>Verifica presenza di sconfini</h1>
                                @if(!empty($presenzaSconfini[$i]))
                                <div class="table-responsive">
									<table class="table table-hover card-table table-vcenter text-nowrap mb-0">
										<thead>
                                            <tr>
                                                <th></th>
                                                @foreach($trimestri[$i] as $singleMonth)
                                                <th colspan="2">{{$singleMonth['mese']}}</th>
                                                @endforeach
                                            </tr>
                                            <tr>
                                                <th>Tipologia di rischio</th>
                                                <th>Sconfino</th>
                                                <th>Disponibilità</th>
                                                <th>Sconfino</th>
                                                <th>Disponibilità</th>
                                                <th>Sconfino</th>
                                                <th>Disponibilità</th>
                                            <tr>
										</thead>
										<tbody>
                                            @foreach($categories as $singolaCategoria)
                                                <tr>
                                                    <th>{{$singolaCategoria}}</th>
                                                @foreach($presenzaSconfini[$i] as $singleSconfinoMonth=>$multipleCategories)
                                                    @if(isset($multipleCategories[$singolaCategoria]['Importo']))
                                                        <td>{{number_format($multipleCategories[$singolaCategoria]['Importo'],0,',','.')}} €</td>
                                                        <td>{{number_format($multipleCategories[$singolaCategoria]['Disp'],0,',','.')}} €</td>
                                                    @else
                                                        <td>-</td>
                                                        <td>-</td>
                                                    @endif
                                                @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
									</table>
								</div>
                                @else
                                <h4>Non sono presenti sconfini</h4>
                                @endif
                                <hr>
                                <h1>Verifica sui crediti scaduti</h1>
                                <div class="table-responsive">
									<table class="table table-hover card-table table-vcenter text-nowrap mb-0">
										<thead>
											<tr>
                                                <th></th>
												@foreach($presenzaCreditiScaduti[$i] as $singleMonth=>$stuff)
                                                <th>{{$singleMonth}}</th>
                                                @endforeach
											</tr>
										</thead>
										<tbody>   
                                            <tr>   
                                                <th>Crediti scaduti impagati</th>
                                                @foreach($presenzaCreditiScaduti[$i] as $singleMonth=>$scadutiData)
                                                    <td>{{number_format($scadutiData['Impagati'],0,',','.')}} €</td>
                                                @endforeach
                                            </tr>
                                            <tr>   
                                                <th>Totale Revoca inutilizzata</th>
                                                @foreach($presenzaCreditiScaduti[$i] as $singleMonth=>$scadutiData)
                                                    <td>{{number_format($scadutiData['Affidamento'],0,',','.')}} €</td>
                                                @endforeach
                                            </tr>
                                        </tbody>
									</table>
								</div>
                                <hr>
                                <h1>Verifica disponibilità inutilizzate</h1>
                                <div class="table-responsive">
									<table class="table table-hover card-table table-vcenter text-nowrap mb-0">
										<thead>
											<tr>
                                                <th></th>
												@foreach($disponibilitaInutilizzata[$i] as $singleMonth=>$stuff)
                                                    <th>{{$singleMonth}}</th>
                                                @endforeach
											</tr>
										</thead>
										<tbody>   
                                            <tr>   
                                                <th>Disponibilità Totale</th>
                                                @foreach($disponibilitaInutilizzata[$i] as $singleMonth=>$disponibilitaData)
                                                    <td>{{number_format($disponibilitaData,0,',','.')}} €</td>
                                                @endforeach
                                            </tr>
                                        </tbody>
									</table>
								</div>
                                <hr>
                                <h1>Peso debiti a breve termine</h1>
                                <div class="table-responsive">
									<table class="table table-hover card-table table-vcenter text-nowrap mb-0">
										<thead>
											<tr>
                                                <th></th>
												@foreach($pesoDebitiBreveTermine[$i] as $singleMonth=>$stuff)
                                                    <th>{{$singleMonth}}</th>
                                                @endforeach
											</tr>
										</thead>
										<tbody>   
                                            <tr>   
                                                <th>Peso debiti</th>
                                                @foreach($pesoDebitiBreveTermine[$i] as $singleMonth=>$pesoDebitiData)
                                                    <th class="text-center">{{number_format($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'],2,'.',',')}} %
                                                @endforeach
                                            </tr>
                                        </tbody>
									</table>
								</div>
                                <hr>
                                <h1>Verifica segnalazioni gravi</h1>
                                <div class="table-responsive">
									<table class="table table-hover card-table table-vcenter text-nowrap mb-0">
										<thead>
											<tr>
                                                <th></th>
												@foreach($trimestri[$i] as $singleIndex=>$month)
                                                    <th>{{$month['mese']}}</th>
                                                @endforeach
											</tr>
										</thead>
										<tbody>   
                                            <tr>   
                                                <th>Presenza di crediti con inadempimenti persistenti (scaduti/sconfinati > 90gg e < 180gg e/o scaduti/sconfinati > 180gg)</th>
                                                @foreach($trimestri[$i] as $singleIndex=>$month)
                                                    @if(isset($segnalazioniGravi[$i]['ScadSconf'][$month['mese']]))
                                                        <td>{{$segnalazioniGravi[$i]['ScadSconf'][$month['mese']]}}</td>
                                                    @else
                                                        <td>0 €</td>
                                                    @endif
                                                @endforeach
                                            </tr>
                                            <tr>   
                                                <th>Presenza di situazioni particolarmente gravi (presenza di sofferenze, crediti passati a perdita)</th>
                                                @foreach($trimestri[$i] as $singleIndex=>$month)
                                                    @if(isset($segnalazioniGravi[$i]['Sofferenze'][$month['mese']]))
                                                        <td>{{$segnalazioniGravi[$i]['Sofferenze'][$month['mese']]}}</td>
                                                    @else
                                                        <td>0 €</td>
                                                    @endif
                                                @endforeach
                                            </tr>
                                        </tbody>
									</table>
								</div>
                                <hr>
                                <h1>Verifica delle garanzie</h1>
                                <div class="table-responsive">
									<table class="table table-hover card-table table-vcenter text-nowrap mb-0">
										<thead>
											<tr>
                                                <th>Informazioni Sui Garanti</th>
												@foreach($trimestri[$i] as $singleIndex=>$month)
                                                    <th>{{$month['mese']}}</th>
                                                @endforeach
											</tr>
										</thead>
										<tbody>   
                                            <tr>   
                                                <th>Garanzia</th>
                                                @foreach($trimestri[$i] as $singleIndex=>$month)
                                                    <td>{{number_format($verificaGaranzie[$i]['InfoGaranti'][$month['mese']]['Importo Garantito'],0,',','.')}} €</td>
                                                @endforeach
                                            </tr>
                                            <tr>   
                                                <th>Importo Garantito</th>
                                                @foreach($trimestri[$i] as $singleIndex=>$month)
                                                    <td>{{number_format($verificaGaranzie[$i]['InfoGaranti'][$month['mese']]['Garanzia'],0,',','.')}} €</td>
                                                @endforeach
                                            </tr>
                                        </tbody>
									</table>
								</div>
                                <hr>
                                <div class="table-responsive">
									<table class="table table-hover card-table table-vcenter text-nowrap mb-0">
										<thead>
											<tr>
                                                <th>Garanzie Ricevute</th>
												@foreach($trimestri[$i] as $singleIndex=>$month)
                                                    <th>{{$month['mese']}}</th>
                                                @endforeach
											</tr>
										</thead>
										<tbody>   
                                            <tr>   
                                                <th>Garanzia</th>
                                                @foreach($trimestri[$i] as $singleIndex=>$month)
                                                    <td>{{number_format($verificaGaranzie[$i]['GaranzieRicevute'][$month['mese']]['Garanzia'],0,',','.')}} €</td>
                                                @endforeach
                                            </tr>
                                            <tr>   
                                                <th>Importo Garantito</th>
                                                @foreach($trimestri[$i] as $singleIndex=>$month)
                                                    <td>{{number_format($verificaGaranzie[$i]['GaranzieRicevute'][$month['mese']]['Importo Garantito'],0,',','.')}} €</td>
                                                @endforeach
                                            </tr>
                                        </tbody>
									</table>
								</div>
                                <hr>
                                <h1>Uso degli affidamenti</h1>
                                <p>Tale analisi mostra la percentuale di uso degli affidamenti dei crediti per cassa rispetto ai fidi concessi al fine di evidenziare eventuali tensioni finanziarie.</p> <br>
                                <small>(*)MLT = Medio e Lungo Termine</small>
                                <br>
                                @foreach($categories as $singolaCategoria)
                                    
                                        @if($singolaCategoria == 'RISCHI A REVOCA')
                                        <h3>Linee di credito a Revoca</h3>
                                        <p>Per la <b>tabella sui rischi a revoca</b> abbiamo la seguente legenda: <br> 

                                        <span style="{{$styles['green']}}">
                                            <i class="feather feather-check-circle"></i>
                                        </span>
                                        Corretto utilizzo delle linee di credito a revoca<br> 
                                        <span style="{{$styles['yellow']}}">
                                            <i class="feather feather-alert-circle"></i>
                                        </span>
                                        Sottoutilizzo / sovrautilizzo rispetto alle soglie ideali <br> 
                                        <span style="{{$styles['orange']}}">
                                            <i class="feather feather-alert-circle"></i>
                                        </span>
                                        Utilizzo non ottimale perché troppo vicino al fido concesso <br> 
                                        <span style="{{$styles['red']}}">
                                            <i class="feather feather-x"></i>
                                        </span> 
                                        Tensione finanziaria <br>
                                        </p>

                                        @elseif($singolaCategoria == 'RISCHI A SCADENZA')
                                        <h3>Linee di credito a Scadenza</h3>
                                        <p>Per la <b>tabella sui rischi a scadenza</b> abbiamo la seguente legenda: <br> 
                                        <span style="{{$styles['green']}}">
                                            <i class="feather feather-check-circle"></i>
                                        </span>
                                        Corretto utilizzo delle linee di credito a scadenza<br> 
                                        <span style="{{$styles['yellow']}}">
                                            <i class="feather feather-alert-circle"></i>
                                        </span>
                                        Sottoutilizzo dei fidi <br> 
                                        <span style="{{$styles['red']}}">
                                            <i class="feather feather-x"></i>
                                        </span> 
                                        Tensione finanziaria <br>
                                        </p>

                                        @elseif($singolaCategoria == 'RISCHI AUTOLIQUIDANTI')

                                        <h3>Linee di credito Autoliquidanti</h3>
                                        
                                        <p> Per la <b>tabella sugli autoliquidanti</b> abbiamo la seguente legenda: <br> 
                                        <span style="{{$styles['green']}}">
                                            <i class="feather feather-check-circle"></i>
                                        </span>
                                            Corretto utilizzo delle linee autoliquidanti  <br> 
                                        <span style="{{$styles['yellow']}}">
                                            <i class="feather feather-alert-circle"></i>
                                        </span>
                                            Sottoutilizzo / sovrautilizzo dei fidi <br> 
                                        <span style="{{$styles['orange']}}">
                                            <i class="feather feather-alert-circle"></i>
                                        </span>
                                            Utilizzo non ottimale perché troppo vicino al fido concesso <br> 
                                        <span style="{{$styles['red']}}">
                                            <i class="feather feather-x"></i>
                                        </span> 
                                            Tensione finanziaria <br>
                                        @endif
                                        </p>

                                    <div class="table-responsive">
                                        <table class="table table-hover card-table table-vcenter text-nowrap mb-0">
                                            <tbody>   
                                                @foreach($affidamenti[$i]['Dettaglio'] as $singleMonth=>$multipleBanks)
                                                <tr>
                                                    <th>{{$singleMonth}}</th>
                                                    <th>Utilizzato</th>
                                                    <th>Accordato</th>
                                                    <th>% Utilizzato / Accordato</th>
                                                    @if($singolaCategoria == 'RISCHI A SCADENZA')
                                                    <th>Tipo</th>
                                                    @elseif($singolaCategoria == 'RISCHI A REVOCA')
                                                    <th>Saldo Medio</th>
                                                    @endif
                                                </tr>
                                                    @foreach($multipleBanks as $singleBank=>$singleBankData)
                                                        @if($singolaCategoria == 'RISCHI A SCADENZA')
                                                        @if(isset($affidamenti[$i]['DettaglioPerTipo']['Breve'][$singleMonth][$singleBank][$singolaCategoria]))
                                                        <tr>   
                                                            <td>{{$singleBank}}</td>
                                                            <td>{{number_format($affidamenti[$i]['DettaglioPerTipo']['Breve'][$singleMonth][$singleBank][$singolaCategoria]['Utilizzato'],0,',','.')}} €</td>
                                                            <td>{{number_format($affidamenti[$i]['DettaglioPerTipo']['Breve'][$singleMonth][$singleBank][$singolaCategoria]['Accordato'],0,',','.')}} €</td>
                                                            <td>{{number_format($affidamenti[$i]['DettaglioPerTipo']['Breve'][$singleMonth][$singleBank][$singolaCategoria]['Percentuale'],0,'.',',')}}% 
                                                                @if((float)number_format($affidamenti[$i]['DettaglioPerTipo']['Breve'][$singleMonth][$singleBank][$singolaCategoria]['Percentuale'],2,'.',',') == 100)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>   
                                                                @elseif((float)number_format($affidamenti[$i]['DettaglioPerTipo']['Breve'][$singleMonth][$singleBank][$singolaCategoria]['Percentuale'],2,'.',',') > 100)
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                        </span>    
                                                                @elseif((float)number_format($affidamenti[$i]['DettaglioPerTipo']['Breve'][$singleMonth][$singleBank][$singolaCategoria]['Percentuale'],2,'.',',') < 100)
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                    @endif    
                                                                </td>
                                                                <td>Breve</td>
                                                        </tr>
                                                        @endif
                                                        @if(isset($affidamenti[$i]['DettaglioPerTipo']['MLT'][$singleMonth][$singleBank][$singolaCategoria]))
                                                        <tr>   
                                                            <td>{{$singleBank}}</td>
                                                            <td>{{number_format($affidamenti[$i]['DettaglioPerTipo']['MLT'][$singleMonth][$singleBank][$singolaCategoria]['Utilizzato'],0,',','.')}} €</td>
                                                            <td>{{number_format($affidamenti[$i]['DettaglioPerTipo']['MLT'][$singleMonth][$singleBank][$singolaCategoria]['Accordato'],0,',','.')}} €</td>
                                                            <td>{{number_format($affidamenti[$i]['DettaglioPerTipo']['MLT'][$singleMonth][$singleBank][$singolaCategoria]['Percentuale'],0,'.',',')}}% 
                                                                @if((float)number_format($affidamenti[$i]['DettaglioPerTipo']['MLT'][$singleMonth][$singleBank][$singolaCategoria]['Percentuale'],2,'.',',') == 100)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>   
                                                                @elseif((float)number_format($affidamenti[$i]['DettaglioPerTipo']['MLT'][$singleMonth][$singleBank][$singolaCategoria]['Percentuale'],2,'.',',') > 100)
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                        </span>    
                                                                @elseif((float)number_format($affidamenti[$i]['DettaglioPerTipo']['MLT'][$singleMonth][$singleBank][$singolaCategoria]['Percentuale'],2,'.',',') < 100)
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                    @endif    
                                                                </td>
                                                                <td>MLT</td>

                                                        </tr>
                                                        @endif
                                                        @endif
                                                        @if(isset($singleBankData[$singolaCategoria]) && $singolaCategoria != 'RISCHI A SCADENZA')
                                                            <tr>   
                                                                <td>{{$singleBank}}</td>
                                                                <td>{{number_format($singleBankData[$singolaCategoria]['Utilizzato'],0,',','.')}} €</td>
                                                                <td>{{number_format($singleBankData[$singolaCategoria]['Accordato'],0,',','.')}} €</td>
                                                                <td>{{number_format($singleBankData[$singolaCategoria]['Percentuale'],0,'.',',')}}% 
                                                                    @if($singolaCategoria == 'RISCHI AUTOLIQUIDANTI')
                                                                        @if((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') < 40)
                                                                            <span style="{{$styles['yellow']}}">
                                                                                <i class="feather feather-alert-circle"></i>
                                                                            </span>
                                                                        @elseif((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') >= 40 && (float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') < 70)
                                                                            <span style="{{$styles['green']}}">
                                                                                <i class="feather feather-check-circle"></i>
                                                                            </span>    
                                                                        @elseif((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') >= 70 && (float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') < 95)
                                                                            <span style="{{$styles['orange']}}">
                                                                                <i class="feather feather-alert-circle"></i>
                                                                            </span>
                                                                        @else
                                                                            <span style="{{$styles['red']}}">
                                                                                <i class="feather feather-x"></i>
                                                                            </span>                                                     
                                                                        @endif
                                                                    @elseif($singolaCategoria == 'RISCHI A SCADENZA')
                                                                        @if((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') == 100)
                                                                            <span style="{{$styles['green']}}">
                                                                                <i class="feather feather-check-circle"></i>
                                                                            </span>   
                                                                        @elseif((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') > 100)
                                                                            <span style="{{$styles['red']}}">
                                                                                <i class="feather feather-x"></i>
                                                                            </span>    
                                                                        @elseif((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') < 100)
                                                                            <span style="{{$styles['yellow']}}">
                                                                                <i class="feather feather-alert-circle"></i>
                                                                            </span>
                                                                        @endif
                                                                    @elseif($singolaCategoria == 'RISCHI A REVOCA')
                                                                        @if((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') <= 40 || ((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') > 70 && (float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') < 90))
                                                                            <span style="{{$styles['yellow']}}">
                                                                                <i class="feather feather-alert-circle"></i>
                                                                            </span>
                                                                        @elseif((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') > 40 && (float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') <= 70)
                                                                            <span style="{{$styles['green']}}">
                                                                                <i class="feather feather-check-circle"></i>
                                                                            </span>    
                                                                        @elseif((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') >= 90 && (float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') < 10)
                                                                            <span style="{{$styles['orange']}}">
                                                                                <i class="feather feather-alert-circle"></i>
                                                                            </span>
                                                                        @elseif((float)number_format($singleBankData[$singolaCategoria]['Percentuale'],2,'.',',') >= 100)
                                                                            <span style="{{$styles['red']}}">
                                                                                <i class="feather feather-x"></i>
                                                                            </span>   
                                                                        @endif                                                      
                                                                    @endif
                                                                    </td>
                                                                @if($singolaCategoria == 'RISCHI A REVOCA')
                                                                 <td>{{number_format($singleBankData[$singolaCategoria]['SaldoMedio'],0,',','.')}} €</td>
                                                                @elseif($singolaCategoria == 'RISCHI A SCADENZA')
                                                                    @if(isset($singleBankData[$singolaCategoria]['Tipo']))
                                                                    <td>{{$singleBankData[$singolaCategoria]['Tipo']}}</td>
                                                                    @else
                                                                    <td></td>
                                                                    @endif
                                                                @endif
                                                            </tr>
                                                        @else
                                                            @if($singolaCategoria != 'RISCHI A SCADENZA') 
                                                            <tr>   
                                                                <td>{{$singleBank}}</td>
                                                                <td>0 €</td>
                                                                <td>0 €</td>
                                                                <td> - </td>
                                                                @if($singolaCategoria == 'RISCHI A REVOCA')
                                                                    <td>0 €</td>
                                                                @endif
                                                            </tr>
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                    @if($singolaCategoria == 'RISCHI A SCADENZA')
                                                    <tr>
                                                        <th>Totale Affidamenti a Breve</th>    
                                                        <th>{{number_format($affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Utilizzato'],0,',','.')}} €</th>
                                                        <th>{{number_format($affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Accordato'],0,',','.')}} €</th>
                                                        @if($affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Accordato'] !== 0)
                                                        <th>{{(float)number_format(($affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Utilizzato'] / $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Accordato']) *100,2,'.',',')}} %
                                                            @php
                                                            
                                                            $val = (float)number_format(($affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Utilizzato'] / $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Accordato']) *100,2,'.',',');
                                                            @endphp
                                                            @if($singolaCategoria == 'RISCHI AUTOLIQUIDANTI')
                                                                @if($val < 40)
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val >= 40 && $singleBankData[$singolaCategoria]['Percentuale'] < 70)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>    
                                                                @elseif($val >= 70 && $val < 95)
                                                                    <span style="{{$styles['orange']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @else
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>                                                     
                                                                @endif
                                                            @elseif($singolaCategoria == 'RISCHI A SCADENZA')
                                                                @if($val == 100)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>   
                                                                @elseif($val > 100)
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>    
                                                                @elseif($val < 100)
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @endif
                                                            @elseif($singolaCategoria == 'RISCHI A REVOCA')
                                                                @if($val <= 40 || ($val > 70 && $val < 90))
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val > 40 && $val <= 70)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>    
                                                                @elseif($val >= 90 && $val < 10)
                                                                    <span style="{{$styles['orange']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val >= 100)
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>   
                                                                @endif                                                      
                                                            @endif
                                                            
                                                            </th>
                                                        @else
                                                            <th> - </th>
                                                            <th> - </th>
                                                        @endif
                                                    </tr>
                                                    <tr>
                                                        <th>Totale Affidamenti MLT</th>   
                                                        <th>{{number_format($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Utilizzato'],0,',','.')}} €</th>
                                                        <th>{{number_format($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Utilizzato'],0,',','.')}} €</th>
                                                        @if($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Accordato'] !== 0)
                                                        <th>{{(float)number_format(($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Utilizzato'] / $affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Accordato']) *100,2,'.',',')}} %
                                                            
                                                            @php
                                                            
                                                            $val = (float)number_format(($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Utilizzato'] / $affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Accordato']) *100,2,'.',',');
                                                            
                                                            @endphp
                                                            @if($singolaCategoria == 'RISCHI AUTOLIQUIDANTI')
                                                                @if($val < 40)
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val >= 40 && $singleBankData[$singolaCategoria]['Percentuale'] < 70)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>    
                                                                @elseif($val >= 70 && $val < 95)
                                                                    <span style="{{$styles['orange']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @else
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>                                                     
                                                                @endif
                                                            @elseif($singolaCategoria == 'RISCHI A SCADENZA')
                                                                @if($val == 100)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>   
                                                                @elseif($val > 100)
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>    
                                                                @elseif($val < 100)
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @endif
                                                            @elseif($singolaCategoria == 'RISCHI A REVOCA')
                                                                @if($val <= 40 || ($val > 70 && $val < 90))
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val > 40 && $val <= 70)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>    
                                                                @elseif($val >= 90 && $val < 10)
                                                                    <span style="{{$styles['orange']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val >= 100)
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>   
                                                                @endif                                                      
                                                            @endif
                                                            
                                                            </th>
                                                        @else
                                                            <th> - </th>
                                                        @endif
                                                    </tr>
                                                    @endif
                                                    <tr>
                                                        <th>Totale Affidamenti</th> 
                                                        
                                                        @if($singolaCategoria == 'RISCHI A SCADENZA')
                                                        <th>{{number_format($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Utilizzato'] + $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Utilizzato'],0,',','.')}} €</th>
                                                        <th>{{number_format($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Accordato'] + $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Accordato'],0,',','.')}} €</th>
                                                        @if(($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Accordato'] + $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Accordato']) !== 0)
                                                            <th>{{(float)number_format(((($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Utilizzato'] + $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Utilizzato']) / ($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Accordato'] + $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Accordato']))*100),2,'.',',')}} %
                                                                
                                                                @php
                                                            
                                                            $val = (float)number_format(((($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Utilizzato'] + $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Utilizzato']) / ($affidamenti[$i]['Mensile']['MLT'][$singleMonth][$singolaCategoria]['Accordato'] + $affidamenti[$i]['Mensile']['Breve'][$singleMonth][$singolaCategoria]['Accordato']))*100),2,'.',',');
                                                            
                                                            @endphp
                                                            @if($singolaCategoria == 'RISCHI AUTOLIQUIDANTI')
                                                                @if($val < 40)
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val >= 40 && $singleBankData[$singolaCategoria]['Percentuale'] < 70)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>    
                                                                @elseif($val >= 70 && $val < 95)
                                                                    <span style="{{$styles['orange']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @else
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>                                                     
                                                                @endif
                                                            @elseif($singolaCategoria == 'RISCHI A SCADENZA')
                                                                @if($val == 100)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>   
                                                                @elseif($val > 100)
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>    
                                                                @elseif($val < 100)
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @endif
                                                            @elseif($singolaCategoria == 'RISCHI A REVOCA')
                                                                @if($val <= 40 || ($val > 70 && $val < 90))
                                                                    <span style="{{$styles['yellow']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val > 40 && $val <= 70)
                                                                    <span style="{{$styles['green']}}">
                                                                        <i class="feather feather-check-circle"></i>
                                                                    </span>    
                                                                @elseif($val >= 90 && $val < 10)
                                                                    <span style="{{$styles['orange']}}">
                                                                        <i class="feather feather-alert-circle"></i>
                                                                    </span>
                                                                @elseif($val >= 100)
                                                                    <span style="{{$styles['red']}}">
                                                                        <i class="feather feather-x"></i>
                                                                    </span>   
                                                                @endif                                                      
                                                            @endif
                                                                
                                                                </th>
                                                        @else
                                                        <td> - </td>
                                                        @endif
                                                        @else
                                                            <th>{{number_format($affidamenti[$i]['Mensile']['Totali'][$singleMonth][$singolaCategoria]['Utilizzato'],0,',','.')}} €</th>
                                                            <th>{{number_format($affidamenti[$i]['Mensile']['Totali'][$singleMonth][$singolaCategoria]['Accordato'],0,',','.')}} €</th>
                                                                @if($affidamenti[$i]['Mensile']['Totali'][$singleMonth][$singolaCategoria]['Accordato'] == 0)
                                                                <th>100%</th>
                                                                @else
                                                            <th>{{number_format(($affidamenti[$i]['Mensile']['Totali'][$singleMonth][$singolaCategoria]['Utilizzato'] / $affidamenti[$i]['Mensile']['Totali'][$singleMonth][$singolaCategoria]['Accordato'])*100,0,',','.')}} %</th>
                                                                @endif
                                                            @if($singolaCategoria == 'RISCHI A REVOCA')
                                                            <th>{{number_format($affidamenti[$i]['Mensile']['SaldoMedio'][$singleMonth][$singolaCategoria],0,',','.')}} €</th>
                                                        @endif                                                        
                                                    @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <hr>
                                @endforeach
                                <hr>
                                <h1>Analisi degli sconfini</h1>
                                <p> Tale analisi mostra i casi di sconfinamento (uso dei fidi oltre i limiti accordati) e l'eventuale disponibilità con la quale si sarebbe potuto evitare lo sconfino</p> 
                                <br>
                                @foreach($categories as $singolaCategoria)
                                    <h3>{{$singolaCategoria}}</h3>
                                    <div class="table-responsive">
                                        <table class="table table-hover card-table table-vcenter text-nowrap mb-0">
                                            <tbody>   
                                                @foreach($analisiSconfini[$i]['DatiSconfini'] as $singleMonth=>$multipleBanks)
                                                    <tr>
                                                        <th>{{$singleMonth}}</th>
                                                        <th>Utilizzato</th>
                                                        <th>Accordato</th>
                                                        <th>Disponibilità</th>
                                                        <th>Sconfino</th>
                                                    </tr>
                                                    @foreach($multipleBanks as $singleBank=>$analisiSconfinoData)
                                                        @if(isset($analisiSconfinoData[$singolaCategoria]))
                                                            <tr>
                                                                <td>{{$singleBank}}</td>
                                                                <td>{{number_format($analisiSconfinoData[$singolaCategoria]['Utilizzato'],0,',','.')}} €</td>
                                                                <td>{{number_format($analisiSconfinoData[$singolaCategoria]['Accordato'],0,',','.')}} €</td>
                                                                <td>{{number_format($analisiSconfinoData[$singolaCategoria]['Sconfino'],0,',','.')}} €</td>
                                                                <td>{{number_format($analisiSconfinoData[$singolaCategoria]['Accordato - Utilizzato'],0,',','.')}} €</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                    <tr>
                                                        <th>Tot. disponibilità {{$singleMonth}}</th>
                                                        <th>Tot. sconfini {{$singleMonth}}</th>
                                                    </tr>
                                                    <tr>
                                                        <td>{{number_format(-($analisiSconfini[$i]['DatiMensiliTotali'][$singleMonth][$singolaCategoria]),0,',','.')}} €</td>
                                                        <td>{{number_format($analisiSconfini[$i]['ConteggioSconfini'][$singleMonth][$singolaCategoria],0,',','.')}} €</td>
                                                    </tr>
                                                @endforeach
                                        </table>
                                    </div>
                                    <hr>
                                @endforeach
                                <h1>Crediti scaduti</h1>
                                <p>Le tabelle seguenti individuano la presenza di crediti autoliquidanti scaduti mostrando quelli pagati e quelli impagati e la disponibilità delle linee a revoca di compensare eventuali crediti scaduti impagati.</p> <br>
                                    <div class="table-responsive">
                                        <table class="table table-hover card-table table-vcenter text-nowrap mb-0">
                                            <tbody>   
                                                    @foreach($creditiRischi[$i]['CreditiScaduti']['Dettaglio'] as $singleMonth=>$multipleBanks)
                                                        <tr>
                                                            <th>{{$singleMonth}}</th>
                                                            <th>Crediti Scaduti</th>
                                                            <th>Crediti Impagati</th>
                                                            <th>Acc a Revoca - Utilizzato</th>
                                                            <th>% Impagati</th>
                                                        </tr>
                                                        @foreach($multipleBanks as $singleBank=>$singleBankData)
                                                            <tr>
                                                                <td>{{$singleBank}}</td>
                                                                <td>{{number_format($singleBankData['Scaduti'],0,',','.')}} €</td>
                                                                <td>{{number_format($singleBankData['Impagati'],0,',','.')}} €</td>
                                                                <td>{{number_format($singleBankData['Differenza'],0,',','.')}} €</td>
                                                                <td>{{number_format($singleBankData['Percentuale'],0,'.',',')}} %</td>
                                                            </tr>
                                                        @endforeach
                                                        <tr>
                                                            <th>Totale</th>
                                                            <th>{{number_format($creditiRischi[$i]['CreditiScaduti']['Mensile'][$singleMonth]['Scaduti'],0,',','.')}} €</th>
                                                            <th>{{number_format($creditiRischi[$i]['CreditiScaduti']['Mensile'][$singleMonth]['Impagati'],0,',','.')}} €</th>
                                                            <th>{{number_format($creditiRischi[$i]['CreditiScaduti']['Mensile'][$singleMonth]['Differenza'],0,',','.')}} €</th>
                                                            <th>{{number_format($creditiRischi[$i]['CreditiScaduti']['Mensile'][$singleMonth]['Percentuale'],0,',','.')}} %</th>
                                                        </tr>
                                                    @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <hr>
                                    <h1>Situazioni a rischio</h1>
                                    <p>Le tabelle seguenti mostrano la presenza di situazioni a rischio come scaduti o sconfini superiori ai 90 e 180 giorni e segnalazioni più gravi che potrebbero pregiudicare l'immagine finanziaria.</p> <br>
                                    <div class="table-responsive">
                                        <table class="table table-hover card-table table-vcenter text-nowrap mb-0">
                                            <tbody>   
                                                @foreach($creditiRischi[$i]['SituazioniRischio']['Dettaglio'] as $singleMonth=>$multipleBanks)
                                                    <tr>
                                                        <th>{{$singleMonth}}</th>
                                                        <th>Scaduti/Sconfinati fra 90 e 180gg</th>
                                                        <th>Scaduti/Sconfinati oltre 180gg</th>
                                                        <th>Sofferenze</th>
                                                        <th>Crediti passati a perdita</th>
                                                        <th>Crediti contestati</th>
                                                    </tr>
                                                    @foreach($multipleBanks as $singleBank=>$singleBankDataRischi)
                                                    <tr>
                                                        <th>{{$singleBank}}</th>
                                                        <td>{{number_format($singleBankDataRischi['ScadSconfEntro180'],0,',','.')}} €</td>
                                                        <td>{{number_format($singleBankDataRischi['ScadSconfOltre180'],0,',','.')}} €</td>
                                                        <td>{{number_format($singleBankDataRischi['Sofferenze'],0,',','.')}} €</td>
                                                        <td>{{number_format($singleBankDataRischi['CreditiPerdita'],0,',','.')}} €</td>
                                                        <td>{{number_format($singleBankDataRischi['Contestati'],0,',','.')}} €</td>
                                                    </tr>
                                                    @endforeach
                                                    <tr>
                                                        <th>Totale</th>
                                                        <th>{{number_format($creditiRischi[$i]['SituazioniRischio']['Mensile'][$singleMonth]['ScadSconfEntro180'],0,',','.')}} €</th>
                                                        <th>{{number_format($creditiRischi[$i]['SituazioniRischio']['Mensile'][$singleMonth]['ScadSconfOltre180'],0,',','.')}} €</th>
                                                        <th>{{number_format($creditiRischi[$i]['SituazioniRischio']['Mensile'][$singleMonth]['Sofferenze'],0,',','.')}} €</th>                                                                
                                                        <th>{{number_format($creditiRischi[$i]['SituazioniRischio']['Mensile'][$singleMonth]['CreditiPerdita'],0,',','.')}} €</th>
                                                        <th>{{number_format($creditiRischi[$i]['SituazioniRischio']['Mensile'][$singleMonth]['Contestati'],0,',','.')}} €</th>
                                                    </tr>                    
                                                @endforeach    
                                            </tbody>
                                        </table>
                                    </div>
                                    <hr>
                                    <h1>Peso Debiti a Breve Termine</h1>
                                    <p>Le tabelle seguenti comparano le linee di credito a scadenza (revoca, autoliquidanti e scadenza a breve termine) rispetto all'accordato operativo dei crediti per cassa al fine di mostrare il peso dei debiti a breve termine (ovvero le linee di credito che potrebbero essere revocate con maggiore facilità).</p> <br>
                                    <h3>Incidenza Indebitamento a breve</h3>
                                    <div class="table-responsive">
                                        <table class="table table-hover card-table table-vcenter text-nowrap mb-0">
                                            <tbody>   
                                                @foreach($creditiScadutiBreveTermine[$i]['Dettaglio'] as $singleMonth=>$multipleBanks)
                                                    <tr>
                                                        <th></th>
                                                        <th class="text-center" colspan="3">Utilizzato</th>
                                                        <th></th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th>{{$singleMonth}}</th>
                                                        @foreach($categories as $singleCategoria)
                                                            <th class="text-center">{{$singleCategoria}}</th>
                                                        @endforeach
                                                        <th class="text-center">Accordato Totale</th>
                                                        <th class="text-center">Peso Debiti a Breve</th>
                                                    </tr>
                                                    @foreach($multipleBanks as $singleBank=>$singleBankDataBT)
                                                    <tr>
                                                        <th>{{$singleBank}}</th>
                                                        @foreach($categories as $singleCategoria)
                                                            <td class="text-center">{{number_format($singleBankDataBT[$singleCategoria],0,',','.')}} €</td>
                                                        @endforeach
                                                        <td class="text-center">{{number_format($singleBankDataBT['AccordatoTotale'],0,',','.')}} €</td>
                                                        <td class="text-center">{{number_format($singleBankDataBT['PesoDebitiBreveTermine'],2,'.',',')}} %
                                                        @if($singleCategoria == 'RISCHI AUTOLIQUIDANTI')
                                                            @if($singleBankDataBT['PesoDebitiBreveTermine'] < 40)
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif($singleBankDataBT['PesoDebitiBreveTermine'] >= 40 && $singleBankDataBT['PesoDebitiBreveTermine'] <= 70)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>    
                                                            @elseif($singleBankDataBT['PesoDebitiBreveTermine'] > 70 && $singleBankDataBT['PesoDebitiBreveTermine'] < 95)
                                                                <span style="{{$styles['orange']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif($singleBankDataBT['PesoDebitiBreveTermine'] >= 95)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>                                                     
                                                            @endif
                                                        @elseif($singleCategoria == 'RISCHI A SCADENZA')
                                                            @if($singleBankDataBT['PesoDebitiBreveTermine'] == 100)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>   
                                                            @elseif($singleBankDataBT['PesoDebitiBreveTermine'] > 100)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>    
                                                            @elseif($singleBankDataBT['PesoDebitiBreveTermine'] < 100)
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @endif
                                                        @elseif($singleCategoria == 'RISCHI A REVOCA')
                                                            @if($singleBankDataBT['PesoDebitiBreveTermine'] < 40 || ($singleBankDataBT['PesoDebitiBreveTermine'] > 70 && $singleBankDataBT['PesoDebitiBreveTermine'] < 90))
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif($singleBankDataBT['PesoDebitiBreveTermine'] >= 40 && $singleBankDataBT['PesoDebitiBreveTermine'] <= 70)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>    
                                                            @elseif($singleBankDataBT['PesoDebitiBreveTermine'] >= 90 && $singleBankDataBT['PesoDebitiBreveTermine'] < 100)
                                                                <span style="{{$styles['orange']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif($singleBankDataBT['PesoDebitiBreveTermine'] >= 100)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>   
                                                            @endif                                                      
                                                        @endif      
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                    <tr>
                                                        <th class="text-center">Totale Mensile</th>
                                                        @foreach($categories as $singolaCategoria)
                                                            <th class="text-center">{{number_format($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth][$singolaCategoria],0,',','.')}} €</th>
                                                        @endforeach
                                                        <th class="text-center">{{number_format($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['AccordatoTotale'],0,',','.')}} €</th>
                                                        <th class="text-center">{{number_format($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'],2,'.',',')}} %
                                                            
                                                            @if($singolaCategoria == 'RISCHI AUTOLIQUIDANTI')
                                                            @if($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] < 40)
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] >= 40 && $creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] <= 70)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>    
                                                            @elseif($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] > 70 && $creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] < 95)
                                                                <span style="{{$styles['orange']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] >= 95)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>                                                     
                                                            @endif
                                                        @elseif($singolaCategoria == 'RISCHI A SCADENZA')
                                                            @if($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] == 100)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>   
                                                            @elseif($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] > 100)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>    
                                                            @elseif($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] < 100)
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @endif
                                                        @elseif($singolaCategoria == 'RISCHI A REVOCA')
                                                            @if($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] < 40 || ($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] > 70 && $creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] < 90))
                                                                <span style="{{$styles['yellow']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] >= 40 && $creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] <= 70)
                                                                <span style="{{$styles['green']}}">
                                                                    <i class="feather feather-check-circle"></i>
                                                                </span>    
                                                            @elseif($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] >= 90 && $creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] < 100)
                                                                <span style="{{$styles['orange']}}">
                                                                    <i class="feather feather-alert-circle"></i>
                                                                </span>
                                                            @elseif($creditiScadutiBreveTermine[$i]['Mensile'][$singleMonth]['PesoDebitiBreveTermine'] >= 100)
                                                                <span style="{{$styles['red']}}">
                                                                    <i class="feather feather-x"></i>
                                                                </span>   
                                                            @endif                                                      
                                                        @endif    
                                                        </th>
                                                    </tr>
                                                @endforeach    
                                            </tbody>
                                        </table>
                                    </div>
                                    <hr>
                                    <h1>Informazioni sui garanti</h1>
                                    <p>Le tabelle seguenti mostrano in dettaglio le garanzie prestate dall'affidato (informazioni sui garanti) e le garanzie prestate sull'indebitamento di terzi (Garanzie ricevute).</p>
                                    <div class="table-responsive">
                                        <table class="table table-hover card-table table-vcenter text-nowrap mb-0">
                                            <tbody>   
                                                    @foreach($informazioniSuiGaranti[$i]['Dettaglio'] as $singleMonth=>$multipleBanks)
                                                        <tr>
                                                            <th>{{$singleMonth}}</th>
                                                            <th>Valore Garanzie</th>
                                                            <th>Importo Garantito</th>
                                                        </tr>
                                                        @foreach($multipleBanks as $singleBank=>$singleBankDataGaranti)
                                                            <tr>
                                                                <td>{{$singleBank}}</td>
                                                                <td>{{number_format($singleBankDataGaranti['Garanzia'],0,',','.')}} €</td>
                                                                <td>{{number_format($singleBankDataGaranti['Importo'],0,',','.')}} €</td>
                                                            </tr>
                                                        @endforeach
                                                        <tr>
                                                            <th>Totale</th>
                                                            <th>{{number_format($informazioniSuiGaranti[$i]['Mensile'][$singleMonth]['Garanzia'],0,',','.')}} €</th>
                                                            <th>{{number_format($informazioniSuiGaranti[$i]['Mensile'][$singleMonth]['Importo'],0,',','.')}} €</th>
                                                        </tr>
                                                    @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                            </div>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>


@endsection