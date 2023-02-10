<!DOCTYPE html>
<html>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap CSS -->
 
    </head>
    <body>

        <p class="header-info text-right text-header pt-0 mt-0">
            Ditta/Denominazione/Ragione sociale: <b class="header-info">{{ $datiImpresa['ragione_sociale'] }}</b><br>
            Tipologia Impresa: <b class="header-info">{{ $datiImpresa['tipologia_impresa'] }}</b><br>
            Settore Attività: <b class="header-info">{{ $datiImpresa['settore'] }}</b>
        </p>

        <hr>

        <table style="width:100%;">
            <tr>
                <th colspan="12" class="heading">DATI IMPRESA</th>
            </tr>
            <tr>
                <td style="width:40%" class="dati_impresa">DITTA/DENOMINAZIONE/RAGIONE SOCIALE</td>
                <td style="width:60%" colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $datiImpresa['ragione_sociale'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">TIPOLOGIA IMPRESA</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $datiImpresa['tipologia_impresa'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">SETTORE ATTIVITÀ</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $datiImpresa['settore'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">DATA CHIUSURA ESERCIZIO PRECEDENTE</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $datiImpresa['data_chiusura'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">ANNO CHIUSURA ESERCIZIO PRECEDENTE</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $datiImpresa['data_ultima'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">ELABORAZIONE A CURA DI</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">KPS Factory</td>
            </tr>
            <tr>
                <td class="dati_impresa">LUOGO ELABORAZIONE</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">Palermo</td>
            </tr>
        </table>



        <table style="width:100%;">
            <tr>
                <th colspan="4" class="size-12">Indici Basic</th>
                <th colspan="4" class="size-12">{{ $datiImpresa['data_ultima'] }}</th>
                <th colspan="4" class="size-12">Fuori Soglia</th>
            </tr>
            @foreach($datiImpresa['bilancioAnalisi']['Indici']['Basic'] as $key => $singleStatoPatrimonialeAttivo)
                @if(isset($singleStatoPatrimonialeAttivo['value']))
                <tr>
                    <td colspan="4" class="dati_impresa">{{ $key }}</td>
                    <td colspan="4" class="dati_impresa bgcolor-dati-impresa">{{ $singleStatoPatrimonialeAttivo['value'] }}</td>
                    <td colspan="4" class="dati_impresa bgcolor-dati-impresa">@if($singleStatoPatrimonialeAttivo['fuoriSoglia'] == true) Si @else No @endif</td>
                </tr>
                @endif
            @endforeach
            <tr>
                <th colspan="4" class="dati_impresa"><b>Indice CNDCEC<b></th>
                <th colspan="4" class="dati_impresa bgcolor-dati-impresa"><b>{{ $datiImpresa['bilancioAnalisi']['Indici']['Basic']['Indice CNDCEC'] }}</b></th>
                <th colspan="4" class="dati_impresa bgcolor-dati-impresa"></th>
            </tr>
        </table>



        <table style="width:100%" class="mt-5">
            <tr>
                <th colspan="6" class="size-12">Alert Questionari Qualitativi</th>
                <th colspan="6" class="size-12">{{ $datiImpresa['data_ultima'] }}</th>
            </tr>
            @foreach($datiImpresa['bilancioAnalisi']['Questionari'] as $key => $alertQuestionari)
                <tr>
                    <td colspan="6" class="dati_impresa">{{ $key }}</td>
                    @if(isset($alertQuestionari->alert) && $alertQuestionari->alert === 'Azienda a rischio')
                        <td colspan="6" class="dati_impresa" style="background-color: #b31317;color: #ffffff;">@if(isset($alertQuestionari->alert)) {{ $alertQuestionari->alert }} @else NA @endif</td>
                    @elseif(!isset($alertQuestionari->alert))
                        <td colspan="6" class="dati_impresa">@if(isset($alertQuestionari->alert)) {{ $alertQuestionari->alert }} @else NA @endif</td>
                    @else
                        <td colspan="6" class="dati_impresa" style="background-color: #99ff66;">@if(isset($alertQuestionari->alert)) {{ $alertQuestionari->alert }} @else NA @endif</td>
                    @endif
                </tr>
            @endforeach
        </table>

    <style>

        table {
            border-right: dotted black;
            border-left: dotted black;
        }

        th, td  {
            border-top: dotted black;
            border-bottom: dotted black;
            padding: 5px;
        }

        .dati_impresa {
            font-size: 12px;
            font-weight: 400!important;
        }

        .heading {
            text-align: center;
            padding: 5px;
            font-size: 12px;
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

    </body>
</html>
