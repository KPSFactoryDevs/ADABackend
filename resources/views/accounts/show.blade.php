@inject('model', '\App\Models\Account')

@extends('backend.layouts.app')

@section('title', __('Analisi Centrale Rischi'))

@section('content')

    <?php

    ?>


    <div class="panel panel-default">
        <div class="panel-heading clearfix">
            <div class="pull-right">
            </div>
        </div>
        <div class="panel-body">
            <dl class="dl-horizontal">
                <h1>{{$account[0]->Name}}</h1>
                <h1 class="text-center">Lista bilanci</h1>
                <table class="table table-bordered table-light ">
                    <thead class="thead-dark">
                    <tr>
                        <th class="text-center" scope="col">Anno</th>
                        <th class="text-center" scope="col">Tipo bilancio</th>
                        <th class="text-center" scope="col">Vedi bilancio</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($bilanci as $data=>$value)
                        <?php
                            $coso = json_decode($value->json_data_anag,true);
                            $cosa = array_pop($coso);
                            $anno = $value->current_year[0].$value->current_year[1].$value->current_year[2].$value->current_year[3];
                        ?>
                        <tr>
                            <td class="text-center round-table" style="width: 33%; font-weight: bold" scope="row">{{$anno}}</td>
                            <td class="text-center round-table" style="width: 33%; font-weight: bold" scope="row">{{$cosa}}</td>
                            <td class="text-center round-table" style="width: 33%" scope="row">
                                <a href="{{route('admin.bilanci.bilancio.show', $value->id)}}"  title="Mostra bilancio">
                                    <span class="btn btn-success" aria-hidden="true"></span>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <h1 class="text-center">Lista CR</h1>
                <table class="table table-bordered table-light ">
                    <thead class="thead-dark">
                    <tr>
                        <th class="text-center" scope="col">Anno</th>
                        <th class="text-center" scope="col">Mese</th>
                        <th class="text-center" scope="col">Espandi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cleanCR as $anno=>$arrayMese)
                        @foreach($arrayMese as $mese=>$value)
                        <tr>
                            <td class="text-center round-table" style="width: 33%; font-weight: bold" scope="row">{{$anno}}</td>
                            <td class="text-center round-table" style="width: 33%; font-weight: bold" scope="row">{{$mese}}</td>
                            <td class="text-center round-table" style="width: 33%" scope="row">
                                <a href="{{route('admin.accounts.account.showCR', 'id='.$value)}}"  title="Mostra bilancio">
                                    <span class="btn btn-success" aria-hidden="true"></span>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>

            </dl>
        </div>
    </div>
@endsection
