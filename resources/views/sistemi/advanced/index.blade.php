@extends('backend.layouts.app')

@section('content')

    @if(Session::has('success_message'))
        <div class="alert alert-success">
            <span class="glyphicon glyphicon-ok"></span>
            {!! session('success_message') !!}

            <button type="button" class="close" data-dismiss="alert" aria-label="close">
                <span aria-hidden="true">&times;</span>
            </button>

        </div>
    @endif

    <div class="panel panel-default">

        <div class="panel-heading clearfix">

            <div class="pull-left">
                <h4 class="mt-5 mb-5">Analisis</h4>
            </div>

        </div>

        @if(count($analisis) == 0)
            <div class="panel-body text-center">
                <h4>No Analisis Available.</h4>
            </div>
        @else
            <div class="panel-body panel-body-with-table">
                <div class="table-responsive">

                    <table class="table table-striped ">
                        <thead>
                        <tr>
                            <th>Id</th>
                            <th>Account</th>
                            <th>Bilancio</th>

                            <th>Tipo Analisi</th>
                            <th>Data Creazione</th>
                            <th>Data Ultima Modifica</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($analisis as $analisi)
                            <tr>
                                <td>{{ $analisi->id }}</td>
                                <td>{{ optional($analisi->account)->Name }}</td>
                                <td>{{ optional($analisi->bilanci)->name }}</td>

                                <td>{{ optional($analisi->analisisType)->name }}</td>
                                <td>{{ $analisi->created_at }}</td>
                                <td>{{ $analisi->updated_at }}</td>

                                <td>
                                        <div class="btn-group btn-group-xs pull-right" role="group">

                                            <a href="{{ route('admin.sistemi.advanced.advanced', $analisi->id ) }}" class="btn btn-info" title="Show Analisi">
                                                Mostra Analisi
                                            </a>

                                        </div>


                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>
            </div>

            <div class="panel-footer">
                {!! $analisis->render() !!}
            </div>

        @endif

    </div>
@endsection
