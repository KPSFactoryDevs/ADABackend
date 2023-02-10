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


        <table style="width:100%;margin-bottom: 49rem;">
       <h3>Scoring per Sezione</h3>
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
