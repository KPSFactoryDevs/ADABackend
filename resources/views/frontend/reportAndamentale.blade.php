
<style>

    table {
        border-right: dotted black;
        border-left: dotted black;
        width:100%;
    }

    th, td  {
        border-top: dotted black;
        border-bottom: dotted black;
        padding: 5px;
        font-size: 12px;
        font-weight: 400!important;
    }

    .dati_impresa {
        font-size: 12px;
        font-weight: 400!important;
    }

    .heading {
        text-align: left;
        padding: 5px;
        font-size: 14px;
        font-weight:bold;
    }
    .header-info {
        font-size: 10px;
        vertical-align: top;
    }
    .bgcolor-dati-impresa {
        background-color: #ccffff;
    }
    .size-12 {
        font-size: 12px;
    }

</style>

<h2>Report Centrale Rischi Andamentale</h2>
<hr>



<table style="width:100%;margin-bottom:50px;">
    <tr>
        <th colspan="12" class="heading">DATI GENERALI - Analisi del {{date('Y-m-d')}}</th>
    </tr>
    <tr>
        <td style="width:40%" class="dati_impresa">Periodo analizzato</td>
        <td style="width:60%" colspan="11" class="dati_impresa bgcolor-dati-impresa">{{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Inizio']}} - {{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Fine']}}</td>
    </tr>
    <tr>
        <td style="width:40%" class="dati_impresa">Scoring Finale</td>
        <td style="width:60%" colspan="11" class="dati_impresa bgcolor-dati-impresa">{{number_format((float)str_replace(',', '.', $response['Scoring']['Panoramica']['FinalScore'])*10/10, 2, ',', '.')}} / 10</td>
    </tr>

    <tr>
        <td style="width:40%" class="dati_impresa">N. Intermediari:</td>
        <td style="width:60%" colspan="11" class="dati_impresa bgcolor-dati-impresa">{{$response['Scoring']['Panoramica']['NumeroIntermediari']}}</td>
    </tr>

    <tr>
        <td style="width:40%" class="dati_impresa">N. Posizioni Contestate:</td>
        <td style="width:60%" colspan="11" class="dati_impresa bgcolor-dati-impresa">{{$response['Scoring']['Panoramica']['NumeroPosizioniContestate']}}</td>
    </tr>
</table>




<table style="width:100%;margin-bottom:50px;">
    <tr>
        <th colspan="12" class="heading">Anomalie Utilizzi</th>
    </tr>
    <tr>
        <td style="width:40%" class="dati_impresa">Tensione Finanziaria Utilizzi Autoliquidanti</td>
        @if($response['Scoring']['AnomalieUtilizzi']['TensioneAutoliquidanti'])
            <td class='text-center' colspan="11"  style='color:white; background-color: red'>Si</td>
        @else
            <td class='text-center' colspan="11"  style='color:white;background-color: green'>No</td>
        @endif  </tr>
    <tr>
        <td style="width:40%" class="dati_impresa">Tensione Finanziaria Utilizzi A Revoca</td>
        @if($response['Scoring']['AnomalieUtilizzi']['TensioneRevoca'])
            <td class='text-center' colspan="11"  style='color:white; background-color: red'>Si</td>
        @else
            <td class='text-center' colspan="11"  style='color:white; background-color: green'>No</td>
        @endif
    </tr>

    <tr>
        <td style="width:40%" class="dati_impresa">Tensione Finanziaria Utilizzi A Scadenza:</td>
        @if($response['Scoring']['AnomalieUtilizzi']['TensioneScadenza'])
            <td class='text-center' colspan="11"  style='color:white; background-color: red'>Si</td>
        @else
            <td class='text-center' colspan="11"  style='color:white; background-color: green'>No</td>
        @endif
    </tr>


</table>








<table style="width:100%;margin-bottom:50px;">
    <tr>
        <th colspan="12" class="heading">Anomalie Lievi</th>
    </tr>
    <tr>
        <td style="width:40%" class="dati_impresa">Impagati</td>
        @if($response['Scoring']['AnomalieLievi']['Impagati'])
            <td class='text-center' colspan="11"  style='color:white; background-color: red'>Si</td>
        @else
            <td class='text-center' colspan="11"  style='color:white;background-color: green'>No</td>
        @endif  </tr>
    <tr>
        <td style="width:40%" class="dati_impresa">Presenza Sconfini</td>
        @if($response['Scoring']['AnomalieLievi']['Sconfini'])
            <td class='text-center' colspan="11"  style='color:white; background-color: red'>Si</td>
        @else
            <td class='text-center' colspan="11"  style='color:white; background-color: green'>No</td>
        @endif
    </tr>

    @if($response['Scoring']['AnomalieLievi']['Sconfini'])

        <tr>
            <td style="width:40%" class="dati_impresa">N° Sconfini Autoliquidanti:</td>
            <td> {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI AUTOLIQUIDANTI'] }}</td>
        </tr>

        <tr>
            <td style="width:40%" class="dati_impresa">N° Sconfini a Revoca:</td>
            <td> {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A REVOCA'] }}</td>
        </tr>

        <tr>
            <td style="width:40%" class="dati_impresa">N° Sconfini a Scadenza:</td>
            <td> {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A SCADENZA'] }}</td>
        </tr>
    @endif

</table>






        <h2 align="center">Anomalie quasi pregiudizievoli</h2>

        <table class="table table-hover">

            <tbody>

            <tr>
                <td><h4>Sconfinamenti entro 90gg</h4></td>
                @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroNovantaGiorni'])
                    <td class='text-center' style='color:white; background-color: red'>Si</td>
                @else
                    <td class='text-center' style='color:white;background-color: green'>No</td>
                @endif
            </tr>

            <tr>
                <td><h4>Sconfinamenti oltre 90 gg ed entro 180</h4></td>
                @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroCentoOttantaGiorni'])
                    <td class='text-center' style='color:white; background-color: red'>Si</td>
                @else
                    <td class='text-center' style='color:white;background-color: green'>No</td>
                @endif
            </tr>

            <tr>
                <td><h4>Sconfinamenti oltre 180gg</h4></td>
                @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniOltreCentoOttantaGiorni'])
                    <td class='text-center' style='color:white; background-color: red'>Si</td>
                @else
                    <td class='text-center' style='color:white;background-color: green'>No</td>
                @endif
            </tr>

            </tbody>
        </table>


<h2 align="center">Anomalie pregiudizievoli</h2>

<table class="table table-hover">

    <tbody>

    <tr>
        <td><h4>Garanzie attivate con esito negativo</h4></td>
        @if($response['Scoring']['AnomaliePregiudizievoli']['GaranzieAttivateEsitoNegativo'])
            <td class='text-center' style='color:white; background-color: red'>Si</td>
        @else
            <td class='text-center' style='color:white;background-color: green'>No</td>
        @endif
    </tr>

    <tr>
        <td><h4>Sofferenze</h4></td>
        @if($response['Scoring']['AnomaliePregiudizievoli']['Sofferenze'])
            <td class='text-center' style='color:white; background-color: red'>Si</td>
        @else
            <td class='text-center' style='color:white;background-color: green'>No</td>
        @endif
    </tr>

    <tr>
        <td><h4>Presenza crediti passati a perdita</h4></td>
        @if($response['Scoring']['AnomaliePregiudizievoli']['CreditiPassatiPerdita'])
            <td class='text-center' style='color:white; background-color: red'>Si</td>
        @else
            <td class='text-center' style='color:white;background-color: green'>No</td>
        @endif
    </tr>

    </tbody>
</table>




