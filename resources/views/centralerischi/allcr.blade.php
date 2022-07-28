@extends('backend.layouts.app')

@section('content')
<div class="page">
    <div class="page-main">
        <div class="main-content">
            <div class="container-fluid">
                <div class="text-center mt-5">
                    <div class="page-leftheader">
                        <h4 class="page-title">Centrale Rischi |<span class="font-weight-normal text-muted ml-2" style="color: red!important;">Key Performance Softwares S.r.l.</span></h4>
                </div>
                </div>
                    <div class="page-header d-xl-flex d-block">
                        <div class="page-leftheader">
                            <div class="d-flex align-items-end flex-wrap my-auto right-content breadcrumb-right">
                                <div class="btn-list">
                                   <a href="#"  class="mr-3"><button data-trigger="hover" data-toggle="tooltip" title="Con questo tasto puoi caricare il tuo file Centrale Rischi in formato .pdf . Il file deve essere il documento elettronico inviato da Banca d'Italia (non è possibile caricare scansioni di documenti cartacei)." class="btn btn-outline-success" ><span data-toggle="modal" data-target="#newcr"><i class="feather feather-plus fs-15 my-auto mr-2"></i>Importa PDF Centrale Rischi</span></button></a>
                                   <a class="mr-3" href="{{ route('admin.cr.centralerischi.truncate') }}"><button data-toggle="tooltip" title="Se vuoi cancellare il file pdf Centrale rischi che hai caricato puoi farlo cliccando su questo tasto" class="btn btn-outline-danger"><i class="feather feather-minus fs-15 my-auto mr-2"></i>Cancella Tutto</button></a> 
                                   <a href="{{ route('admin.cr.centralerischi.dettagliata') }}"><button data-toggle="tooltip" title="Vai all'analisi dettagliata della tua Centrale Rischi cliccando su questo tasto" class="btn btn-outline-primary"><i class="feather feather-activity fs-15 my-auto mr-2"></i>Analisi Dettagliata</button></a>
                                </div>
                            </div>
                        <!-- </div> -->
                    </div>
                    <div class="d-flex align-items-end flex-wrap my-auto right-content breadcrumb-right">
                        <a type="button" data-toggle="modal" data-target=".bd-example-modal-lg"><img src="https://img.icons8.com/flat-round/30/000000/question-mark.png"/></a>
                            <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                                <div style="background: white;" class="modal-dialog modal-lg">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="myLargeModalLabel">Informazione</h4>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                    </div>
                                    <div class="modal-body">
                                    La Centrale dei rischi (CR) è una banca dati che fornisce una fotografia d’insieme dell’esposizione debitoria dell’impresa nei confronti del sistema
                                    bancario e finanziario. Le informazioni non hanno valore certificativo. La CR comunica mensilmente agli intermediari il debito totale verso il sistema
                                    creditizio di ciascun cliente segnalato. La verifica delle informazioni contenute nella propria CR permette di conoscere lo status complessivo della
                                    propria situazione creditizia e/o di controllare l’esattezza delle informazioni registrate in Banca d’Italia.
                                    Grazie all’analisi della CR elaborata dal software è possibile capire come si viene percepiti dal sistema creditizio e prevedere l’esito di eventuali future
                                    richieste di finanziamento, nonché di programmare eventuali azioni correttive necessarie per migliorare il proprio Rating.
                                    Al fine di ottenere un’analisi completa si consiglia di caricare la Centrale dei Rischi contenente le rilevazioni degli ultimi 36 mesi. Qualora venissero
                                    caricati documenti contenenti periodi di tempo frammentati o troppo brevi, i risultati delle analisi potrebbero essere compromessi.
                                    Il sistema richiede il caricamento del file .pdf generato dai sistemi informativi della Banca d’Italia e scaricabile dal portale dedicato ai Servizi On Line di Banca d'Italia                                    </div>
                                </div>
                            </div>
                            </a>
                        </div>
                </div>
                
                <div class="row">

                    <div class="col-12">
                        <div class="table-responsive">
                            @if(isset($crData))
                                <x-forms.post :action="route('admin.cr.centralerischi.andamentale')" enctype="multipart/form-data">
                                    @if(count($periods) > 0)
                                        <x-backend.card>
                                            <x-slot name="body">
                                                <h3>Seleziona il Periodo da Analizzare:</h3>
                                                <ul>
                                                    <li><input type="radio" id="lastYear" name="period" value="1"> <b><label for="lastYear">Ultimo anno</label></b> (<span>{{$lastYear->format('d-m-Y').' - '.$lastDate->format('d-m-Y')}})</span></li>
                                                    <li><input type="radio" id="lastTwoYears" name="period" value="2">  <b><label for="lastTwoYears">Ultimi due anni</label></b> (<span>{{$lastTwoYears->format('d-m-Y').' - '.$lastDate->format('d-m-Y')}})</span></li>
                                                    <li><input type="radio" id="lastThreeYears" name="period" value="3"> <b><label for="lastThreeYears"> Ultimi tre anni</label></b> (<span>{{$lastThreeYears->format('d-m-Y').' - '.$lastDate->format('d-m-Y')}})</span></li>
                                                    <li><input type="radio" id="allYears" name="period" value="0"> <b><label for="allYears"> Tutti gli anni</label></b> (<span>{{$earliestDate->format('d-m-Y').' - '.$lastDate->format('d-m-Y')}}</span>)</li>
                                                </ul>
                                                <!-- <h3>Filtra la CR per Banche (se non viene selezionata nessuna banca, verranno analizzati tutti gli intermediari)</h3>
                                                @for($i = 0; $i < count($banks); $i++)
                                                <div class="row">
                                                    <label style="font-weight: bold" for="banks[{{$i}}]">  <input type="checkbox" id="banks[{{$i}}]" name="banks[{{$i}}]" value="{{$banks[$i]['nome_banca']}}">  {{$banks[$i]['nome_banca']}}</label>
                                                </div>
                                                @endfor -->

                                            </x-slot>

                                            <x-slot name="footer">
                                                <button class="btn btn-outline-success float-right" type="submit">Visualizza Analisi</button>
                                            </x-slot>
                                        </x-backend.card>
                                    @else
                                        <div class="alert alert-danger" role="alert"><button class="close" data-dismiss="alert" aria-hidden="true">×</button><i class="fa fa-frown-o mr-2" aria-hidden="true"></i>Nessun dato disponibile, importa una centrale rischi per poter procedere.</div>
                                    @endif
                                </x-forms.post>
                            @endif
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="newcr">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importa Centrale Rischi da PDF</h5>
                    <button class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <x-forms.post :action="route('admin.cr.centralerischi.recap')" enctype="multipart/form-data">
                        <x-backend.card>


                            <x-slot name="headerActions">

                            </x-slot>

                            <x-slot name="body">


                                <!--   <div class="form-group {{ $errors->has('account_id') ? 'has-error' : '' }}">
                                        <label for="account_id" class="col-md-2 control-label">Account</label>
                                        <div class="col-md-10">
                                            <select class="form-control" id="account_id" name="account_id" required="true">
                                                <option value="" style="display: none;" disabled selected>Select account</option>
                                                <option value="1">
                                                    Test
                                                </option>
                                            </select>

                                            {!! $errors->first('account_id', '<p class="help-block">:message</p>') !!}
                                        </div>
                                    </div>-->
                                <div class="form-group row">
                                    <label for="name" class="col-md-4 col-form-label">@lang('Centrale Rischi PDF')</label>
                                    <div class="flex flex-col justify-around h-full">
                                        <div class="col-md-10">
                                            <input type="file" accept="application/pdf" file name="centrale_rischi" required />
                                        </div>
                                    </div>
                                </div>
                                <!--form-group-->

                            </x-slot>

                            <x-slot name="footer">
                                <button class="btn btn-sm btn-primary float-right" type="submit">Importa PDF</button>
                            </x-slot>
                        </x-backend.card>
                    </x-forms.post>
                </div>

            </div>
        </div>
    </div>

     <!-- Intro JS -->
  <link href="https://unpkg.com/intro.js/minified/introjs.min.css"  type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/4.3.0/introjs-rtl.min.css" integrity="sha512-VwsKKwi99ZnRScgAkJ+ISGNolfoq+ic/mzJfhZWQ1xwfcbLZzLnHDoERYEppL25Okf+wEI/nDhHogudTa/YkWA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/4.3.0/introjs.min.css" integrity="sha512-YZO1kAqr8VPYJMaOgT4ZAIP4OeCuAWoZqgdvVYjeqyfieNWrUTzZrrxpgAdDrS7nV3sAVTKdP6MSKhqaMU5Q4g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://unpkg.com/intro.js/themes/introjs-modern.css" rel="stylesheet">
      <!-- Intro JS -->
  <script type="text/javascript" src="https://unpkg.com/intro.js/minified/intro.min.js"></script>

    <script>
    introJs().setOptions({
      showProgress: true,
      scrollToElement: false,
      exitOnOverlayClick: false,
      doneLabel: "Next page",
      showStepNumbers: true,
      steps: [{
          intro: "Benvenuto!"
        },
        {
          element: document.querySelector('.introduction-farm-allcr'),
          intro: "Qui vedrai tutte le cr",

        },

      ]
    }).start().oncomplete(function() {
      window.location.href = '../allerta/select';

    }).stop();

    var modal = document.getElementById('modal');
    $('[data-toggle="tooltip"]').tooltip();
  </script> 



    @endsection
