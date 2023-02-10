<!DOCTYPE html>
<html>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <title>Report Allerta</title>
    </head>
    <body>

        <p class="header-info text-right text-header pt-0 mt-0">
            Ditta/Denominazione/Ragione sociale: <b class="header-info">{{ $dati['ragione_sociale'] }}</b><br>
            Tipologia Impresa: <b class="header-info">{{ $dati['tipologia_impresa'] }}</b><br>
            Settore Attività: <b class="header-info">{{ $dati['settore'] }}</b>
        </p>

        <table style="width:100%;margin-bottom: 44rem;">
            <tr>
                <th colspan="12" class="heading">DATI IMPRESA</th>
            </tr>
            <tr>
                <td style="width:40%" class="dati_impresa">DITTA/DENOMINAZIONE/RAGIONE SOCIALE</td>
                <td style="width:60%" colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $dati['ragione_sociale'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">TIPOLOGIA IMPRESA</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $dati['tipologia_impresa'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">SETTORE ATTIVITÀ</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $dati['settore'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">DATA CHIUSURA ESERCIZIO PRECEDENTE</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $dati['data_chiusura'] }}</td>
            </tr>
            <tr>
                <td class="dati_impresa">ANNO CHIUSURA ESERCIZIO PRECEDENTE</td>
                <td colspan="11" class="dati_impresa bgcolor-dati-impresa">{{ $dati['data_ultima'] }}</td>
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


        <p class="header-info text-right text-header pt-0 mt-0">
            Ditta/Denominazione/Ragione sociale: <b class="header-info">{{ $dati['ragione_sociale'] }}</b><br>
            Tipologia Impresa: <b class="header-info">{{ $dati['tipologia_impresa'] }}</b><br>
            Settore Attività: <b class="header-info">{{ $dati['settore'] }}</b>
        </p>


        <table style="width:100%;margin-bottom: 49rem;">
            <tr>
                <th colspan="6" class="size-12">Indici Basic</th>
                <th colspan="6" class="size-12">{{ $dati['data_ultima'] }}</th>
            </tr>
            @foreach($dati['GeneralScore'] as $key => $singleStatoPatrimonialeAttivo)
                @if(isset($singleStatoPatrimonialeAttivo['value']))
                <tr>
                    <td colspan="6" class="dati_impresa">{{ $key }}</td>
                    <td colspan="6" class="dati_impresa bgcolor-dati-impresa">{{ $singleStatoPatrimonialeAttivo['value'] }}</td>
                </tr>
                @endif
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
        .bgcolor-alert {
            background-color: #99ff66;
        }
    </style>

    </body>
</html>
