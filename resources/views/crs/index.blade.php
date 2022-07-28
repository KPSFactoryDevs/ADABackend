@extends('layouts.app')

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
                <h4 class="mt-5 mb-5">Crs</h4>
            </div>

            <div class="btn-group btn-group-sm pull-right" role="group">
                <a href="{{ route('crs.cr.create') }}" class="btn btn-success" title="Create New Cr">
                    <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                </a>
            </div>

        </div>
        
        @if(count($crs) == 0)
            <div class="panel-body text-center">
                <h4>No Crs Available.</h4>
            </div>
        @else
        <div class="panel-body panel-body-with-table">
            <div class="table-responsive">

                <table class="table table-striped ">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Anno</th>
                            <th>Mese</th>
                            <th>Nome Banca</th>
                            <th>Sezione</th>
                            <th>Categoria</th>
                            <th>Accordato</th>
                            <th>Accordato Operativo</th>
                            <th>Utilizzato</th>
                            <th>Durata Residua</th>
                            <th>Durata Originaria</th>
                            <th>Localizzazione</th>
                            <th>Divisa</th>
                            <th>Tipo Garanzia</th>
                            <th>Stato Rapporto</th>
                            <th>Tipo Attivita</th>
                            <th>Ruolo Affidato</th>
                            <th>Import Export</th>

                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($crs as $cr)
                        <tr>
                            <td>{{ optional($cr->account)->Name }}</td>
                            <td>{{ $cr->anno }}</td>
                            <td>{{ $cr->mese }}</td>
                            <td>{{ $cr->nome_banca }}</td>
                            <td>{{ $cr->sezione }}</td>
                            <td>{{ $cr->categoria }}</td>
                            <td>{{ $cr->accordato }}</td>
                            <td>{{ $cr->accordato_operativo }}</td>
                            <td>{{ $cr->utilizzato }}</td>
                            <td>{{ $cr->durata_residua }}</td>
                            <td>{{ $cr->durata_originaria }}</td>
                            <td>{{ $cr->localizzazione }}</td>
                            <td>{{ $cr->divisa }}</td>
                            <td>{{ $cr->tipo_garanzia }}</td>
                            <td>{{ $cr->stato_rapporto }}</td>
                            <td>{{ $cr->tipo_attivita }}</td>
                            <td>{{ $cr->ruolo_affidato }}</td>
                            <td>{{ $cr->import_export }}</td>

                            <td>

                                <form method="POST" action="{!! route('crs.cr.destroy', $cr->id) !!}" accept-charset="UTF-8">
                                <input name="_method" value="DELETE" type="hidden">
                                {{ csrf_field() }}

                                    <div class="btn-group btn-group-xs pull-right" role="group">
                                        <a href="{{ route('crs.cr.show', $cr->id ) }}" class="btn btn-info" title="Show Cr">
                                            <span class="glyphicon glyphicon-open" aria-hidden="true"></span>
                                        </a>
                                        <a href="{{ route('crs.cr.edit', $cr->id ) }}" class="btn btn-primary" title="Edit Cr">
                                            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                        </a>

                                        <button type="submit" class="btn btn-danger" title="Delete Cr" onclick="return confirm(&quot;Click Ok to delete Cr.&quot;)">
                                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                        </button>
                                    </div>

                                </form>
                                
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        </div>

        <div class="panel-footer">
            {!! $crs->render() !!}
        </div>
        
        @endif
    
    </div>
@endsection