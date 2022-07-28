@extends('backend.layouts.app')


@section('content')


<div style=" " class="container-fluid">

  <div class="text-center mt-5">
    <h1>BENVENUTO SU <span style="font-size:48px; color:#700d11;" class="">B</span>FINTECH
    </h1>
    <!-- <button style="border-radius:14px;" class="btn btn-outline-success pull-right">Carica file</button> -->
    <p style="color:black;padding-bottom: 7px;">Carica il tuoi file per iniziare ad analizzare l’immagine finanziaria della tua azienda.</p>
  </div>
  <div style="" class="text-center">
    <!-- START MODAL -->

 <!--   <button type="button" class="btn btn-outline-warning" data-toggle="modal" data-target="#exampleModalCenter">Carica file</button>-->


    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <div class="d-flex p-2">
              <th>
                <div class="card mr-5 mt-3" style="width: 18rem;">
                  <img class="card-img-top" src="https://img.icons8.com/external-ddara-flat-ddara/10000/000000/external-analysis-fintech-ddara-flat-ddara-2.png" alt="Card image cap">
                  <div class="card-body">
                    <h5 class="card-title">Import XBRL</h5>
                    <p class="card-text">Carica il tuo Bilancio in formato .xbrl</p>
                    <button data-toggle="modal" data-target="#newbilancio" class="btn btn-outline-danger">Importa</butt>
                  </div>
                </div>
              </th>
              <th>
                <div class="card mr-5 mt-3" style="width: 18rem;">
                  <img class="card-img-top" src="https://img.icons8.com/external-ddara-flat-ddara/10000/000000/external-bank-fintech-ddara-flat-ddara.png" alt="Card image cap">
                  <div class="card-body">
                    <h5 class="card-title">Import CR</h5>
                    <p class="card-text">Carica la tua Centrale Rischi in formato .pdf</p>
                    <button data-toggle="modal" data-target="#newcr" class="btn btn-outline-danger">Importa</button>
                  </div>
                </div>
              </th>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- END MODAL -->
  </div>
</div>



<div class="container mt-5">
    <div class="row">
        <div class="col">
            <div class="innerBox text-center">
                <i class="fa fa-globe"></i>
                <p>Panoramica</p>
            </div>
        </div>
        <div class="col">
            <div class="innerBox text-center">
                <i class="fa fa-upload"></i>
                <p>Importa Bilancio</p>
            </div>
        </div>
        <div class="col">
            <div class="innerBox text-center">
                <i class="fa fa-chart-area"></i>
                <p>Analizza Bilancio</p>
            </div>
        </div>
        <div class="col">
            <div class="innerBox text-center">
                <i class="fa fa-chart-pie"></i>
                <p>Analizza Centrale Rischi</p>
            </div>
        </div>
        <div class="col">
            <div class="innerBox text-center">
                <i class="fa fa-bell"></i>
                <p>Sistema Allerta</p>
            </div>
        </div>
    </div>
</div>



<div class="card w-75 mt-5 container" style=" border-radius: 15px;">
    <div class="card-body">
        <h5 class="card-title"><b>COME FUNZIONA <span class="kps">B</span> FINTECH</b> </h5>
        <p class="text-left">Grazie a questa Suite puoi valutare l’immagine finanziaria e le performance aziendali, prevenire segnali di crisi e individuare le aree di intervento migliorando il tuo rating. Inoltre permetterai alla tua azienda di assolvere agli obblighi di creazione di un sistema di monitoraggio aziendale dettati dal <b>Codice della Crisi di Impresa</b> (Legge 155/2017) <br>
            Basterà caricare il bilancio aziendale in formato .xbrl (o inserire manualmente i dati nel caso dell’analisi di un bilancio provvisorio) caricare il file .pdf contenente le ultime rilevazioni presenti in Centrale Rischi e rispondere a una serie di semplici domande per ottenere in tempo reale una serie di report dettagliati e di semplice comprensione.<br>
        </p>
        <footer class="blockquote-footer mt-2">Ti consigliamo di scaricare e conservare i report ai fini di eventuali controlli dell’osservanza degli obblighi di legge.</footer>
        <hr>
        <h5 class="card-title"><b>IL SISTEMA É IN GRADO DI ELABORARE:</b></h5><br>
        <p>
            - Report basic necessari per assolvere agli obblighi del D. Lgs 14/2019 (legge 155).<br>
            - Report advanced importanti per esaminare la salute aziendale ed individuare le aree di intervento.<br>
            - Report CR sintetica e CR dettagliata fondamentali per comprendere come le banche valutino l’azienda dall’esterno, implementare strategie per diminuire i costi finanziari e/o aumentare la possibilità di accedere al credito, identificare eventuali errate segnalazioni in centrale rischi e rettificare la propria posizione.<br>
        </p>
        <!-- <a href="#" class="btn btn-primary">Button</a> -->
        <footer class="blockquote-footer mt-2">N:B. Nel caso di problemi tecnici puoi contattare il team <a class="kps" href="">B Fintech</a> che ti fornirà la corretta assistenza.</footer>

    </div>
</div>


<div class="container mt-5">
    <div class="row">
        <div class="col-6"></div>
        <div class="col-6">
            <div class="row">
                <div class="col">
                    <div class="innerBox text-center">
                        <i class="fa fa-question-circle"></i>
                        <p>FAQ</p>
                    </div>
                </div>
                <div class="col">
                    <div class="innerBox text-center">
                        <i class="fa fa-chart-area"></i>
                        <p>Guide e assistenza</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!--
  <div class="card w-75 mt-5 container" style="m  border-radius: 15px;">
    <div class="card-body" data-step="4" data-intro="Bilanci Analizzati!">
      <div class="card-title  border-0 responsive-header">
        <h5 class="card-title">Bilanci Standard</h5>
      </div>
      <div class="card-body">

        <div class="table-responsive">
          <table class="table table-borderless" id="responsive-datatable">
            <thead>
              <tr>

                <th class="wd-15p border-bottom-0">Ultimo Anno</th>
                <th class="wd-15p border-bottom-0">Anno Precedente</th>
                <th class="wd-15p border-bottom-0">Nome azienda</th>
                <th class="wd-20p border-bottom-0">Tipo Azienda</th>
                <th class="wd-25p border-bottom-0">Azioni</th>
              </tr>
            </thead>
            <tbody>

              <tr>

                <td> 2020-01-01 2020-12-31 </td>
                <td>2019-01-01 2019-12-31</td>
                <td>SEAB INSTRUMENTS SRL</td>
                <td>COMM INGROSSO e DETT AUTOVEICOLI, COMM INGROSSO, DISTRIB. ENERGIA/GAS</td>
                <td class="text-right">
                  <div class="d-flex">
                    <a href="http://127.0.0.1:8000/admin/analysis/bilanci/showCurrent/86" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Visualizza Bilancio" data-bs-original-title=""><i class="fe fe-eye primary text-primary"></i></a>
                    <a href="http://127.0.0.1:8000/admin/analysis/bilanci/showAnalisys/86" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Visualizza Analisi" data-bs-original-title=""><i class="fe fe-bar-chart-2 primary text-primary"></i></a>
                    <a href="http://127.0.0.1:8000/admin/bilanci/delete/86" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Elimina Bilancio" data-bs-original-title=""><i class="fe fe-trash primary text-primary"></i></a>

                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

-->

<div class="modal fade"  id="newbilancio">
            <div class="modal-dialog modal-lg" role="">
                <x-forms.post :action="route('admin.analysis.bilanci.recap')" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <button  class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label class="form-label">Seleziona XBRL</label>
                                    <input id="demoz" type="file"  name="xbrl" />
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="form-group mb-0">
                                <div class="col-md-12">
                                    <label for="tipo_azienda" class="form-label">Tipo Azienda</label>
                                    <select class="form-control" id="tipo_azienda" name="tipo_azienda" required="true">
                                        <option value="" style="display: none;" disabled selected>Seleziona il tipo di azienda</option>
                                        @foreach($tipiAziende as $tipo)
                                            <option value="{{$tipo}}">
                                                {{$tipo}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group mb-0">
                                <div class="col-md-12">
                                    <label for="forma_giuridica" class="form-label">Forma Giuridica</label>
                                    <select class="form-control" id="forma_giuridica" name="forma_giuridica" required="true">
                                        <option value="" style="display: none;" disabled selected>Seleziona la forma giuridica</option>
                                        @foreach($formaGiuridica as $forma)
                                            <option value="{{$forma}}">
                                                {{$forma}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button  class="btn btn-outline-primary" data-dismiss="modal">Chiudi</button>
                        <button  type="submit" class="btn btn-success">Importa</button>
                    </div>
                </div>
                </x-forms.post>
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

    <style>
        .innerBox {
            padding-top:15px;
            background:white;
            height:200px;
            border-radius:5px;
            cursor:pointer;
        }

        .innerBox:hover {
            background:lightgrey;
        }
        .innerBox i {
            font-size:140px;
            color:lightblue;

        }
        .innerBox:hover i {
        color:white;
        }
        .innerBox p {
            font-size:20px;
            font-weight:bold;
            font-family:'Roboto';
        }


    </style>

@endsection
