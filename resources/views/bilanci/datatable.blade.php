@extends('backend.layouts.app')

@section('content')
    <div class="page">
        <div class="page-main">
            <div class="main-content">
                <div class="container">
                    <div class="text-center mt-5">
                        <div class="page-leftheader">
                            <h4 class="page-title">Lista Bilanci |<span class="font-weight-normal text-muted ml-2" style="color: red!important;">Key Performance Softwares S.r.l.</span></h4>
                        </div>
                        </div>
                        <div class="page-header d-xl-flex d-block">
                            <div class="page-leftheader">
                                <div class="d-flex align-items-end flex-wrap my-auto right-content breadcrumb-right">
                                    <div class="btn-list">
                                        <a  class="btn btn-outline-primary importxbrl" data-trigger="hover" data-intro="lorem ipsum" data-toggle="tooltip" title="Con questo tasto puoi caricare il tuo bilancio in formato .xbrl"><span data-toggle="modal" data-target="#newbilancio"><i class="feather feather-plus fs-15 my-auto mr-2"></i>Importa Bilancio XBRL</span></a>
                                        <a data-toggle="tooltip" title="Con questo tasto puoi procedere all'inserimento dei dati di bilancio provvisorio" href="{{ route('admin.bilanci.bilancio.provvisorio')}}" class="btn btn-outline-dark"><i class="feather feather-plus fs-15 my-auto mr-2"></i>Inserimento Bilancio Provvisorio</a>
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
                                    Questa sezione è dedicata al caricamento del bilancio aziendale in formato .xbrl, necessario al fine di permettere al sistema di effettuare le elaborazioni dei dati. E’ importante confermare i dati estratti dal sistema e popolare manualmente la maschera di inserimento voci mancanti (qualora questa dovesse apparire dopo l’upload del file .xbrl contenente il bilancio) prima di cliccare sul tasto “Importa bilancio”. Il sistema prevede la possibilità di caricare il bilancio depositato in formato .xbrl; suggeriamo innanzitutto di effettuare il caricamento dell’ultimo bilancio depositato. E’ possibile analizzare anche il bilancio provvisorio, in questo caso l’utente dovrà inserire manualmente le voci di bilancio richieste inserendo i dati all’interno delle apposite caselle
                                    </div>
                                </div>
                            </div>
                            </a>
                        </div>
                    </div>

    <div class="row">
    <div class="col-12">
    <div class="card">
    <a class='introduction-farm-bilanci' data-step='8'>
            <div class="card-header  border-0 responsive-header">
                <h4 class="card-title">Bilanci Standard</h4>
            </div>
            <div class="card-body">
                <!-- <p></p>     -->
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
                        @foreach($bilancis as $bilancio)

                        <tr>

                            <td> {{ $bilancio->year}} </td>
                            <td>{{ $bilancio->prev_year }}</td>
                            <td><?php $anagrafica = json_decode($bilancio['json_data_anag']); echo ($anagrafica->DatiAnagraficiDenominazione) ?></td>
                            <td>{{ $bilancio->tipo_azienda }}</td>
                            <td class="text-right">
                                <div class="d-flex">
                                    <a href="{{ route('admin.analysis.bilanci.showCurrent', $bilancio->id ) }}" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Visualizza Bilancio"><i class="fe fe-eye primary text-primary"></i></a>
                                    <a href="{{ route('admin.analysis.bilanci.showAnalisys', $bilancio->id ) }}" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Visualizza Analisi"><i class="fe fe-bar-chart-2 primary text-primary"></i></a>
                                    <a href="{{ route('admin.bilanci.bilancio.delete', $bilancio->id ) }}" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Elimina Bilancio"><i class="fe fe-trash primary text-primary"></i></a>

                                </div>
                            </td>
                        </tr>

                        @endforeach
                        </tbody>
                    </table>
                </div> 
            </div>
            </a>
        </div>
        @if(count($bilanciProvvisori) > 0)
        <div class="card">
            <div class="card-header  border-0 responsive-header">
                <h4 class="card-title">Bilanci provvisori</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap border-bottom" id="responsive-datatable">
                        <thead>
                        <tr>
                            <th class="wd-15p border-bottom-0">Periodo Considerato</th>
                            <th class="wd-15p border-bottom-0">Nome azienda</th>
                            <th class="wd-20p border-bottom-0">Tipo Azienda</th>
                            <th class="wd-25p border-bottom-0">Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($bilanciProvvisori as $bilancio)

                        <tr>

                            @php
                            // dd($bilancio);
                            @endphp

                            <td>{{ $bilancio->year }}</td>
                            <td><?php $anagrafica = json_decode($bilancio['json_data_anag']); echo ($anagrafica->DatiAnagraficiDenominazione) ?></td>
                            <td>{{ $bilancio->tipo_azienda }}</td>
                            <td class="text-right">
                                <div class="d-flex">
                                    <a href="{{ route('admin.analysis.bilanci.showAnalisys', $bilancio->id ) }}" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Visualizza Analisi"><i class="fe fe-bar-chart-2 primary text-primary"></i></a>
                                    <a href="{{ route('admin.bilanci.bilancio.delete', $bilancio->id ) }}" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Elimina Bilancio"><i class="fe fe-trash primary text-primary"></i></a>
                                    <a href="{{ route('admin.analysis.bilanci.showCurrent', $bilancio->id ) }}" class="action-btns1" data-toggle="tooltip" data-placement="top" title="" data-original-title="Visualizza Bilancio"><i class="fe fe-eye primary text-primary"></i></a>
                                </div>
                            </td>
                        </tr>

                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--/div-->
        <!--div-->
@endif
    </div>
    </div>
                </div>
            </div>
        </div>


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



  <script type="text/javascript" src="js/intro.js"></script>
    <script type="text/javascript">
      if (RegExp('multipage', 'gi').test(window.location.search)) {
        introJs().setOptions({
            showProgress: true,
            scrollToElement: true,
            positionPrecedence: ["right", "left"],
            exitOnOverlayClick: false,
            doneLabel: "Prossima pagina",
            nextLabel: "Prossimo",
            prevLabel: "Indietro",
            skipLabel : 'Salta' ,
            hidePrev: true,
            showStepNumbers: false,
            showBullets: false,
            steps: [
                {
                    intro: "Pagina bilanci",
                },
                {
                element: document.querySelector('.importxbrl'),
                intro: "Lorem ipsum",
            },
            ]
        }).start();
      }

      </script>

@endsection
