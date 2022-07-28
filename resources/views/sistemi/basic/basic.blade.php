@extends('backend.layouts.app')

@section('content')
    <div class="panel panel-default">
    <div class="panel-body">
        <dl class="dl-horizontal">
            <h2 class="text-center">Sistema Allerta Basic</h2>

            <table class="table table-bordered table-light smaller-table ">
                <thead class="thead-dark">
                <tr>
                    <th class="text-center" scope="col">Indice</th>
                    <th class="text-center" scope="col">Valore</th>
                </tr>
                </thead>
                <tbody>
                @foreach($dataAnalisis as $data=>$val)
                    <tr>
                        <td class="text-center round-table" style="width: 50%" scope="row"><?=str_replace("_", " ", $data);?></td>
                        <td class="text-center round-table" style="width: 50%" scope="row"><?= $val = ($data == 'PN_NEGATIVO' )? (number_format((float)$val, 2, ',', '.')).' €' : $val?></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </dl>
    </div>
@endsection
