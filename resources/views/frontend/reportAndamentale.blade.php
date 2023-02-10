<style>
* {
  box-sizing: border-box;
}
table {
    width:100%;
}
.row {
  margin-left:-5px;
  margin-right:-5px;
}

.column {
  float: left;
  width: 50%;
  padding: 5px;
}

/* Clearfix (clear floats) */
.row::after {
  content: "";
  clear: both;
  display: table;
}

table {
  border-collapse: collapse;
  border-spacing: 0;
  width: 100%;
  padding: 0;
  border: 1px solid #ddd;
}

th, td {
  text-align: left;
  padding: 5px;
}

tr:nth-child(even) {
  background-color: #f2f2f2;
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





