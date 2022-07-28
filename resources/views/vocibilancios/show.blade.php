@extends('backend.layouts.app')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading clearfix">

        <span class="pull-left">
            <h4 class="mt-5 mb-5">{{ isset($vocibilancio->name) ? $vocibilancio->name : 'Vocibilancio' }}</h4>
        </span>

        <div class="pull-right">

            <form method="POST" action="{!! route('admin.vocibilancios.vocibilancio.destroy', $vocibilancio->id) !!}" accept-charset="UTF-8">
            <input name="_method" value="DELETE" type="hidden">
            {{ csrf_field() }}
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.vocibilancios.vocibilancio.index') }}" class="btn btn-primary" title="Show All Vocibilancio">
                        <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('admin.vocibilancios.vocibilancio.create') }}" class="btn btn-success" title="Create New Vocibilancio">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                    </a>
                    
                    <a href="{{ route('admin.vocibilancios.vocibilancio.edit', $vocibilancio->id ) }}" class="btn btn-primary" title="Edit Vocibilancio">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </a>

                    <button type="submit" class="btn btn-danger" title="Delete Vocibilancio" onclick="return confirm(&quot;Click Ok to delete Vocibilancio.?&quot;)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>

    </div>

    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt>Name</dt>
            <dd>{{ $vocibilancio->name }}</dd>
            <dt>Tassonomia</dt>
            <dd>{{ $vocibilancio->tassonomia }}</dd>
            <dt>Created At</dt>
            <dd>{{ $vocibilancio->created_at }}</dd>
            <dt>Updated At</dt>
            <dd>{{ $vocibilancio->updated_at }}</dd>

        </dl>

    </div>
</div>

@endsection