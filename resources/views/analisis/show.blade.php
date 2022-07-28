@extends('backend.layouts.app')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading clearfix">
        @if(isset($msg))<div class="alert alert-danger" role="alert">
             {{ $msg }} 
        </div>
        @endif
    </div>

<x-forms.post :action="route('admin.analisis.analisi.basic')">
    <input type="hidden" name="idBilancio" value="{{$idBilancio}}">
    <div class="panel-body">
        <div class="">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title">Stampa analisi Basic <a href="{{ route('admin.pdf.basicPDF', (int)$idBilancio ) }}" class="action-btns1" data-placement="top"><i class="fe fe-file-text primary text-primary"></i></a></div>
                        </div>
                        <div class="card-body">
                            <h2 class="text-center">{{$nomeAzienda}}</h2>
                            <p>Al fine di adempiere alle direttive dettate dalla Legge 155/2017 il sistema prende in considerazione l’ultimo bilancio depositato ed una serie di indicazioni
                                sullo stato attuale dell’azienda e sulle previsioni di performance a sei mesi derivanti dalla compilazione di un questionario.
                                Per intercettare l’eventuale stato di crisi è sufficiente valutare il Patrimonio Netto aziendale e il DSCR previsionale a sei mesi. <br> È necessario che l’utente dia
                                indicazioni sulla effettiva possibilità di calcolare il valore DSCR (Debt Service Cover Ratio) a 6 mesi e sull’attendibilità del risultato di calcolo di questo
                                indicatore.
                                Qualora l’azienda disponga di un budget di tesoreria potrà inserire i dati al fine di permettere al sistema di calcolare il valore del DSCR flaggando la casella
                                "DSCR a 6 mesi attendibile".
                                Nel caso in cui il valore del DSCR non fosse determinabile o attendibile, il sistema proseguirà con il calcolo di altri indici di bilancio.</p>
                        </div>
                    </div>


                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" id="basic-datatable">
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th style="width: 50%" class="text-center">Patrimonio Netto Negativo</th>
                                            <td id="PatrimonioNetto" style="width: 50%" class="text-center">
                                                @if($dataAnalisisBasic['Patrimonio_Netto'] < 0)
                                                Si
                                                @else
                                                No
                                                @endif
                                            </td>
                                        </tr>

                                        <tr <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> >
                                            <th style="width: 50%" class="text-center">DSCR a 6 mesi attendibile?</th>
                                            <td style="width: 50%" class="text-center">
                                                <label onclick="DSCRyes()" for="DSCRyes"><input type="radio" id="DSCRyes" required  name="DSCR" @if(isset($sistemaBasic[0])) @if($sistemaBasic[0]->DSCR == "si") checked @endif  @endif value="si"> Si</label>
                                                &nbsp;
                                                <label onclick="DSCRno()" for="DSCRno"><input type="radio" id="DSCRno" required name="DSCR" @if(isset($sistemaBasic[0])) @if($sistemaBasic[0]->DSCR == "no") checked @endif @endif value="no"> No</label>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div style="display: @if(isset($sistemaBasic[0])) @if($sistemaBasic[0]->DSCR == "no") @else none @endif @else none @endif" id="soglieIndici" class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title">Soglie Indici</div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" id="basic-datatable">
                                    <thead>
                                    </thead>
                                    <tbody>
                                        @foreach($soglieBasic as $index=>$soglia)
                                        <tr>
                                            <td style="width: 33%" class="text-center">{{$index}}</td>
                                            <td style="width: 33%" class="text-center">{{$basicData[$index]}}%</td>
                                            <td style="width: 33%" class="text-center">@if($soglia) Entro Soglia @else Fuori Soglia @endif </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div style="display: @if(isset($sistemaBasic[0])) @if($sistemaBasic[0]->DSCR == "si") @else none @endif @else none @endif" id="calcoloDSCR" class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title">Calcolo DSCR</div>
                        </div>
                        <div class="card-body">
                            <h5>Data </h5>
                            <input style="width: 30%; border: 0.1px solid lightgrey" class="form-control it-date-datepicker" name="DSCRDate" type="date" @if(isset($sistemaBasic[0]->DSCRDate)) value="{{$sistemaBasic[0]->DSCRDate}}" @endif>
                            <br>
                            <h5>Disponibilità liquida di partenza</h5>
                            <input id="dispLiquida" style="width: 30%; border: 0.1px solid lightgrey" class="form-control it-date-datepicker" name="DSCRdispLiquida" type="number" @if(isset($sistemaBasic[0]->DSCRdispLiquida)) value="{{$sistemaBasic[0]->DSCRdispLiquida}}" @endif>
                            <hr> 
                            <h3 class="text-center">Previsioni di Cash Flow a servizio del debito</h3> 
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" >
                                    <thead>
                                        <tr>
                                            <td class="text-center">    </td> 
                                            <td class="text-center">Mese 1</td> 
                                            <td class="text-center">Mese 2</td> 
                                            <td class="text-center">Mese 3</td> 
                                            <td class="text-center">Mese 4</td> 
                                            <td class="text-center">Mese 5</td> 
                                            <td class="text-center">Mese 6</td>  
                                        </tr> 
                                    </thead>
                                    <tbody> 
                                        <tr>
                                            <td class="text-center">Entrate di cassa</td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="entrataDSCRCFmese1" @if(isset($sistemaBasic[0]->entrataDSCRCFmese1)) value="{{$sistemaBasic[0]->entrataDSCRCFmese1}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="entrataDSCRCFmese2" @if(isset($sistemaBasic[0]->entrataDSCRCFmese2)) value="{{$sistemaBasic[0]->entrataDSCRCFmese2}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="entrataDSCRCFmese3" @if(isset($sistemaBasic[0]->entrataDSCRCFmese3)) value="{{$sistemaBasic[0]->entrataDSCRCFmese3}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="entrataDSCRCFmese4" @if(isset($sistemaBasic[0]->entrataDSCRCFmese4)) value="{{$sistemaBasic[0]->entrataDSCRCFmese4}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="entrataDSCRCFmese5" @if(isset($sistemaBasic[0]->entrataDSCRCFmese5)) value="{{$sistemaBasic[0]->entrataDSCRCFmese5}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="entrataDSCRCFmese6" @if(isset($sistemaBasic[0]->entrataDSCRCFmese6)) value="{{$sistemaBasic[0]->entrataDSCRCFmese6}}" @endif> </td> 
                                        </tr> 
                                        <tr>
                                            <td class="text-center">Uscite di cassa</td>  
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="uscitaDSCRCFmese1" @if(isset($sistemaBasic[0]->uscitaDSCRCFmese1)) value="{{$sistemaBasic[0]->uscitaDSCRCFmese1}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="uscitaDSCRCFmese2" @if(isset($sistemaBasic[0]->uscitaDSCRCFmese2)) value="{{$sistemaBasic[0]->uscitaDSCRCFmese2}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="uscitaDSCRCFmese3" @if(isset($sistemaBasic[0]->uscitaDSCRCFmese3)) value="{{$sistemaBasic[0]->uscitaDSCRCFmese3}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="uscitaDSCRCFmese4" @if(isset($sistemaBasic[0]->uscitaDSCRCFmese4)) value="{{$sistemaBasic[0]->uscitaDSCRCFmese4}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="uscitaDSCRCFmese5" @if(isset($sistemaBasic[0]->uscitaDSCRCFmese5)) value="{{$sistemaBasic[0]->uscitaDSCRCFmese5}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="uscitaDSCRCFmese6" @if(isset($sistemaBasic[0]->uscitaDSCRCFmese6)) value="{{$sistemaBasic[0]->uscitaDSCRCFmese6}}" @endif> </td> 
                                        </tr> 
                                    </tbody>
                                </table>
                            </div>
                            <hr>
                            <h3 class="text-center">Previsioni di rimborso debiti finanziari</h3>
                            <div class="table-responsive"> 
                                <table class="table table-bordered text-nowrap border-bottom" >
                                    <thead>
                                        <tr>
                                            <td class="text-center">    </td> 
                                            <td class="text-center">Mese 1</td> 
                                            <td class="text-center">Mese 2</td> 
                                            <td class="text-center">Mese 3</td> 
                                            <td class="text-center">Mese 4</td> 
                                            <td class="text-center">Mese 5</td> 
                                            <td class="text-center">Mese 6</td> 
                                        </tr> 
                                    </thead>
                                    <tbody> 
                                        <tr>
                                            <td class="text-center">Rate finanziamenti + Oneri Finanziari</td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="rimborsoDSCRmese1" @if(isset($sistemaBasic[0]->rimborsoDSCRmese1)) value="{{$sistemaBasic[0]->rimborsoDSCRmese1}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey"type="number" name="rimborsoDSCRmese2" @if(isset($sistemaBasic[0]->rimborsoDSCRmese2)) value="{{$sistemaBasic[0]->rimborsoDSCRmese2}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="rimborsoDSCRmese3" @if(isset($sistemaBasic[0]->rimborsoDSCRmese3)) value="{{$sistemaBasic[0]->rimborsoDSCRmese3}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="rimborsoDSCRmese4" @if(isset($sistemaBasic[0]->rimborsoDSCRmese4)) value="{{$sistemaBasic[0]->rimborsoDSCRmese4}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey"type="number" name="rimborsoDSCRmese5" @if(isset($sistemaBasic[0]->rimborsoDSCRmese5)) value="{{$sistemaBasic[0]->rimborsoDSCRmese5}}" @endif> </td> 
                                            <td class="text-center"><input style="border: 0.1px solid lightgrey" type="number" name="rimborsoDSCRmese6" @if(isset($sistemaBasic[0]->rimborsoDSCRmese6)) value="{{$sistemaBasic[0]->rimborsoDSCRmese6}}" @endif> </td> 
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <a onclick="calcolaDSCR();" class="btn btn-primary">Calcola DSCR</a>
                            <hr>
                            <h3>Risultato: <span id="risultatoDSCR"></span> <input style="border: 0" readonly type="text" id="allertaDSCR" name="alertDSCR"></h3>
                        </div>
                    </div>

                    <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title">
                                ANALISI DEI RAPPORTI CON CREDITORI PUBBLICI
                            </div>
                        </div>
                        <div class="card-body">
                            <p>L’articolo 15 del Codice della Crisi di Impresa obbliga i creditori pubblici qualificati (Agenzia delle Entrate, INPS e Agente della Riscossione) a segnalare il
                                debitore che abbia superato i limiti previsti. Nel caso in cui da uno dei seguenti controlli emergesse un alert, il debitore avrà l’obbligo di risolvere l’alert
                                entro 90 giorni sanando il proprio debito oppure dovrà proporre una domanda di accesso ad una delle procedure di crisi previste dal Codice o presentare
                                istanza per trovare un accordo con il creditore.
                                Qualora l’alert non venisse risolto il debitore sarà passibile di segnalazione all’OCRI.
                                Se il debitore fornirà prove documentali di esser titolare di credito di imposta o altri crediti vantati verso la PA di entità pari o superiore alla metà delle
                                soglie di rilevanza identificate per i vari Enti pubblici creditori, allora l’Ente non potrà effettuare la segnalazione all’OCRI.</p>
                            <h4>1. Agenzia delle Entrate</h4>
                            <p>L’Agenzia delle Entrata è tenuta ad attivarsi in presenza di un debito IVA scaduto e non versato dal contribuente “rilevante” e rapportato alla liquidazione
                                periodica trimestrale.
                                Il debito viene definito “rilevante” nel caso in cui sia pari almeno al 30% del volume di affari del periodo preso in considerazione e non inferiore a 25.000
                                € / 50.000 € / 100.000 € a seconda che il volume d’affari del contribuente derivante dalla dichiarazione IVA dell’anno precedente sia stato inferiore a 2
                                mln € / 10 mln € / oltre 10 mln €</p>
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" >
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 50%" >Debito IVA scaduto non versato relativo all'ultima liquidazione IVA trimestrale</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="number" onkeyup="calcolaAgenziaEntrate()" name="agenziaEntrate1" @if(isset($sistemaBasic[0]->agenziaEntrate1)) value="{{$sistemaBasic[0]->agenziaEntrate1}}" @endif></td> 
                                        </tr>  
                                        <tr>
                                            <td style="width: 50%" >Volume d'affari riferito al trimestre dell'ultima liquidazione IVA</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="number" onkeyup="calcolaAgenziaEntrate()" name="agenziaEntrate2" @if(isset($sistemaBasic[0]->agenziaEntrate2)) value="{{$sistemaBasic[0]->agenziaEntrate2}}" @endif></td> 
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" >Volume d’affari relativo alla dichiarazione IVA dell’anno precedente</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey"  onkeyup="calcolaAgenziaEntrate()" type="number" name="agenziaEntrate3"  @if(isset($sistemaBasic[0]->agenziaEntrate3)) value="{{$sistemaBasic[0]->agenziaEntrate3}}" @else value="{{$ValoreProduzioneRicaviVenditePrestazioni}}" @endif ></td> 
                                        </tr> 
                                        <tr>
                                            <td style="width: 50%" >Debito IVA scaduto / Volume d'affari dell' ultima liquidazione IVA</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" readonly type="text" name="agenziaEntrate4" @if(isset($sistemaBasic->agenziaEntrate4)) value="{{$sistemaBasic->agenziaEntrate4}}" @endif> %</td> 
                                        </tr> 
                                    </tbody>
                                </table>
                                <h4>Alert: <input style="border: 0" readonly type="text" id="allertaAgenziaEntrate" name="alertAgenziaEntrate"></h4>
                            </div>
                                <h4>2. INPS</h4>
                                <p>L’Inps è tenuto ad attivarsi quando il debitore è in ritardo di oltre sei mesi nel versamento di contributi previdenziali e questi siano superiori alla metà di
                                    quelli dovuti nell'anno precedente e maggiori di 50.000 €.</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered text-nowrap border-bottom" >
                                        <thead>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td style="width: 50%" >Contributi previdenziali NON versati e con ritardo superiore a 6 mesi (ad esclusione di debiti contributivi non versati ma che sono già oggetto di rateizzazione)</td> 
                                                <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="number" onkeyup="calcolaINPS()" name="INPS1" @if(isset($sistemaBasic[0]->INPS1)) value="{{$sistemaBasic[0]->INPS1}}" @endif></td> 
                                            </tr>  
                                            <tr>
                                                <td style="width: 50%" >Totale contributi previdenziali dell’anno precedente</td> 
                                                <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="number" onkeyup="calcolaINPS()" name="INPS2" @if(isset($sistemaBasic[0]->INPS2)) value="{{$sistemaBasic[0]->INPS2}}" @endif></td> 
                                            </tr> 
                                            <tr>
                                                <td style="width: 50%" >Contributi previdenziali NON versati da oltre 6 mesi / Totale contributi previdenziali anno precedente</td> 
                                                <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="text" readonly name="INPS3" @if(isset($sistemaBasic[0]->INPS3)) value="{{$sistemaBasic[0]->INPS3}}" @endif> %</td> 
                                            </tr> 
                                        </tbody>
                                    </table>
                                    <h4>Alert: <input style="border: 0" readonly type="text" id="allertaINPS" name="alertINPS"></h4>
                                </div>
                            <h4>3. Agente della riscossione</h4>
                            <p>L’Agente della Riscossione è tenuto ad attivarsi quando la sommatoria dei crediti affidati per la riscossione auto-dichiarati o definitivamente accertati e
                            scaduti da oltre novanta giorni superi la soglia di 500.000 € per le imprese individuali e quella di 1.000.000 € per le imprese collettive.</p>
                            <h5>Totale crediti affidati per la riscossione scaduti da oltre 90 giorni</h5>
                            <hr>
                            <input style="border: 0.1px solid lightgrey" id="riscossione" type="number" onkeyup="calcolaRiscossione()" name="riscossione" @if(isset($sistemaBasic[0]->riscossione)) value="{{$sistemaBasic[0]->riscossione}}" @endif>
                            <hr>
                            <h4>Alert: <input style="border: 0" readonly type="text" id="allertaRiscossione" name="alertRiscossione"></h4>
                        </div>
                    </div>

                    <div class="card">
                        <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> class="card-header border-bottom-0">
                           <div class="card-title">
                            ACCESSO ALLE MISURE PREMIALI
                            </div>
                        </div>
                        <div class="card-body">
                            <p>L’articolo 24 del Codice della Crisi di Impresa stabilisce che il debitore ha diritto ad una serie di misure premiali nel caso in cui sia tempestivo (ovvero
                                agisca entro 6 mesi) nella richiesta di accesso a una delle procedure regolate dal Codice quando si accorga della presenza di uno dei seguenti Alert (Debiti
                                per retribuzioni, Debiti verso fornitori, Superamento soglie indici CNDCEC).</p>
                            <h4>a. Debiti per retribuzioni</h4>
                            <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" >
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 50%" >Debiti per retribuizioni scaduti da più di 60 giorni</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="number" onkeyup="calcolaRetribuzione();" name="retribuzioni1" @if(isset($sistemaBasic[0]->retribuzioni1)) value="{{$sistemaBasic[0]->retribuzioni1}}" @endif></td> 
                                        </tr>  
                                        <tr>
                                            <td style="width: 50%" >Totale delle retribuzioni mensili</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="text" onkeyup="calcolaRetribuzione();" value="{{(int)$salariStipendi/12}}" name="retribuzioni2" @if(isset($sistemaBasic[0]->retribuzioni2)) value="{{$sistemaBasic[0]->retribuzioni2}}" @endif></td> 
                                        </tr> 
                                        <tr>
                                            <td style="width: 50%" >Debiti per retribuzioni scaduti / Totale retribuzioni mensili</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="text" name="retribuzioni3" @if(isset($sistemaBasic[0]->retribuzioni3)) value="{{$sistemaBasic[0]->retribuzioni3}}" @endif> %</td> 
                                        </tr> 
                                    </tbody>
                                </table>
                                <h4>Alert: <input style="border: 0" readonly type="text" id="allertaRetribuzioni" name="alertRetribuzioni"></h4>
                            </div>
                            <h4 <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?>>b. Debiti verso fornitori</h4>
                                <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> class="table-responsive">
                                <table  class="table table-bordered text-nowrap border-bottom" >
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 50%" >Debiti verso fornitori scaduti da più di 120 giorni</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="number" onkeyup="calcolaFornitori();" name="fornitori1" @if(isset($sistemaBasic[0]->fornitori1)) value="{{$sistemaBasic[0]->fornitori1}}" @endif></td> 
                                        </tr>  
                                        <tr>
                                            <td style="width: 50%" >Debiti verso fornitori non scaduti</td> 
                                            <td style="width: 50%" class="text-center"><input style="border: 0.1px solid lightgrey" type="number" onkeyup="calcolaFornitori();" name="fornitori2" @if(isset($sistemaBasic[0]->fornitori2)) value="{{$sistemaBasic[0]->fornitori2}}" @endif></td> 
                                        </tr>
                                        </tbody>
                                    </table>
                                    <h4>Alert: <input style="border: 0" readonly type="text" id="allertaFornitori" name="alertFornitori"></h4>
                                </div>
                            <h4 <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?>>c. Superamento soglie degli indici elaborati da CNDCEC</h4>
                            <p <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?>>Qualora il patrimonio netto fosse negativo il debitore non potrebbe accedere alle misure premiali.
                                Se il Patrimonio Netto non risulterà negativo dovrà essere verificato che il DSCR a 6 mesi sia pari o superiore a 1, in questo caso il debitore non sarà
                                considerato a rischio. Se invece il DSCR a 6 mesi risultasse inferiore a 1 scatterebbe l'Alert.
                                Nel caso in cui non sia possibile determinare il valore del DSCR a 6 mesi, basterà verificare che i 5 indici definiti nell’articolo 13 comma 2 risultino tutti
                                all’interno delle soglie definite dal CNDCEC.
                                Se tutti e 5 gli indici del CNDCEC supereranno i valori soglia scatterà l’alert, altrimenti il debitore non verrà considerato a rischio.</p>
                            <div class="table-responsive">                                        
                                <table class="table table-bordered text-nowrap border-bottom" id="basic-datatable">
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Patrimonio Netto Negativo</td>
                                            <td id="PatrimonioNetto" style="width: 50%" class="text-center">
                                                @if($dataAnalisisBasic['Patrimonio_Netto'] < 0)
                                                Si
                                                @else
                                                No
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Indice DSCR</td>
                                            <td id="AlertDSCR" style="width: 50%" class="text-center">
                                                
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-center">Indici CNDCEC</td>
                                            <td class="text-center">@if($valutazioneAllertaBasic) Azienda a Rischio @else Azienda non a Rischio @endif</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?>    class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title">Recap Alerts</div>
                        </div>
                        <div class="card-body">
                            <a class="btn btn-primary" style="display: inline-block" onclick="recapAlert()">Aggiorna Sezione Alerts</a>
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" >
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Alert Agenzia Entrate</td>
                                            <td style="width: 50%" class="text-center" id="recapAgenziaEntrate"></td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Alert INPS</td>
                                            <td style="width: 50%" class="text-center" id="recapINPS"></td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Alert Agente della riscossione</td>
                                            <td style="width: 50%" class="text-center" id="recapRiscossione"></td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Alert Debiti per retribuzioni</td>
                                            <td style="width: 50%" class="text-center" id="recapRetribuzioni"></td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Alert Debiti verso fornitori</td>
                                            <td style="width: 50%" class="text-center" id="recapFornitori"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-success">Salva Analisi</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-forms.post>

</div>


@endsection