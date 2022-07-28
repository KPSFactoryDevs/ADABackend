@extends('backend.layouts.app')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading clearfix">

        <span class="pull-left">
            <h4 class="mt-5 mb-5">{{ isset($voci->name) ? $voci->name : 'pesi' }}</h4>
        </span>

        <div class="pull-right">

            <form method="POST" action="{!! route('admin.vocis.voci.destroy', $voci->id) !!}" accept-charset="UTF-8">
            <input name="_method" value="DELETE" type="hidden">
            {{ csrf_field() }}
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.vocis.voci.index') }}" class="btn btn-primary" title="Show All voci">
                        <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('admin.vocis.voci.create') }}" class="btn btn-success" title="Create New voci">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                    </a>
                    
                    <a href="{{ route('admin.vocis.voci.edit', $voci->id ) }}" class="btn btn-primary" title="Edit voci">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </a>

                    <button type="submit" class="btn btn-danger" title="Delete voci" onclick="return confirm(&quot;Click Ok to delete voci.?&quot;)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>

    </div>

    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt>Nome</dt>
            <dd>{{ $voci->name }}</dd>
            <dt>Nome_esteso</dt>
            <dd>{{ $voci->extended_name }}</dd>
            <dt>Obbligatorio</dt>
            <dd>{{ $voci->required }}</dd>
            <dt>Created At</dt>
            <dd>{{ $voci->created_at }}</dd>
            <dt>Updated At</dt>
            <dd>{{ $voci->updated_at }}</dd>

        </dl>

    </div>
</div>

@endsection