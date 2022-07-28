@extends('layouts.app')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading clearfix">

        <span class="pull-left">
            <h4 class="mt-5 mb-5">{{ isset($title) ? $title : 'Cr' }}</h4>
        </span>

        <div class="pull-right">

            <form method="POST" action="{!! route('crs.cr.destroy', $cr->id) !!}" accept-charset="UTF-8">
            <input name="_method" value="DELETE" type="hidden">
            {{ csrf_field() }}
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('crs.cr.index') }}" class="btn btn-primary" title="Show All Cr">
                        <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('crs.cr.create') }}" class="btn btn-success" title="Create New Cr">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                    </a>
                    
                    <a href="{{ route('crs.cr.edit', $cr->id ) }}" class="btn btn-primary" title="Edit Cr">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </a>

                    <button type="submit" class="btn btn-danger" title="Delete Cr" onclick="return confirm(&quot;Click Ok to delete Cr.?&quot;)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>

    </div>

    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt>Created At</dt>
            <dd>{{ $cr->created_at }}</dd>
            <dt>Updated At</dt>
            <dd>{{ $cr->updated_at }}</dd>
            <dt>Account</dt>
            <dd>{{ optional($cr->account)->Name }}</dd>
            <dt>Anno</dt>
            <dd>{{ $cr->anno }}</dd>
            <dt>Mese</dt>
            <dd>{{ $cr->mese }}</dd>
            <dt>Nome Banca</dt>
            <dd>{{ $cr->nome_banca }}</dd>
            <dt>Sezione</dt>
            <dd>{{ $cr->sezione }}</dd>
            <dt>Categoria</dt>
            <dd>{{ $cr->categoria }}</dd>
            <dt>Accordato</dt>
            <dd>{{ $cr->accordato }}</dd>
            <dt>Accordato Operativo</dt>
            <dd>{{ $cr->accordato_operativo }}</dd>
            <dt>Utilizzato</dt>
            <dd>{{ $cr->utilizzato }}</dd>
            <dt>Durata Residua</dt>
            <dd>{{ $cr->durata_residua }}</dd>
            <dt>Durata Originaria</dt>
            <dd>{{ $cr->durata_originaria }}</dd>
            <dt>Localizzazione</dt>
            <dd>{{ $cr->localizzazione }}</dd>
            <dt>Divisa</dt>
            <dd>{{ $cr->divisa }}</dd>
            <dt>Tipo Garanzia</dt>
            <dd>{{ $cr->tipo_garanzia }}</dd>
            <dt>Stato Rapporto</dt>
            <dd>{{ $cr->stato_rapporto }}</dd>
            <dt>Tipo Attivita</dt>
            <dd>{{ $cr->tipo_attivita }}</dd>
            <dt>Ruolo Affidato</dt>
            <dd>{{ $cr->ruolo_affidato }}</dd>
            <dt>Import Export</dt>
            <dd>{{ $cr->import_export }}</dd>

        </dl>

    </div>
</div>

@endsection