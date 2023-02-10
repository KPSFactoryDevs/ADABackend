
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


</style>

<h2 class="page-title">Centrale Rischi Andamentale | <span class="font-weight-normal text-muted ml-2">Key Performance Softwares S.r.l.</span></h2>
<hr>
<table>
    <tr>
        <th><h3 class="page-title">Inizio del periodo analizzato | <span class="font-weight-normal text-muted ml-2">{{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Inizio']}}</span></h3></th>
        <th>&nbsp;&nbsp;&nbsp;</th>
        <th><h3 class="page-title">Fine del periodo analizzato | <span class="font-weight-normal text-muted ml-2">{{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Fine']}}</span></h3></th>
    <tr>
</table>
<table>
    <th><h3 align="left" class="page-title">Data Analisi | {{date('Y-m-d')}}</h3></th>
    <th>&nbsp;&nbsp;&nbsp;</th>
    <th><div class="card-title"><h3>Punteggio CR</h3> <span class="font-weight-normal text-muted ml-2">{{number_format((float)str_replace(',', '.', $response['Scoring']['Panoramica']['FinalScore'])*10/10, 2, ',', '.')}} / 10</span><div></th>
    <th>&nbsp;&nbsp;&nbsp;</th>
</table>

                    <table class="table table-hover">
                        <tbody>

                        <tr>
                            <td><h4>Periodo di riferimento: </h4></td>
                            <td class="text-center">{{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Inizio']}} - {{$response['Scoring']['Panoramica']['PeriodoRiferimento']['Fine']}}</td>
                        </tr>

                        <tr>
                            <td><h4>N. Intermediari: </h4></td>
                            <td class="text-center">{{$response['Scoring']['Panoramica']['NumeroIntermediari']}}</td>
                        </tr>

                        <tr>
                            <td><h4>N° Posizioni Contestate: </h4></td>
                            <td class="text-center">{{$response['Scoring']['Panoramica']['NumeroPosizioniContestate']}}</td>
                        </tr>
                        </tbody>
                    </table>

                    <h2 align="center">Anomalie utilizzi</h2>
                    <table class="table table-hover">
                        <tbody>

                        <tr>
                            <td><h4>Tensione Finanziaria Utilizzi Autoliquidanti</h4></td>
                            @if($response['Scoring']['AnomalieUtilizzi']['TensioneAutoliquidanti'])
                                <td class='text-center' style='color:white; background-color: red'>Si</td>
                            @else
                                <td class='text-center' style='color:white;background-color: green'>No</td>
                            @endif
                        </tr>

                        <tr>
                            <td><h4>Tensione Finanziaria Utilizzi A Revoca</h4></td>
                            @if($response['Scoring']['AnomalieUtilizzi']['TensioneRevoca'])
                                <td class='text-center' style='color:white; background-color: red'>Si</td>
                            @else
                                <td class='text-center' style='color:white; background-color: green'>No</td>
                            @endif
                        </tr>

                        <tr>
                            <td><h4>Tensione Finanziaria Utilizzi A Scadenza</h4></td>
                            @if($response['Scoring']['AnomalieUtilizzi']['TensioneScadenza'])
                                <td class='text-center' style='color:white; background-color: red'>Si</td>
                            @else
                                <td class='text-center' style='color:white; background-color: green'>No</td>
                            @endif
                        </tr>
                        </tbody>
                    </table>



 
        <h2 align="center">Anomalie lievi</h2>

        <table class="table table-hover">

            <tbody>

            <tr>
                <td><h4>Impagati</h4></td>
                @if($response['Scoring']['AnomalieLievi']['Impagati'])
                    <td class='text-center' style='color:white; background-color: red'>Si</td>
                @else
                    <td class='text-center' style='color:white;background-color: green'>No</td>
                @endif
            </tr>

            <tr>
                <td><h4>Presenza Sconfini</h4></td>
                @if($response['Scoring']['AnomalieLievi']['Sconfini'])
                    <td class='text-center' style='color:white; background-color: red'>Si</td>
                @else
                    <td class='text-center' style='color:white;background-color: green'>No</td>
                @endif
            </tr>

            @if($response['Scoring']['AnomalieLievi']['Sconfini'])

                <tr>
                    <td><h4>N° Sconfini Autoliquidanti</h4></td>
                    <td class="text-center">
                    {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI AUTOLIQUIDANTI'] }}

                    <!--    @if(isset($response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI AUTOLIQUIDANTI']))
                        {{$response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI AUTOLIQUIDANTI']}}
                    @else
                        0
@endif -->
                    </td>
                </tr>

                <tr>
                    <td><h4>N° Sconfini A Revoca</h4></td>
                    <td class="text-center">
                    {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A REVOCA'] }}

                    <!--    @if(isset($response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A REVOCA']))
                        {{$response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A REVOCA']}}
                    @else
                        0
@endif -->
                    </td>
                </tr>

                <tr>
                    <td><h4>N° Sconfini A Scadenza</h4></td>
                    <td class="text-center">
                    {{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A SCADENZA'] }}

                    <!--    @if(isset($response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A SCADENZA']))
                        {{$response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A SCADENZA']}}
                    @else
                        0
@endif -->
                    </td>
                </tr>
            @endif
            </tbody>
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





