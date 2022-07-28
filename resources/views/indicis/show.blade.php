@extends('backend.layouts.app')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading clearfix">

        <span class="pull-left">
            <h4 class="mt-5 mb-5">{{ isset($indici->name) ? $indici->name : 'Indici' }}</h4>
        </span>

        <div class="pull-right">

            <form method="POST" action="{!! route('admin.indicis.indici.destroy', $indici->id) !!}" accept-charset="UTF-8">
            <input name="_method" value="DELETE" type="hidden">
            {{ csrf_field() }}
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.indicis.indici.index') }}" class="btn btn-primary" title="Show All Indici">
                        <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('admin.indicis.indici.create') }}" class="btn btn-success" title="Create New Indici">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                    </a>
                    
                    <a href="{{ route('admin.indicis.indici.edit', $indici->id ) }}" class="btn btn-primary" title="Edit Indici">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </a>

                    <button type="submit" class="btn btn-danger" title="Delete Indici" onclick="return confirm(&quot;Click Ok to delete Indici.?&quot;)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>

    </div>

    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt>Name</dt>
            <dd>{{ $indici->name }}</dd>
            <dt>Formula</dt>
            <dd>{{ $indici->formula }}</dd>
            <dt>Analisistype</dt>
            <dd>{{ optional($indici->Analisistype)->name }}</dd>
            <dt>Created At</dt>
            <dd>{{ $indici->created_at }}</dd>
            <dt>Updated At</dt>
            <dd>{{ $indici->updated_at }}</dd>

        </dl>

    </div>
</div>

@endsection