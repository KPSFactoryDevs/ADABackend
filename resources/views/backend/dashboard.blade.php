@extends('backend.layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="page">
    <div class="page-main">
        <div class="main-content">
            <div class="container">
                <div class="page-header d-xl-flex d-block">
                    <div class="page-leftheader">
                        <h4 class="page-title">La mia Azienda |<span class="font-weight-normal text-muted ml-2" style="color: red!important;">Key Performance Softwares S.r.l.</span></h4>

                    </div>
                    <div class="page-header d-xl-flex d-block">
                        <div class="page-rightheader ml-md-auto">
                            <div class="d-flex align-items-end flex-wrap my-auto right-content breadcrumb-right">
                                <div class="btn-list">
      <!-- <button class="btn btn-outline-secondary importXbrl" data-intro='ciao1' data-toggle="modal" data-target="#newbilancio"><i class="feather feather-plus fs-15 my-auto mr-2"></i>Import XBRL</button> -->
                                    <button class="btn btn-primary importCr" data-intro='ciao2' data-toggle="modal" data-target="#newcr"><i class="feather feather-plus fs-15 my-auto mr-2"></i>Importa Bilancio</button>
                                    <button class="btn btn-primary importCr" data-intro='ciao2' data-toggle="modal" data-target="#newcr"><i class="feather feather-plus fs-15 my-auto mr-2"></i>Importa PDF Centrale Rischi</button>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12">
                        <div class="row">
                            <div class="col-xl-4 col-lg-4 col-md-12">
                                    <div class="card">
                                        <div class="card-body introduction-farm" data-intro="lorem ipsum" data-position='top' data-step="2">
                                            <div class="row">
                                                <div class="col-9">
                                                    <div class="mt-0 text-left">
                                                        <a class="fs-16 font-weight-semibold">Scoring ultimo bilancio disponibile</a>
                                                        <h3 class="mb-0 mt-1 text-success fs-25">Bilancio</h3>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="icon1 bg-primary-transparent my-auto  float-right"> 8/10</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12">
                                <div class="card">
                                        <div class="card-body introduction-farm2" data-intro="lorem ipsum" data-position='top' data-step="3">
                                            <div class="row">
                                                <div class="col-9">
                                                    <div class="mt-0 text-left">
                                                        <a class="fs-16 font-weight-semibold">Scoring centrale rischi banca d'italia</a>
                                                        <h3 class="mb-0 mt-1 text-success fs-25">Centrale Rischi</h3>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="icon1 bg-primary-transparent my-auto  float-right">8/10</div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12">
                                <div class="card">
                                        <div class="card-body introduction-farm3" data-intro="lorem ipsum" data-position='top' data-step="4">
                                            <div class="row">
                                                <div class="col-9">
                                                    <div class="mt-0 text-left">
                                                        <a class="fs-16 font-weight-semibold">Scoring generale sistema allerta</a>
                                                        <h3 class="mb-0 mt-1 text-success fs-25">Allerta</h3>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="icon1 bg-primary-transparent my-auto  float-right"> 8/10 </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-8 col-lg-12 col-md-12">

                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-12 col-md-12">

                        </div>
                    </div>
                    <!-- End Row -->

                    <!--Row-->

                    <!-- End Row-->



                </div>

                <div class="row">
                    <!--<div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card">
                            <div class="card-header  border-0 responsive-header">
                                <h4 class="card-title">Andamento del Fatturato</h4>
                                <div class="card-options">
                                    <div class="btn-list">
                                        <a href="#" class="btn ripple btn-outline-light text-dark float-left mr-4 d-flex my-auto"><span class="dot-label bg-primary mr-2 my-auto"></span>Spese</a>
                                        <a href="#" class="btn ripple btn-outline-light text-dark float-left mr-4 d-flex my-auto"><span class="dot-label bg-light4 mr-2 my-auto"></span>Progetti</a>
                                        <a href="#" class="btn ripple btn-outline-light text-dark float-left mr-4 d-flex my-auto" data-toggle="dropdown" aria-expanded="false"> Anno <i class="feather feather-chevron-down"></i> </a>
                                        <ul class="dropdown-menu" role="menu">
                                            <li><a href="#">Annuale</a></li>
                                            <li><a href="#">Mensile</a></li>
                                            <li><a href="#">Settimanale</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div style="padding-bottom: 63px;"class="chart-wrapper">
                                    <div class="h-300" id="flotPie2"></div>
                                </div>
                            </div>
                        </div>

                    </div>-->
                    <div class="col-12">
                        <div class="card overflow-hidden">
                            <div class="card-header border-0">
                                <h4 class="card-title">Bilanci Disponibili</h4>
                                <div class="card-options pr-3">
                                    <div class="dropdown">
                                        <a href="{{route('admin.bilanci.bilancio.datatable')}}">
                                            <button class="btn btn-outline-primary">Vedi tutti</button></a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0 pt-4">
                                <div class="table-responsive">
                                    <table class="table table-vcenter text-nowrap border-top mb-0 invoice-table">
                                        <thead>
                                            <tr>

                                                <th class="wd-10p border-bottom-0">Azienda</th>
                                                <th class="wd-10p border-bottom-0">Anno</th>
                                                <th class="wd-15p border-bottom-0">Forma Giuridica</th>

                                                <th class="wd-15p border-bottom-0">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(count($bilancis) > 0)

                                            @foreach($bilancis as $bilancio)
                                            <tr class="border-bottom">
                                                <td>
                                                    <div class="d-flex">

                                                        <div class=" d-block mt-0 mt-sm-1">
                                                            <h6 class="mb-0 fs-14 font-weight-semibold"> Key Performance Softwares S.r.l. </h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex">

                                                        <div class=" d-block mt-0 mt-sm-1">
                                                            <?php
                                                            $bilancio->current_year = explode(' ', $bilancio->current_year);
                                                            $bilancio->current_year = substr($bilancio->current_year[0], 0, 4);?>

                                                            <h6 class="mb-0 fs-14 font-weight-semibold"> {{ $bilancio->current_year }}</h6>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="text-left">
                                                    <span class="">{{$bilancio->forma_giuridica}}</span>
                                                </td>

                                                <td class="text-left">
                                                    <div class="d-flex">
                                                        <a href="{{ route('admin.analysis.bilanci.showCurrent', $bilancio->id ) }}" class="action-btns1" ><i class="fe fe-eye primary text-primary"></i></a>
                                                        <a href="{{ route('admin.analysis.bilanci.showAnalisys', $bilancio->id ) }}" class="action-btns1" ><i class="fa fa-chart-area primary text-primary"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                            @else
                                            <div class="alert alert-danger" style="margin: 0px 25px;" role="alert"><button class="close" data-dismiss="alert" aria-hidden="true">×</button><i class="fa fa-frown-o mr-2" aria-hidden="true"></i>Nessun dato disponibile, importa una centrale rischi per poter procedere.</div>

                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <x-forms.post :action="route('admin.cr.centralerischi.andamentale')" enctype="multipart/form-data">
                            <div class="card">
                                <div class="card-header border-0">
                                    <h4 class="card-title">Centrale Rischi</h4>

                                    <div class="card-options pr-3">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary float-right" type="submit">Visualizza Centrale Rischi</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        @if(isset($crData))
                                        <x-forms.post :action="route('admin.cr.centralerischi.andamentale')" enctype="multipart/form-data">
                                            @if(count($periods) > 0)


                                            <ul style="padding:0;margin:0;">
                                                <li><input type="radio" id="lastYear" name="period" required value="1"> <b><label for="lastYear">Ultimo anno</label></b> (<span>{{$lastYear->format('d-m-Y').' - '.$lastDate->format('d-m-Y')}})</span></li>
                                                <li><input type="radio" id="lastTwoYears" name="period" required value="2"> <b><label for="lastTwoYears">Ultimi due anni</label></b> (<span>{{$lastTwoYears->format('d-m-Y').' - '.$lastDate->format('d-m-Y')}})</span></li>
                                                <li><input type="radio" id="lastThreeYears" required name="period" value="3"> <b><label for="lastThreeYears"> Ultimi tre anni</label></b> (<span>{{$lastThreeYears->format('d-m-Y').' - '.$lastDate->format('d-m-Y')}})</span></li>
                                                <li><input type="radio" id="allYears" required name="period" value="0"> <b><label for="allYears"> Tutti gli anni</label></b> (<span>{{$earliestDate->format('d-m-Y').' - '.$lastDate->format('d-m-Y')}}</span>)</li>
                                            </ul>



                                            @else
                                            <div class="alert alert-danger" role="alert"><button class="close" data-dismiss="alert" aria-hidden="true">×</button><i class="fa fa-frown-o mr-2" aria-hidden="true"></i>Nessun dato disponibile, importa una centrale rischi per poter procedere.</div>
                                            @endif
                                        </x-forms.post>
                                        @else
                                        <h4>Nessuna Centrale Rischi Disponibile</h4>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </x-forms.post>
                    </div>
                </div>
            </div><!-- end app-content-->
        </div>



        <!--Change password Modal -->
        <div class="modal fade" id="changepasswordnmodal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Change Password</h5>
                        <button class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" placeholder="password" value="">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" placeholder="password" value="">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-outline-primary" data-dismiss="modal">Close</a>
                        <a href="#" class="btn btn-primary">Confirm</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Change password Modal  -->

        <!-- New Project Modal -->
        <div class="modal fade" id="newbilancio">
            <div class="modal-dialog modal-lg" role="">
                <x-forms.post :action="route('admin.analysis.bilanci.recap')" enctype="multipart/form-data">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Importa una nuova CR</h5>
                            <button class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Seleziona XBRL</label>
                                        <input id="demoz" type="file" name="xbrl" />

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-primary" data-dismiss="modal">Chiudi</button>
                            <button data-toggle="tooltip" title="Con questo tasto procederai all'upload del file .xbrl che hai selezionato" type="submit" class="btn btn-success">Importa</button>
                        </div>
                    </div>
                </x-forms.post>
            </div>
        </div>

        <div class="modal fade" id="newcr">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Importa il file Centrale Rischi in formato PDF</h5>
                        <button class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <x-forms.post :action="route('admin.cr.centralerischi.recap')" enctype="multipart/form-data">
                        <x-backend.card>

                            <x-slot name="headerActions">

                            </x-slot>

                            <x-slot name="body">


                              <!--  <div class="form-group {{ $errors->has('account_id') ? 'has-error' : '' }}">
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

                                    <div class="flex flex-col justify-around h-full">
                                        <label for="name" class="col-form-label">@lang('Seleziona la Centrale Rischi in formato PDF')</label><br>

                                            <input type="file" name="centrale_rischi" required />

                                    </div>
                                </div>
                                <!--form-group-->

                            </x-slot>


                        </x-backend.card>

                    <div class="modal-footer">
                        <button class="btn btn btn-success float-right" type="submit">Importa Centrale Rischi</button>

                        <button class="btn btn-outline-primary" data-dismiss="modal">Chiudi</button>
                    </div>
                    </x-forms.post>
                </div>
            </div>
        </div>

        <!-- New Project Modal -->

    </div>

    <!-- Back to top -->
    <a href="#top" id="back-to-top"><span class="feather feather-chevrons-up"></span></a>

 <script>

// var intro = introJs();

//     intro.setOptions({
//       showProgress: true,
//       scrollToElement: true,
//       positionPrecedence: ["right", "left"],
//       exitOnOverlayClick: false,
//       doneLabel: "Prossima pagina",
//       nextLabel: "Prossimo",
//       prevLabel: "Indietro",
//       skipLabel : 'Salta' ,
//       hidePrev: true,
//       showStepNumbers: false,
//       showBullets: false,
//       steps: [
//         {
//           intro: "<span>Oggi per crescere ed accedere al credito è fondamentale dotarsi di strumenti capaci di valutare l’immagine finanziaria e performance aziendali, prevenire segnali di crisi e diminuire il costo del denaro.</span><br><span>L’andamentale ha un’influenza del 65% nel rating delle piccole imprese italiane che si rivolgono alle banche per ottenere finanziamenti e i dati quantitativi di bilancio hanno una influenza del 30%. Inoltre la Riforma della Crisi di Impresa obbliga dal 2019 tutte le imprese italiane a dotarsi di un sistema organizzativo volto a prevenire la crisi. KPS Fintech aiuta le aziende ad assolvere agli obblighi di legge e migliorare il proprio rating.</span><br><span>Cosa fa KPS Fintech?</span>",
//         },
//         {
//           element: document.querySelector('.panoramica'),
//           intro: "Lorem ipsum",

//         },
//         {
//           element: document.querySelector('.introduction-farm'),
//           intro: "Lorem ipsum",
//         },
//         {
//           element: document.querySelector('.introduction-farm2'),
//           intro: "Lorem ipsum",
//         },
//         {
//           element: document.querySelector('.introduction-farm3'),
//           intro: "Lorem ipsum",
//         },
//         {
//           element: document.querySelector('.importXbrl'),
//           intro: "Lorem ipsum",
//         },
//         {
//           element: document.querySelector('.importCr'),
//           intro: "Lorem ipsum",
//         },
//         {
//         element: document.querySelector('.introduction-bilanci'),
//         intro: "Lorem ipsum",
//       },
//      ]
//     });

//     intro.start().oncomplete(function() {
//       window.location.href = 'bilanci/allbilanci?multipage=true';

//     }).stop();

document.addEventListener("DOMContentLoaded", function() {

var hadTour = localStorage.getItem('hadTour');

if(!hadTour){
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
            title: "Benvenuto!",
            intro: "<span>Vuoi iniziare il tour?</span>",
            },
            {
            element: document.querySelector('.panoramica'),
            intro: "Lorem ipsum",

            },
            {
            element: document.querySelector('.introduction-farm'),
            intro: "Lorem ipsum",
            },
            {
            element: document.querySelector('.introduction-farm2'),
            intro: "Lorem ipsum",
            },
            {
            element: document.querySelector('.introduction-farm3'),
            intro: "Lorem ipsum",
            },
            {
            element: document.querySelector('.importXbrl'),
            intro: "Lorem ipsum",
            },
            {
            element: document.querySelector('.importCr'),
            intro: "Lorem ipsum",
            },
            {
            element: document.querySelector('.introduction-bilanci'),
            intro: "Lorem ipsum",
        },
        ]
        }).oncomplete(function() {
        window.location.href = 'bilanci/allbilanci?multipage=true';
        localStorage.setItem('hadTour', true);
        }).onexit(function(){
        localStorage.setItem('hadTour', true);
        }).start();
}

});

</script>
    @endsection
