<style>
    * {
  font-family: Arial, Helvetica, sans-serif;
}
</style>

<x-forms.post :action="route('admin.analisis.analisi.basic')">
    <div class="panel-body">
        <div class="">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h1 class="text-center">{{$nomeAzienda}}</h1>
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
                                                @if(isset($sistemaBasic)) @if($sistemaBasic->DSCR == "no") No @else Si @endif @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div @if(isset($sistemaBasic)) @if($sistemaBasic->DSCR == "si") style="display: none" @endif @endif  id="soglieIndici" class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title"><h2>Soglie Indici</h2></div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" id="basic-datatable">
                                    <thead>
                                    </thead>
                                    <tbody>
                                        @foreach($soglieBasic as $index=>$soglia)
                                        <tr>
                                            <td style="width: 50%" class="text-center">{{$index}}</td>
                                            <td style="width: 50%" class="text-center">@if($soglia) Entro Soglia @else Fuori Soglia @endif </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div @if(isset($sistemaBasic)) @if($sistemaBasic->DSCR == "no") style="display: none" @endif @endif id="calcoloDSCR" class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title"> <h2>Calcolo DSCR</h2></div>
                        </div>
                        <div class="card-body">
                            <h3>Data: @if(isset($sistemaBasic->DSCRDate)) {{$sistemaBasic->DSCRDate}} @endif </h3>
                            <h5>Disponibilità liquida di partenza</h5>
                            <p>@if(isset($sistemaBasic->DSCRdispLiquida)) {{number_format($sistemaBasic->DSCRdispLiquida,0,',','.')}} € @endif </p>
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
                                            <td class="text-center">Entrate di cassa |</td> 
                                            <td class="text-center">@if(isset($sistemaBasic->entrataDSCRCFmese1)) {{number_format($sistemaBasic->entrataDSCRCFmese1,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->entrataDSCRCFmese2)) {{number_format($sistemaBasic->entrataDSCRCFmese2,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->entrataDSCRCFmese3)) {{number_format($sistemaBasic->entrataDSCRCFmese3,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->entrataDSCRCFmese4)) {{number_format($sistemaBasic->entrataDSCRCFmese4,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->entrataDSCRCFmese5)) {{number_format($sistemaBasic->entrataDSCRCFmese5,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->entrataDSCRCFmese6)) {{number_format($sistemaBasic->entrataDSCRCFmese6,0,',','.')}} €@endif </td> 
                                        </tr> 
                                        <tr>
                                            <td class="text-center">Uscite di cassa | </td>  
                                            <td class="text-center">@if(isset($sistemaBasic->uscitaDSCRCFmese1)) {{number_format($sistemaBasic->uscitaDSCRCFmese1,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->uscitaDSCRCFmese2)) {{number_format($sistemaBasic->uscitaDSCRCFmese2,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->uscitaDSCRCFmese3)) {{number_format($sistemaBasic->uscitaDSCRCFmese3,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->uscitaDSCRCFmese4)) {{number_format($sistemaBasic->uscitaDSCRCFmese4,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->uscitaDSCRCFmese5)) {{number_format($sistemaBasic->uscitaDSCRCFmese5,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->uscitaDSCRCFmese6)) {{number_format($sistemaBasic->uscitaDSCRCFmese6,0,',','.')}} €@endif </td> 
                                        </tr> 
                                    </tbody>
                                </table>
                            </div>
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
                                            <td class="text-center">Rate finanziamenti + Oneri Finanziari |</td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese1)) {{number_format($sistemaBasic->rimborsoDSCRmese1,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese2)) {{number_format($sistemaBasic->rimborsoDSCRmese2,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese3)) {{number_format($sistemaBasic->rimborsoDSCRmese3,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese4)) {{number_format($sistemaBasic->rimborsoDSCRmese4,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese5)) {{number_format($sistemaBasic->rimborsoDSCRmese5,0,',','.')}} €@endif </td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese6)) {{number_format($sistemaBasic->rimborsoDSCRmese6,0,',','.')}} €@endif </td>  
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <h3>Risultato: {{$sistemaBasic->alertDSCR}}</h3>
                            <hr>
                        </div>
                    </div>

                    <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title">
                                <h2>ANALISI DEI RAPPORTI CON CREDITORI PUBBLICI</h2>
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
                                            <td style="width: 50%" >Debito IVA scaduto non versato relativo all'ultima liquidazione IVA trimestrale &nbsp;</td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese1)) {{number_format($sistemaBasic->agenziaEntrate1,0,',','.')}} €@endif </td> 
                                        </tr>  
                                        <tr>
                                            <td style="width: 50%" >Volume d'affari riferito al trimestre dell'ultima liquidazione IVA &nbsp;</td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese2)) {{number_format($sistemaBasic->agenziaEntrate2,0,',','.')}} €@endif </td> 
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" >Volume d’affari relativo alla dichiarazione IVA dell’anno precedente &nbsp;</td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese3)) {{number_format($sistemaBasic->agenziaEntrate3,0,',','.')}} € @else {{$ValoreProduzioneRicaviVenditePrestazioni}} € @endif </td> 
                                        </tr> 
                                        <tr>
                                            <td style="width: 50%" >Debito IVA scaduto / Volume d'affari dell' ultima liquidazione IVA &nbsp;</td> 
                                            <td class="text-center">@if(isset($sistemaBasic->rimborsoDSCRmese4)) {{number_format($sistemaBasic->agenziaEntrate4,2,',','.')}} %@endif </td> 
                                        </tr> 
                                    </tbody>
                                </table>
                                <h3>Alert:  {{$sistemaBasic->alertAgenziaEntrate}} </h3>

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
                                                <td style="width: 50%" class="text-center"> @if(isset($sistemaBasic->INPS1)) {{number_format($sistemaBasic->INPS1,0,',','.')}} € @endif</td> 
                                            </tr>  
                                            <tr>
                                                <td style="width: 50%" >Totale contributi previdenziali dell’anno precedente</td> 
                                                <td style="width: 50%" class="text-center"> @if(isset($sistemaBasic->INPS2)) {{number_format($sistemaBasic->INPS2,0,',','.')}} € @endif</td>                                            </tr> 
                                            <tr>
                                                <td style="width: 50%" >Contributi previdenziali NON versati da oltre 6 mesi / Totale contributi previdenziali anno precedente</td> 
                                                <td style="width: 50%" class="text-center"> @if(isset($sistemaBasic->INPS3)) {{number_format($sistemaBasic->INPS3,2,',','.')}} % @endif</td>                                            </tr> 
                                        </tbody>
                                    </table>
                                    <h3>Alert:  {{$sistemaBasic->alertINPS}} </h3>
                                </div>
                            <h4>3. Agente della riscossione</h4>
                            <p>L’Agente della Riscossione è tenuto ad attivarsi quando la sommatoria dei crediti affidati per la riscossione auto-dichiarati o definitivamente accertati e
                            scaduti da oltre novanta giorni superi la soglia di 500.000 € per le imprese individuali e quella di 1.000.000 € per le imprese collettive.</p>
                            <h4>Totale crediti affidati per la riscossione scaduti da oltre 90 giorni</h4>
                            @if(isset($sistemaBasic->riscossione)) {{$sistemaBasic->riscossione}} € @endif                            
                            <h3>Alert: {{$sistemaBasic->alertRiscossione}}</h3>
                        </div>
                    </div>

                    <div class="card">
                        <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> class="card-header border-bottom-0">
                           <div class="card-title">
                            <h2>ACCESSO ALLE MISURE PREMIALI</h2>
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
                                            <td style="width: 50%" >Debiti per retribuizioni scaduti da più di 60 giorni &nbsp;</td> 
                                            <td style="width: 50%" class="text-center">@if(isset($sistemaBasic->retribuzioni1)) {{number_format($sistemaBasic->retribuzioni1,0,',','.')}} € @endif</td> 
                                        </tr>  
                                        <tr>
                                            <td style="width: 50%" >Totale delle retribuzioni mensili &nbsp;</td> 
                                            <td style="width: 50%" class="text-center">@if(isset($sistemaBasic->retribuzioni2)) {{number_format($sistemaBasic->retribuzioni2,0,',','.')}} € @else {{$salariStipendi}} € @endif</td> 
                                        </tr> 
                                        <tr>
                                            <td style="width: 50%" >Debiti per retribuzioni scaduti / Totale retribuzioni mensili &nbsp;</td> 
                                            <td style="width: 50%" class="text-center">@if(isset($sistemaBasic->retribuzioni2)) {{$sistemaBasic->retribuzioni2}} % @endif </td> 
                                        </tr> 
                                    </tbody>
                                </table>
                                <h3>Alert:  {{$sistemaBasic->alertRetribuzioni}} </h3>
                            </div>
                            <h4 <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?>>b. Debiti verso fornitori &nbsp;</h4>
                                <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> class="table-responsive">
                                <table  class="table table-bordered text-nowrap border-bottom" >
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 50%" >Debiti verso fornitori scaduti da più di 120 giorni &nbsp;</td> 
                                            <td style="width: 50%" class="text-center">@if(isset($sistemaBasic->fornitori1)) {{$sistemaBasic->fornitori1}} € @endif</td> 
                                        </tr>  
                                        <tr>
                                            <td style="width: 50%" >Debiti verso fornitori non scaduti &nbsp;</td> 
                                            <td style="width: 50%" class="text-center">@if(isset($sistemaBasic->fornitori2)) {{$sistemaBasic->fornitori2}} € @endif</td> 
                                        </tr>
                                        </tbody>
                                    </table>
                                    <h3>Alert:  {{$sistemaBasic->alertFornitori}} </h3>
                                </div>
                            <h4 <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?>>c. Superamento soglie degli indici elaborati da CNDCEC</h4>
                            <p <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?>>Qualora il patrimonio netto fosse negativo il debitore non potrebbe accedere alle misure premiali.
                                Se il Patrimonio Netto non risulterà negativo dovrà essere verificato che il DSCR a 6 mesi sia pari o superiore a 1, in questo caso il debitore non sarà
                                considerato a rischio. Se invece il DSCR a 6 mesi risultasse inferiore a 1 scatterebbe l'Alert.
                                Nel caso in cui non sia possibile determinare il valore del DSCR a 6 mesi, basterà verificare che i 5 indici definiti nell’articolo 13 comma 2 risultino tutti
                                all’interno delle soglie definite dal CNDCEC.
                                Se tutti e 5 gli indici del CNDCEC supereranno i valori soglia scatterà l’alert, altrimenti il debitore non verrà considerato a rischio.</p>
                            <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?> class="table-responsive">                                        
                                <table class="table table-bordered text-nowrap border-bottom" id="basic-datatable">
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Patrimonio Netto Negativo &nbsp;</td>
                                            <td id="PatrimonioNetto" style="width: 50%" class="text-center">
                                                @if($dataAnalisisBasic['Patrimonio_Netto'] < 0)
                                                Si
                                                @else
                                                No
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center">Indice DSCR &nbsp;</td>
                                            <td id="AlertDSCR" style="width: 50%" class="text-center">
                                                @if($sistemaBasic->DSCR == 'si') {{$sistemaBasic->alertDSCR}} @else {{'No'}} @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-center">Indici CNDCEC &nbsp;</td>
                                            <td class="text-center">@if($valutazioneAllertaBasic) Azienda a Rischio @else Azienda non a Rischio @endif</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div <?php if($dataAnalisisBasic['Patrimonio_Netto'] < 0){ echo "style='display: none'"; } ?>    class="card">
                        <div class="card-header border-bottom-0">
                            <div class="card-title"><h2>Recap Alerts</h2></div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap border-bottom" >
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 50%" class="text-center"><h3>Alert Agenzia Entrate</h3></td>
                                            <td style="width: 50%" class="text-center" id="recapAgenziaEntrate"><h3>{{$sistemaBasic->alertAgenziaEntrate}} </h3>                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center"><h3>Alert INPS</h3></td>
                                            <td style="width: 50%" class="text-center" id="recapINPS"> <h3>{{$sistemaBasic->alertINPS}}</h3> </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center"><h3>Alert Agente della riscossione</h3></td>
                                            <td style="width: 50%" class="text-center" id="recapRiscossione"><h3>{{$sistemaBasic->alertRiscossione}} </h3></td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center"><h3>Alert Debiti per retribuzioni</h3></td>
                                            <td style="width: 50%" class="text-center" id="recapRetribuzioni"><h3>{{$sistemaBasic->alertRetribuzioni}} </h3></td>
                                        </tr>
                                        <tr>
                                            <td style="width: 50%" class="text-center"><h3>Alert Debiti verso fornitori</h3></td>
                                            <td style="width: 50%" class="text-center" id="recapFornitori"><h3>{{$sistemaBasic->alertFornitori}}</h3></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-forms.post>

</div>



