@extends('backend.layouts.app')

@section('content')
    <div class="page">
        <div class="page-main">
            <div class="main-content">
                <div class="container">
                    <div class="page-header d-xl-flex d-block">
                        <div class="page-leftheader">
                            <h4 class="page-title">Sistema Allerta | <span class="font-weight-normal text-muted ml-2">Key Performance Softwares S.r.l.</span></h4>
                        </div>
                        <div class="page-header d-xl-flex d-block">
                            <div class="page-rightheader ml-md-auto">
                                <div class="d-flex align-items-end flex-wrap my-auto right-content breadcrumb-right">
                                    <div class="btn-list">
                                      <!--  <button  class="btn btn-primary " data-toggle="modal" data-target="#newbilancio"><i class="feather feather-plus fs-15 my-auto mr-2"></i>Import XBRL</button>
                                        <button  class="btn btn-primary " data-toggle="modal" data-target="#newcr"><i class="feather feather-plus fs-15 my-auto mr-2"></i>Inserimento Manuale</button>
                                  -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>




                    <div class="row ">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header border-bottom-0">
                                    <div class="card-title">
                                        Basic Content Wizard
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="wizard1" role="application" class="wizard clearfix">
                                        <div class="steps clearfix">
                                            <ul role="tablist">
                                                <li role="tab" class="first current" aria-disabled="false" aria-selected="true">
                                                    <a id="wizard1-t-0" href="#wizard1-h-0" aria-controls="wizard1-p-0">
                                                        <span class="number">1</span>
                                                        <span class="title">Seleziona Bilancio</span>
                                                    </a>
                                                </li>
                                                <li role="tab" class="disabled" aria-disabled="false" aria-selected="false">
                                                    <a id="wizard1-t-1" href="#wizard1-h-1" aria-controls="wizard1-p-1">
                                                        <span class="number">2</span>
                                                        <span class="title">Seleziona Centrale Rischi</span>
                                                    </a>
                                                </li>
                                                <li role="tab" class="last disabled" aria-disabled="false" aria-selected="false">
                                                    <a id="wizard1-t-2" href="#wizard1-h-2" aria-controls="wizard1-p-2">
                                                        <span class="current-info audible">current step: </span>
                                                        <span class="number">3</span>
                                                        <span class="title">Risultato</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="content clearfix">

                                            <h3 id="wizard1-h-0" tabindex="-1" class="title current">Seleziona Bilancio</h3>
                                            <section id="wizard1-p-0" role="tabpanel" aria-labelledby="wizard1-h-0" class="body current" aria-hidden="true" >
                                                <div class="table-responsive">
                                                    <table class="table table-bordered text-nowrap border-bottom" id="responsive-datatable">
                                                        <thead>
                                                        <tr>
                                                            <th class="wd-15p border-bottom-0"></th>
                                                            <th class="wd-15p border-bottom-0">Anno</th>
                                                            <th class="wd-20p border-bottom-0">Tipologia</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <tr>
                                                            <td>
                                                                <div class="custom-controls-stacked">
                                                                    <label class="custom-control custom-checkbox">
                                                                        <input type="checkbox" class="custom-control-input" name="example-checkbox1" value="option1" >
                                                                        <span class="custom-control-label"></span>
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>2021</td>
                                                            <td>Ordinario</td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="custom-controls-stacked">
                                                                    <label class="custom-control custom-checkbox">
                                                                        <input type="checkbox" class="custom-control-input" name="example-checkbox1" value="option1" >
                                                                        <span class="custom-control-label"></span>
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>2020</td>
                                                            <td>Ordinario</td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="custom-controls-stacked">
                                                                    <label class="custom-control custom-checkbox">
                                                                        <input type="checkbox" class="custom-control-input" name="example-checkbox1" value="option1">
                                                                        <span class="custom-control-label"></span>
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>2019</td>
                                                            <td>Ordinario</td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="custom-controls-stacked">
                                                                    <label class="custom-control custom-checkbox">
                                                                        <input type="checkbox" class="custom-control-input" name="example-checkbox1" value="option1">
                                                                        <span class="custom-control-label"></span>
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>2018</td>
                                                            <td>Ordinario</td>
                                                        </tr>

                                                        </tbody>
                                                    </table>
                                                </div>



                                            </section>
                                            <h3 id="wizard1-h-1" tabindex="-1" class="title">Seleziona Centrale Rischi</h3>
                                            <section id="wizard1-p-1" role="tabpanel" aria-labelledby="wizard1-h-1" class="body" aria-hidden="true" style="display: none;">
                                                Seleziona CR
                                            </section>
                                            <h3 id="wizard1-h-2" tabindex="-1" class="title ">Payment Details</h3>
                                            <section id="wizard1-p-2" role="tabpanel" aria-labelledby="wizard1-h-2" class="body" aria-hidden="false" style="display: none;">
                                                terza sezione
                                            </section>
                                        </div>

                                        <div class="actions clearfix"><ul role="menu" aria-label="Pagination"><li class="disabled" aria-disabled="true"><a href="#previous" role="menuitem">Previous</a></li><li aria-hidden="false" aria-disabled="false"><a href="#next" role="menuitem">Next</a></li><li aria-hidden="true" style="display: none;"><a href="#finish" role="menuitem">Finish</a></li></ul></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>


@endsection
