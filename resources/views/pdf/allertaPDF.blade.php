<div class="panel panel-default">
    <div class="panel-body">
        <div class="">
            <h1>Analisi Sistema Allerta Fintech - Key Performances Software</h1>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade active show" id="pills-valutazionecentralerischi" role="tabpanel" aria-labelledby="pills-crscoring">
                    <dl class="dl-horizontal">
                        <div class="row">
                            <div class="card">
                                <div class="card-header  border-0 responsive-header">
                                    <h2 class="card-title">Scoring CR Andamentale</h2>
                                </div>
                                <div class="card-body">

                                    <table class="table table-hover table-light table-bordered">
                                        <tbody>
                                            <tr>
                                                <th>Valutazione negativa del CR Scoring che deriva dall'analisi sintetica della CR &nbsp;</th>
                                                <th style="width: 20%">
                                                    @if($alerts['1'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="card">
                                <div class="card-header  border-0 responsive-header">
                                    <h4 class="card-title">Sconfini e ritardi nei pagamenti</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-hover table-light table-bordered">
                                        <tbody>
                                            <tr>
                                                <th>Sconfini significativi e/o ripetuti nel corso degli ultimi 12 mesi &nbsp;</th>
                                                <th style="width: 20%">
                                                    @if($alerts['2'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>Mancato pagamento di finanziamenti o di altre scadenze &nbsp;</th>
                                                <th style="width: 20%">
                                                    @if($alerts['3'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="card">
                                <div class="card-header  border-0 responsive-header">
                                    <h4 class="card-title">Aumento delle garanzie</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-hover table-light table-bordered table-curved">
                                        <tbody>
                                            <tr>
                                                <th>Aumento delle richieste di garanzie su beni aziendali &nbsp;</th>
                                                <th style="width: 20%">
                                                    @if($alerts['4'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>Aumento delle garanzie concesse su esposizioni di altri soggetti &nbsp;</th>
                                                <th>
                                                    @if($alerts['5'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="card">
                                <div class="card-header  border-0 responsive-header">
                                    <h4 class="card-title">Insoluti portafoglio anticipi</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-hover table-light table-bordered">
                                        <tbody>
                                            <tr>
                                                <th>Aumento significativo o peso elevato di incidenza insoluti su anticipo crediti &nbsp;</th>
                                                <th style="width: 20%">
                                                    @if($alerts['6'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>

                        <div class="row">
                            <div class="card">
                                <div class="card-header  border-0 responsive-header">
                                    <h4 class="card-title">Aumento affidamenti e utilizzi</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-hover table-light table-bordered">
                                        <tbody>
                                            <tr>
                                                <th style="text-align: left">Aumento significativo delle richieste di affidamenti di cassa &nbsp;</th>
                                                <th style="width: 20%">
                                                    @if($alerts['7'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                            <tr>
                                                <th style="text-align: left">Richiesta finanziamenti straordinari &nbsp;</th>
                                                <th>
                                                    @if($alerts['8'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                            <tr>
                                                <th style="text-align: left">Crescita continua e rilevante di utilizzi per liquidità di cassa &nbsp;</th>
                                                <th>
                                                    @if($alerts['9'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                            <tr>
                                                <th style="text-align: left">Crescita continua e rilevante di utilizzi per smobilizzo crediti commerciali o tensione finanziaria &nbsp;</th>
                                                <th>
                                                    @if($alerts['10'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="card">
                                <div class="card-header  border-0 responsive-header">
                                    <h4 class="card-title">Rientro linee anticipi, cassa e firma</h4>
                                </div>
                                <div class="card-body">

                            <table class="table table-hover table-light table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="text-align: left">Rientri nelle linee di cassa &nbsp;</th>
                                        <th style="width: 20%">
                                            @if($alerts['11'])
                                            {{'Si'}}
                                            @else
                                            {{'No'}}
                                            @endif
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>Rientri nelle linee anticipi sbf/fatture &nbsp;</th>
                                        <th>
                                            @if($alerts['12'])
                                            {{'Si'}}
                                            @else
                                            {{'No'}}
                                            @endif
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>Rientri nelle linee di crediti per firma &nbsp;</th>
                                        <th>
                                            @if($alerts['13'])
                                            {{'Si'}}
                                            @else
                                            {{'No'}}
                                            @endif
                                        </th>
                                    </tr>
                                </tbody>
                            </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="card">
                                <div class="card-header  border-0 responsive-header">
                                    <h4 class="card-title">Segnalazioni pregiudizievoli</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-hover table-light table-bordered">

                                        <tbody>
                                            <tr>
                                                <th>Presenza sconfinamenti fra 90gg e 180gg oppure oltre i 180gg &nbsp;</th>
                                                <th style="width: 20%">
                                                    @if($alerts['14'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                            <tr>
                                                <th style="text-align: left">Presenza di garanzie attivate con esito negativo &nbsp;</th>
                                                <th>
                                                    @if($alerts['15'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                            <tr>
                                                <th style="text-align: left">Presenza di sofferenze o crediti passati a perdita &nbsp;</th>
                                                <th>
                                                    @if($alerts['16'])
                                                    {{'Si'}}
                                                    @else
                                                    {{'No'}}
                                                    @endif
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="card">
                                <div class="card-header  border-0 responsive-header">
                                    <h2 class="card-title">Valutazione macroarea centrale rischi</h2>
                                </div>
                                <div class="card-body">
                                    <h2> {{$punteggioCR*10}} </h2>
                                </div>
                            </div>
                        </div>

                    </dl>
                </div>
                <div class="tab-pane fade" id="pills-valutazionebilancio" role="tabpanel" aria-labelledby="pills-valutazionebilancio">
                    <div class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title"><h2>Indici di bilancio</h2></div>
                        </div>
                        <div class="card-body">

                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" id="basic-datatable">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Indice</th>
                                            <th class="text-center">Valore</th>
                                            <th class="text-center">Giudizio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($bilancioData['Giudizi']['Giudizi'] as $data=>$val)
                                        <tr>
                                            <td style="border: 1px solid black" class="text-center" >{{ $data }} &nbsp;</td>
                                            <td style="border: 1px solid black" class="text-center" >{{ $bilancioData['Indici'][str_replace(' ','_',$data)] }} &nbsp;</td>
                                            <td style="border: 1px solid black" class="text-center" >{{ $val['Giudizio'] }} &nbsp;</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer border-bottom-0">
                            <h3>Esito: {{$bilancioData['Giudizi']['Score']}} / 10</h3>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-questionarioqualitativo" role="tabpanel" aria-labelledby="pills-questionarioqualitativo">
                    <dl class="dl-horizontal">
                        <div class="row">

                            <x-forms.post :action="route('admin.allerta.questionario')" enctype="multipart/form-data">
                                <x-backend.card>
                                    <x-slot name="header"></x-slot>
                                    <x-slot name="body">
                                        <h2>Questionario AS IS</h2>
                                        <table class="table table-hover table-light table-bordered">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="3"><h3>Anomalie dei pagamenti verso controparti commerciali </h3></th>
                                                </tr>
                                                <tr>
                                                    <th scope="col">Parametro analizzato</th>
                                                    <th scope="col">Esito</th>
                                                    <th scope="col">Spiegazioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1) Sono presenti fatture e avvisi di pagamento di cui non si è rispettata la scadenza?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-1']['Result'] == 'Si')<label for="questionario3-1"><input type="radio" id="questionario3-1" required  name="questionario_3-1"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-1']['Result'] == 'No') <label for="questionario3-1"><input type="radio" id="questionario3-1" required  name="questionario_3-1"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_3-1_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['3-1']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2) Ci sono ritardi nei pagamenti ai fornitori superiori a 90 giorni per un ammontare superiore a quello dei debiti non scaduti?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-2']['Result'] == 'Si') <label for="questionario3-2"><input type="radio" id="questionario3-2" required  name="questionario_3-2"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-2']['Result'] == 'No') <label for="questionario3-2"><input type="radio" id="questionario3-2" required  name="questionario_3-2"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_3-2_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['3-2']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>3) I fornitori hanno modificato le condizioni di pagamento delle forniture (ad es. pagamento dilazionato a pagamento anticipato)?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-3']['Result'] == 'Si') <label for="questionario3-3"><input type="radio" id="questionario3-3" required  name="questionario_3-3"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-3']['Result'] == 'No') <label for="questionario3-3"><input type="radio" id="questionario3-3" required  name="questionario_3-3"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_3-3_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['3-3']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>4) Avete inoltrato richieste di rimodulazione delle scadenze nei pagamenti richiedendo di aumentare la dilazione?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-4']['Result'] == 'Si') <label for="questionario3-4"><input type="radio" id="questionario3-4" required  name="questionario_3-4"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-4']['Result'] == 'No') <label for="questionario3-4"><input type="radio" id="questionario3-4" required  name="questionario_3-4"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_3-4_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['3-4']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>5) Sono state modificate le condizioni di incasso da parte dei clienti con un conseguente allungamento dei tempi di incasso?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-5']['Result'] == 'Si') <label for="questionario3-5"><input type="radio" id="questionario3-5" required  name="questionario_3-5"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-5']['Result'] == 'No') <label for="questionario3-5"><input type="radio" id="questionario3-5" required  name="questionario_3-5"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_3-5_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['3-5']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>6) Sono stati registrati mancati incassi per importi considerevoli superiori a 90gg?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-6']['Result'] == 'Si') <label for="questionario3-6"><input type="radio" id="questionario3-6" required  name="questionario_3-6"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-6']['Result'] == 'No') <label for="questionario3-6"><input type="radio" id="questionario3-6" required  name="questionario_3-6"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_3-6_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['3-6']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>7) Sono presenti contenziosi in atto con clienti o fornitori?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-7']['Result'] == 'Si') <label for="questionario3-7"><input type="radio" id="questionario3-7" required  name="questionario_3-7"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-7']['Result'] == 'No') <label for="questionario3-7"><input type="radio" id="questionario3-7" required  name="questionario_3-7"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_3-7_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['3-7']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>8) Sono presenti criticità con clienti derivanti da non conformità o ritardi?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-8']['Result'] == 'Si')<label for="questionario3-8"><input type="radio" id="questionario3-8" required  name="questionario_3-8"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['3-8']['Result'] == 'No') <label for="questionario3-8"><input type="radio" id="questionario3-8" required  name="questionario_3-8"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_3-8_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['3-8']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>



                                        <table class="table table-hover table-light table-bordered">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="3"><h3>Anomalie gestionali</h3></th>
                                                </tr>
                                                <tr>
                                                    <th scope="col">Parametro analizzato</th>
                                                    <th scope="col">Esito</th>
                                                    <th scope="col">Spiegazioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1) Sono stati registrate perdite di fette di mercato, commesse o clienti importanti?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-1']['Result'] == 'Si') <label for="questionario4-1"><input type="radio" id="questionario4-1" required  name="questionario_4-1"  checked  value="Si"> Si</label> @endif @endif
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-1']['Result'] == 'No') <label for="questionario4-1"><input type="radio" id="questionario4-1" required  name="questionario_4-1"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-1_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-1']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2) Si sono verificate perdite di membri della direzione o di figure di responsabilità strategiche senza una loro sostituzione?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-2']['Result'] == 'Si') <label for="questionario4-2"><input type="radio" id="questionario4-2" required  name="questionario_4-2"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-2']['Result'] == 'No') <label for="questionario4-2"><input type="radio" id="questionario4-2" required  name="questionario_4-2"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-2_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-2']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>3) Sono presenti problemi con la gestione del personale</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-3']['Result'] == 'Si') <label for="questionario4-3"><input type="radio" id="questionario4-3" required  name="questionario_4-3"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-3']['Result'] == 'No') <label for="questionario4-3"><input type="radio" id="questionario4-3" required  name="questionario_4-3"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-3_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-3']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>4) Ci sono ritardi nei pagamenti relativi alle retribuzioni superiori a 60gg per un ammontare maggiore della metà dell'ammontare mensile della retribuzione?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-4']['Result'] == 'Si') <label for="questionario4-4"><input type="radio" id="questionario4-4" required  name="questionario_4-4"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-4']['Result'] == 'No') <label for="questionario4-4"><input type="radio" id="questionario4-4" required  name="questionario_4-4"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-4_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-4']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>5) Sono presenti nel mercato nuove aziende concorrenti che possono mettere in difficoltà la nostra azienda?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-5']['Result'] == 'Si') <label for="questionario4-5"><input type="radio" id="questionario4-5" required  name="questionario_4-5"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-5']['Result'] == 'No') <label for="questionario4-5"><input type="radio" id="questionario4-5" required  name="questionario_4-5"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-5_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-5']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>6) Sono state richieste dilazioni alle banche su finanziamenti in essere?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-6']['Result'] == 'Si') <label for="questionario4-6"><input type="radio" id="questionario4-6" required  name="questionario_4-6"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-6']['Result'] == 'No') <label for="questionario4-6"><input type="radio" id="questionario4-6" required  name="questionario_4-6"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-6_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-6']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>7) Vi è la possibilità di incorrere in problemi inerenti l'approviggionamento di prodotti fondamentali o in aumenti drastici dei prezzi di acquisto delle materie prime?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-7']['Result'] == 'Si') <label for="questionario4-7"><input type="radio" id="questionario4-7" required  name="questionario_4-7"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-7']['Result'] == 'No') <label for="questionario4-7"><input type="radio" id="questionario4-7" required  name="questionario_4-7"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-7_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-7']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>8) Sono state registrate variazioni nell'assetto societario?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-8']['Result'] == 'Si') <label for="questionario4-8"><input type="radio" id="questionario4-8" required  name="questionario_4-8"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-8']['Result'] == 'No') <label for="questionario4-8"><input type="radio" id="questionario4-8" required  name="questionario_4-8"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-8_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-8']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>9) Le scelte gestionali portate avanti dall'amministratore (o consiglio di amministrazione) risultano in contrasto con la mission aziendale e con la vision della direzione?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-9']['Result'] == 'Si') <label for="questionario4-9"><input type="radio" id="questionario4-9" required  name="questionario_4-9"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-9']['Result'] == 'No') <label for="questionario4-9"><input type="radio" id="questionario4-9" required  name="questionario_4-9"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-9_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-9']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>10) Vi è possibilità che si verifichino eventi catastrofici per i quali non si dispone di una adeguata copertura assicurativa?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-10']['Result'] == 'Si') <label for="questionario4-10"><input type="radio" id="questionario4-10" required  name="questionario_4-10"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['4-10']['Result'] == 'No') <label for="questionario4-10"><input type="radio" id="questionario4-10" required  name="questionario_4-10"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_4-10_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['4-10']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>



                                        <table class="table table-hover table-light table-bordered">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="3"><h3>Minacce erariali e rischi caratteristici</h3></th>
                                                </tr>
                                                <tr>
                                                    <th scope="col">Parametro analizzato</th>
                                                    <th scope="col">Esito</th>
                                                    <th scope="col">Spiegazioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1) Sono presenti mancati pagamenti verso Agenzia delle Entrate ed Enti di riscossione per oltre 6 mesi</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['5-1']['Result'] == 'Si') <label for="questionario5-1"><input type="radio" id="questionario5-1" required  name="questionario_5-1"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['5-1']['Result'] == 'No') <label for="questionario5-1"><input type="radio" id="questionario5-1" required  name="questionario_5-1"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_5-1_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['5-1']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2) Sono presenti mancati pagamenti verso INPS e INAIL per oltre 6 mesi?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['5-2']['Result'] == 'Si') <label for="questionario5-2"><input type="radio" id="questionario5-2" required  name="questionario_5-2"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['5-2']['Result'] == 'No') <label for="questionario5-2"><input type="radio" id="questionario5-2" required  name="questionario_5-2"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_5-2_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['5-2']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>3) Vi sono procedimenti legali o regolamentari in corso la cui sorte negativa potrebbe comportare richieste di risarcimento alle quali l'impresa potrebbe non far fronte?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['5-3']['Result'] == 'Si') <label for="questionario5-3"><input type="radio" id="questionario5-3" required  name="questionario_5-3"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['5-3']['Result'] == 'No') <label for="questionario5-3"><input type="radio" id="questionario5-3" required  name="questionario_5-3"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_5-3_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['5-3']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>4) Sono entrate in atto modifiche di leggi o regolamenti o politiche governative che potrebbero influenzare negativamente l'impresa?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['5-4']['Result'] == 'Si') <label for="questionario5-4"><input type="radio" id="questionario5-4" required  name="questionario_5-4"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['5-4']['Result'] == 'No') <label for="questionario5-4"><input type="radio" id="questionario5-4" required  name="questionario_5-4"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_5-4_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['5-4']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>



                                        <table class="table table-hover table-light table-bordered">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="3"><h3>Minacce da eventi pregiudizievoli</h3></th>
                                                </tr>
                                                <tr>
                                                    <th scope="col">Parametro analizzato</th>
                                                    <th scope="col">Esito</th>
                                                    <th scope="col">Spiegazioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1) Sono presenti iscrizioni di ipoteche giudiziarie, pegni e forme tecniche di prelazioni sui beni aziendali?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-1']['Result'] == 'Si') <label for="questionario6-1"><input type="radio" id="questionario6-1" required  name="questionario_6-1"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-1']['Result'] == 'No') <label for="questionario6-1"><input type="radio" id="questionario6-1" required  name="questionario_6-1"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_6-1_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['6-1']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2) Sono stati ricevuti decreti ingiuntivi ed atti ricognitivi di avvio di azioni per il recupero di crediti?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-2']['Result'] == 'Si') <label for="questionario6-2"><input type="radio" id="questionario6-2" required  name="questionario_6-2"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-2']['Result'] == 'No') <label for="questionario6-2"><input type="radio" id="questionario6-2" required  name="questionario_6-2"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_6-2_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['6-2']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>3) L'azienda ha subito il protesto di assegni e cambiali?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-3']['Result'] == 'Si') <label for="questionario6-3"><input type="radio" id="questionario6-3" required  name="questionario_6-3"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-3']['Result'] == 'No') <label for="questionario6-3"><input type="radio" id="questionario6-3" required  name="questionario_6-3"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_6-3_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['6-3']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>4) Sono in atto azioni volte alla liquidazione dell'azienda o alla cessazione dell'attività?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-4']['Result'] == 'Si')<label for="questionario6-4"><input type="radio" id="questionario6-4" required  name="questionario_6-4"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-4']['Result'] == 'No') <label for="questionario6-4"><input type="radio" id="questionario6-4" required  name="questionario_6-4"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_6-4_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['6-4']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>5) Vi sono istanze di fallimento avanzate dai creditori aziendali?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-5']['Result'] == 'Si') <label for="questionario6-5"><input type="radio" id="questionario6-5" required  name="questionario_6-5"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-5']['Result'] == 'No') <label for="questionario6-5"><input type="radio" id="questionario6-5" required  name="questionario_6-5"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_6-5_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['6-5']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>6) Si è verificato il default o il fallimento di garanti e default o fallimento dei garanti legati all'azienda (rischio infragruppo)?</td>
                                                    <td>
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-6']['Result'] == 'Si') <label for="questionario6-6"><input type="radio" id="questionario6-6" required  name="questionario_6-6"  checked  value="Si"> Si</label> @endif @endif
                                                        &nbsp;
                                                        @if(count($arrayQuestionario) > 0) @if($arrayQuestionario['6-6']['Result'] == 'No')<label for="questionario6-6"><input type="radio" id="questionario6-6" required  name="questionario_6-6"  checked  value="No"> No</label> @endif @endif
                                                    </td>
                                                    <td>
                                                        <input type="text"  name="questionario_6-6_desc" value="@if(count($arrayQuestionario) > 0) {{$arrayQuestionario['6-6']['Details']}}  @endif">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </x-slot>
                                </x-backend.card>
                            </x-forms.post>

                        </div>
                    </dl>
                </div>
                <div class="tab-pane fade" id="pills-moduloforwardlooking" role="tabpanel" aria-labelledby="pills-moduloforwardlooking">
                    <dl class="dl-horizontal">
                        <h2>Questionario TO BE</h2>
                        <x-forms.post :action="route('admin.allerta.forwardlooking')" enctype="multipart/form-data">
                            <x-backend.card>
                                <x-slot name="header"></x-slot>
                                <x-slot name="body">

                                    <div class="row">
                                        <h4>1) Per i prossimi 6 mesi l'azienda prevede un fatturato rispetto al semestre precedente:</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking1" required value="1" @if($arrayForwardLooking['forwardLooking1'] == 1) checked @endif> Costante</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking1" required value="2" @if($arrayForwardLooking['forwardLooking1'] == 2) checked @endif> In aumento</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking1" required value="3" @if($arrayForwardLooking['forwardLooking1'] == 3) checked @endif> In diminuzione</label>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <h4>2) L'azienda prevede di chiedere nuovi finanziamenti nei prossimi 6 mesi?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking2" required value="1" @if($arrayForwardLooking['forwardLooking2'] == 1) checked @endif> Si, per nuovi investimenti</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking2" required value="2" @if($arrayForwardLooking['forwardLooking2'] == 2) checked @endif> No</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking2" required value="3" @if($arrayForwardLooking['forwardLooking2'] == 3) checked @endif> Si, perchè serve liquidità</label>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <h4>3) L'azienda prevede costi di gestione (fissi e variabili) per i prossimi 6 mesi rispetto al semestre precedente:</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking3" required value="1" @if($arrayForwardLooking['forwardLooking3'] == 1) checked @endif> Costanti</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking3" required value="2" @if($arrayForwardLooking['forwardLooking3'] == 2) checked @endif> In diminuzione</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking3" required value="3" @if($arrayForwardLooking['forwardLooking3'] == 3) checked @endif> In aumento</label>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <h4>4) Su quanti clienti è concentrato il fatturato dei prossimi 6 mesi?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking4" required value="1" @if($arrayForwardLooking['forwardLooking4'] == 1) checked @endif> Fra 10 e 30</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking4" required value="2" @if($arrayForwardLooking['forwardLooking4'] == 2) checked @endif> Più di 30</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking4" required value="3" @if($arrayForwardLooking['forwardLooking4'] == 3) checked @endif> Meno di 10</label>
                                    </div>
                                    <br>


                                    <div class="row">
                                        <h4>5) L'azienda utilizza un sistema di pianificazione e controllo di gestione?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking5" required value="1" @if($arrayForwardLooking['forwardLooking5'] == 1) checked @endif> Su base pluriennale</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking5" required value="2" @if($arrayForwardLooking['forwardLooking5'] == 2) checked @endif> Su base annuale</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking5" required value="3" @if($arrayForwardLooking['forwardLooking5'] == 3) checked @endif> No</label>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <h4>6) L'azienda prevede costi straordinari per i prossimi 6 mesi? (es. manutenzioni starordinarie, minusvalenze da conferimenti aziendali, da ristrutturazione, da espropri, da cessione di beni o contenziosi, oneri per le multe ecc.)</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking6" required value="1" @if($arrayForwardLooking['forwardLooking6'] == 1) checked @endif> Si, ma non rilevanti</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking6" required value="2" @if($arrayForwardLooking['forwardLooking6'] == 2) checked @endif> No</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking6" required value="3" @if($arrayForwardLooking['forwardLooking6'] == 3) checked @endif> Si</label>
                                    </div>
                                    <br>

                                    <div class="row">
                                        <h4>7) L'azienda prevede di utilizzare al limite (o oltre) le disponibilità per liquidità di cassa nei prossimi 6 mesi?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking7" required value="1" @if($arrayForwardLooking['forwardLooking7'] == 1) checked @endif> Si, per aumento previsto di utilizzi</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking7" required value="2" @if($arrayForwardLooking['forwardLooking7'] == 2) checked @endif> No</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking7" required value="3" @if($arrayForwardLooking['forwardLooking7'] == 3) checked @endif> Si, li usiamo sempre al limite</label>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <h4>8) L'azienda prevede flussi di cassa della gestione operativa (ricavi esigibili - costi da sostenere nei prossimi 6 mesi) sufficienti a coprire gli impegni finanziari (quote capitali sui finanziamenti e oneri finanziari) dei prossimi 6 mesi (DSCR)?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking8" required value="1" @if($arrayForwardLooking['forwardLooking8'] == 1) checked @endif> Forse si, ma potrebbero esserci difficoltà</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking8" required value="2" @if($arrayForwardLooking['forwardLooking8'] == 2) checked @endif> Si</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking8" required value="3" @if($arrayForwardLooking['forwardLooking8'] == 3) checked @endif> No, serve sicuramente liquidità</label>
                                    </div>
                                    <br>


                                    <div class="row">
                                        <h4>9) L'azienda prevede che le disponibilità di cassa attuali e le entrate (ordinarie + straordinarie) dei prossimi 6 mesi saranno in grado di coprire tutte le passività/uscite (impegni finanziari e commerciali) dei prossimi 6 mesi (margine di tesorerie positivo)?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking9" required value="1" @if($arrayForwardLooking['forwardLooking9'] == 1) checked @endif> Probabilmente si</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking9" required value="2" @if($arrayForwardLooking['forwardLooking9'] == 2) checked @endif> Si</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking9" required value="3" @if($arrayForwardLooking['forwardLooking9'] == 3) checked @endif> No</label>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <h4>10) L'azienda prevede tempi medi di pagamento ai fornitori (uscite) inferiori ai tempi medi di incasso dai clienti (entrate)?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking10" required value="1" @if($arrayForwardLooking['forwardLooking10'] == 1) checked @endif> No</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking10" required value="3" @if($arrayForwardLooking['forwardLooking10'] == 3) checked @endif> Si, i tempi di pagamento ai fornitori sono più corti</label>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <h4>11) L'azienda prevede reiterati e significativi ritardi nei pagamenti verso terzi (fornitori, dipendenti, erario, enti previdenziali, finanziamenti) nei prossimi 6 mesi?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking11" required value="1" @if($arrayForwardLooking['forwardLooking11'] == 1) checked @endif> Si, ma evitabili</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking11" required value="2" @if($arrayForwardLooking['forwardLooking11'] == 2) checked @endif> No</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking11" required value="3" @if($arrayForwardLooking['forwardLooking11'] == 3) checked @endif> Si</label>
                                    </div>
                                    <br>


                                    <div class="row">
                                        <h4>12) L'azienda prevede di utilizzare al limite (o oltre) le disponibilità del castelletto anticipi nei prossimi 6 mesi?</h4>
                                    </div>

                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking12" required value="1" @if($arrayForwardLooking['forwardLooking12'] == 1) checked @endif> No</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking12" required value="2" @if($arrayForwardLooking['forwardLooking12'] == 2) checked @endif> Si, prevediamo maggior utilizzo</label>
                                    </div>
                                    <div>
                                        <label for="forwardLooking1"><input type="radio" name="forwardLooking12" required value="3" @if($arrayForwardLooking['forwardLooking12'] == 3) checked @endif> Si, usiamo sempre al limite le disponibilità</label>
                                    </div>

                                    <br>
                                </x-slot>
                            </x-backend.card>
                        </x-forms.post>

                    </dl>
                </div>
                <div class="tab-pane fade" id="pills-esitosistemaallerta" role="tabpanel" aria-labelledby="pills-esitosistemaallerta">
                    <div class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title"><h2>Giudizio finale sistema di allerta</h2></div>
                        </div>
                        <div class="card-body">
                            <p>Il modulo avanzato del sistema di Allerta mira a valutare lo stato di salute aziendale in maniera approfondita fornendo
                                indicazioni all’utente sulla presenza di anomalie di bilancio, anomalie derivanti dalla gestione degli affidamenti bancari,
                                anomalie nei rapporti con clienti e fornitori, anomalie gestionali ed anomalie derivanti da eventi pregiudizievoli o eventuali
                                rischi.
                                Al fine di produrre una analisi esaustiva sarà necessario aver caricato il bilancio, la Centrale Rischi degli ultimi 36 mesi e
                                aver risposto ad una serie di semplici domande.</p>
                            <p>
                                Il risultato del Sistema di Allerta deriva dalla valutazione delle aree inerenti l’analisi di Bilancio, quella della
                                Centrale Rischi e delle risposte ai questionari AS IS e TO BE. Attraverso un sistema di pesi attribuiti a ciascuna macroarea il
                                sistema elabora il giudizio finale.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-hover table-light table-bordered">
                                    <thead>
                                        <tr>
                                            <th scope="col">Area Esaminata</th>
                                            <th scope="col">Risultato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="border: 1px solid black">1) Analisi Centrale Rischi</td>
                                            <th style="border: 1px solid black">
                                                <?php
                                                if($punteggioCR >= 0 && $punteggioCR < 0.14){
                                                    echo "Default";
                                                }
                                                else if($punteggioCR >= 0.14 && $punteggioCR < 0.28){
                                                    echo "Situazione Grave";
                                                }
                                                else if($punteggioCR >= 0.28 && $punteggioCR < 0.42){
                                                    echo "Alert";
                                                }
                                                else if($punteggioCR >= 0.42 && $punteggioCR < 0.56){
                                                    echo "Rischio alert";
                                                }
                                                else if($punteggioCR >= 0.56 && $punteggioCR < 0.70){
                                                    echo "Fragilità elevata";
                                                }
                                                else if($punteggioCR >= 0.70 && $punteggioCR < 0.85){
                                                    echo "Fragilità";
                                                }
                                                else if($punteggioCR >= 0.85 && $punteggioCR <= 1){
                                                    echo "Solidità";
                                                }
                                                ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid black">2) Analisi bilancio</td>
                                            <th style="border: 1px solid black">
                                                <?php
                                                if($bilancioData['Giudizi']['Score'] >= 0 && $bilancioData['Giudizi']['Score'] < 0.14){
                                                    echo "Default";
                                                }
                                                else if($bilancioData['Giudizi']['Score'] >= 0.14 && $bilancioData['Giudizi']['Score'] < 0.28){
                                                    echo "Situazione Grave";
                                                }
                                                else if($bilancioData['Giudizi']['Score'] >= 0.28 && $bilancioData['Giudizi']['Score'] < 0.42){
                                                    echo "Alert";
                                                }
                                                else if($bilancioData['Giudizi']['Score'] >= 0.42 && $bilancioData['Giudizi']['Score'] < 0.56){
                                                    echo "Rischio alert";
                                                }
                                                else if($bilancioData['Giudizi']['Score'] >= 0.56 && $bilancioData['Giudizi']['Score'] < 0.70){
                                                    echo "Fragilità elevata";
                                                }
                                                else if($bilancioData['Giudizi']['Score'] >= 0.70 && $bilancioData['Giudizi']['Score'] < 0.85){
                                                    echo "Fragilità";
                                                }
                                                else if($bilancioData['Giudizi']['Score'] >= 0.85 && $bilancioData['Giudizi']['Score'] <= 1){
                                                    echo "Solidità";
                                                }
                                                ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid black">3) Minacce rapporti commerciali</td>
                                            <th style="border: 1px solid black">
                                                <?php
                                                if($scoreASIS['3'] >= 0 && $scoreASIS['3'] < 0.14){
                                                    echo "Default";
                                                }
                                                else if($scoreASIS['3'] >= 0.14 && $scoreASIS['3'] < 0.28){
                                                    echo "Situazione Grave";
                                                }
                                                else if($scoreASIS['3'] >= 0.28 && $scoreASIS['3'] < 0.42){
                                                    echo "Alert";
                                                }
                                                else if($scoreASIS['3'] >= 0.42 && $scoreASIS['3'] < 0.56){
                                                    echo "Rischio alert";
                                                }
                                                else if($scoreASIS['3'] >= 0.56 && $scoreASIS['3'] < 0.70){
                                                    echo "Fragilità elevata";
                                                }
                                                else if($scoreASIS['3'] >= 0.70 && $scoreASIS['3'] < 0.85){
                                                    echo "Fragilità";
                                                }
                                                else if($scoreASIS['3'] >= 0.85 && $scoreASIS['3'] <= 1){
                                                    echo "Solidità";
                                                }
                                                ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid black">4) Minacce gestione aziendale</td>
                                            <th style="border: 1px solid black">
                                                <?php
                                                if($scoreASIS['4'] >= 0 && $scoreASIS['4'] < 0.14){
                                                    echo "Default";
                                                }
                                                else if($scoreASIS['4'] >= 0.14 && $scoreASIS['4'] < 0.28){
                                                    echo "Situazione Grave";
                                                }
                                                else if($scoreASIS['4'] >= 0.28 && $scoreASIS['4'] < 0.42){
                                                    echo "Alert";
                                                }
                                                else if($scoreASIS['4'] >= 0.42 && $scoreASIS['4'] < 0.56){
                                                    echo "Rischio alert";
                                                }
                                                else if($scoreASIS['4'] >= 0.56 && $scoreASIS['4'] < 0.70){
                                                    echo "Fragilità elevata";
                                                }
                                                else if($scoreASIS['4'] >= 0.70 && $scoreASIS['4'] < 0.85){
                                                    echo "Fragilità";
                                                }
                                                else if($scoreASIS['4'] >= 0.85 && $scoreASIS['4'] <= 1){
                                                    echo "Solidità";
                                                }
                                                ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid black">5) Minacce da eventi pregiudizievoli</td>
                                            <th style="border: 1px solid black">
                                                <?php
                                                if($scoreASIS['5'] >= 0 && $scoreASIS['5'] < 0.14){
                                                    echo "Default";
                                                }
                                                else if($scoreASIS['5'] >= 0.14 && $scoreASIS['5'] < 0.28){
                                                    echo "Situazione Grave";
                                                }
                                                else if($scoreASIS['5'] >= 0.28 && $scoreASIS['5'] < 0.42){
                                                    echo "Alert";
                                                }
                                                else if($scoreASIS['5'] >= 0.42 && $scoreASIS['5'] < 0.56){
                                                    echo "Rischio alert";
                                                }
                                                else if($scoreASIS['5'] >= 0.56 && $scoreASIS['5'] < 0.70){
                                                    echo "Fragilità elevata";
                                                }
                                                else if($scoreASIS['5'] >= 0.70 && $scoreASIS['5'] < 0.85){
                                                    echo "Fragilità";
                                                }
                                                else if($scoreASIS['5'] >= 0.85 && $scoreASIS['5'] <= 1){
                                                    echo "Solidità";
                                                }
                                                ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid black">6) Minacce erariali e rischi caratteristici</td>
                                            <th style="border: 1px solid black">
                                                <?php
                                                if($scoreASIS['6'] >= 0 && $scoreASIS['6'] < 0.14){
                                                    echo "Default";
                                                }
                                                else if($scoreASIS['6'] >= 0.14 && $scoreASIS['6'] < 0.28){
                                                    echo "Situazione Grave";
                                                }
                                                else if($scoreASIS['6'] >= 0.28 && $scoreASIS['6'] < 0.42){
                                                    echo "Alert";
                                                }
                                                else if($scoreASIS['6'] >= 0.42 && $scoreASIS['6'] < 0.56){
                                                    echo "Rischio alert";
                                                }
                                                else if($scoreASIS['6'] >= 0.56 && $scoreASIS['6'] < 0.70){
                                                    echo "Fragilità elevata";
                                                }
                                                else if($scoreASIS['6'] >= 0.70 && $scoreASIS['6'] < 0.85){
                                                    echo "Fragilità";
                                                }
                                                else if($scoreASIS['6'] >= 0.85 && $scoreASIS['6'] <= 1){
                                                    echo "Solidità";
                                                }
                                                ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th>Profilo rischio AS IS</th>
                                            <th>
                                                @if($ASISfinalScore)
                                                    {{$ASISfinalScore['Giudizio']}}
                                                @endisset
                                            </th>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>
                            <p>
                                Il profilo di rischio AS IS esamina le 3 macroaree inerenti la situazione attuale che si evince dagli ultimi dati di bilancio e
                                centrale rischi e dalle risposte date al primo questionario. I possibili risultati sono divisi in 7 giudizi: DEFAULT,
                                SITUAZIONE GRAVE, ALERT, RISCHIO ALERT, FRAGILITA’ ELEVATA, FRAGILITA’, SOLIDITA’.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-hover table-light table-bordered">
                                    <thead>
                                        <tr>
                                            <th scope="col">Area Esaminata</th>
                                            <th scope="col">Risultato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>7) Questionario TO BE</td>
                                            <th>{{$scoreFL['Giudizio']}}</th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <div class="card">
                        <div class="card-header  border-0 responsive-header">
                            <h4 class="card-title">Esito: @if(isset($generalScore['Giudizio'])) {{$generalScore['Giudizio']}} @endif</h4>
                        </div>
                        <div class="card-body">

                                <p>
                                    Il profilo di rischio TO BE integra il giudizio derivante dal profilo di rischio AS IS con le risposte date al secondo
questionario in modo da fornire una visione prospettica della situazione aziendale.
I possibili risultati sono divisi in 7 giudizi:
DEFAULT, SITUAZIONE GRAVE, ALERT, RISCHIO ALERT, FRAGILITA’ ELEVATA, FRAGILITA’, SOLIDITA’
                                </p>
                            </h4>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

