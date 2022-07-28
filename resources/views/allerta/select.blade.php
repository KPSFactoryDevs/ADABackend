@extends('backend.layouts.app')

@section('content')
    <div class="page">
        <div class="page-main">
            <div class="main-content">
                <div class="container-fluid">
                    <div class="text-center mt-5">
                        <div class="page-leftheader">
                            <h4 class="page-title">Sistema Allerta - Seleziona Bilancio |<span class="font-weight-normal text-muted ml-2" style="color: red!important;">Key Performance Softwares S.r.l.</span></h4>
                        </div>
                        <!-- <div class="page-header d-xl-flex d-block">
                            <div class="page-rightheader ml-md-auto">
                                <div class="d-flex align-items-end flex-wrap my-auto right-content breadcrumb-right">
                                </div>
                            </div>
                        </div> -->
                    </div>
    <div class="row mt-5">
    <div class="col-12">

        <div class="card">
            <div class="card-header  border-0 responsive-header">
                <h4 class="card-title">Seleziona il bilancio da considerare per l'analisi del Sistema Allerta</h4>
            </div>
            <div class="card-body">
            <a class='introduction-farm-allerta' data-step='6' data-intro='Tutte le cr!' >
                <div class="table-responsive">
                    <table class="table table-borderless" id="responsive-datatable">
                        <thead>
                        <tr>
                            <th class="wd-15p border-bottom-0">Azienda</th>
                            <th class="wd-20p border-bottom-0">Tipologia</th>
                            <th class="wd-15p border-bottom-0">Ultimo Periodo</th>
                            <th class="wd-15p border-bottom-0">Periodo Precedente</th>
                            <th class="wd-25p border-bottom-0">Seleziona</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($bilancis as $bilancio)

                        <tr>
                            <td><?php $anagrafica = json_decode($bilancio['json_data_anag']); echo ($anagrafica->DatiAnagraficiDenominazione) ?></td>
                            <td>{{ $bilancio->tipo_azienda }}</td>
                            <td>{{ $bilancio->year }}</td>
                            <td>{{ $bilancio->prev_year }}</td>

                            <td>
                                <div class="text-center">
                                    <a href="{{ route('admin.allerta.general', $bilancio->id ) }}" class="action-btns1" data-placement="top"> <button class="btn btn-outline-success">Analizza</button></a>
                                </div>
                            </td>
                        </tr>

                        @endforeach
                        </tbody>
                    </table>
                </div>
                </a>
            </div>
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

                            <th class="wd-15p border-bottom-0">Periodo considerato</th>
                            <th class="wd-15p border-bottom-0">Nome azienda</th>
                            <th class="wd-20p border-bottom-0">Tipo Azienda</th>
                            <th class="wd-25p border-bottom-0">Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($bilanciProvvisori as $bilancio)

                        <tr>

                            <td>{{ $bilancio->year }}</td>
                            <td><?php $anagrafica = json_decode($bilancio['json_data_anag']); echo ($anagrafica->DatiAnagraficiDenominazione) ?></td>
                            <td>{{ $bilancio->tipo_azienda }}</td>
                            <td class="text-right">
                                <div class="d-flex">
                                    <a href="{{ route('admin.allerta.general', $bilancio->id ) }}" class="action-btns1" data-placement="top"><i class="fe fe-file-text primary text-primary"></i></a>
                                </div>
                            </td>
                        </tr>

                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
        <!--/div-->
        <!--div-->

    </div>
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
          element: document.querySelector('.introduction-farm-allerta'),
          intro: "Qui puoi analizzare i bilanci",

        },
      ]
    }).start().oncomplete(function() {
      window.location.href = '../faq';

    }).stop();
  </script> 


@endsection
