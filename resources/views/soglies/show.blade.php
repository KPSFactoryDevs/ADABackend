@extends('backend.layouts.app')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading clearfix">

        <span class="pull-left">
            <h4 class="mt-5 mb-5">{{ isset($title) ? $title : 'Soglie' }}</h4>
        </span>

        <div class="pull-right">

            <form method="POST" action="{!! route('admin.soglies.soglie.destroy', $soglie->id) !!}" accept-charset="UTF-8">
            <input name="_method" value="DELETE" type="hidden">
            {{ csrf_field() }}
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.soglies.soglie.index') }}" class="btn btn-primary" title="Show All Soglie">
                        <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('admin.soglies.soglie.create') }}" class="btn btn-success" title="Create New Soglie">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('admin.soglies.soglie.edit', $soglie->id ) }}" class="btn btn-primary" title="Edit Soglie">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </a>

                    <button type="submit" class="btn btn-danger" title="Delete Soglie" onclick="return confirm(&quot;Click Ok to delete Soglie.?&quot;)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>

    </div>

    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt>Rischio Elevato</dt>
            <dd>{{ $soglie->rischio_elevato }}</dd>
            <dt>Situazione Critica</dt>
            <dd>{{ $soglie->situazione_critica }}</dd>
            <dt>Buono</dt>
            <dd>{{ $soglie->buono }}</dd>
            <dt>Ottimo</dt>
            <dd>{{ $soglie->ottimo }}</dd>
            <dt>Indice Riferimento</dt>
            <dd>{{ $soglie->indice_riferimento }}</dd>
            <dt>Tipo Azienda</dt>
            <dd>{{ $soglie->tipo_azienda }}</dd>

        </dl>

    </div>
</div>

@endsection
