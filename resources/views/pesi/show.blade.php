@extends('backend.layouts.app')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading clearfix">

        <span class="pull-left">
            <h4 class="mt-5 mb-5">{{ isset($pesi->name) ? $pesi->name : 'pesi' }}</h4>
        </span>

        <div class="pull-right">

            <form method="POST" action="{!! route('admin.pesis.pesi.destroy', $pesi->id) !!}" accept-charset="UTF-8">
            <input name="_method" value="DELETE" type="hidden">
            {{ csrf_field() }}
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.pesis.pesi.index') }}" class="btn btn-primary" title="Show All pesi">
                        <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('admin.pesis.pesi.create') }}" class="btn btn-success" title="Create New pesi">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                    </a>
                    
                    <a href="{{ route('admin.pesis.pesi.edit', $pesi->id ) }}" class="btn btn-primary" title="Edit pesi">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </a>

                    <button type="submit" class="btn btn-danger" title="Delete pesi" onclick="return confirm(&quot;Click Ok to delete pesi.?&quot;)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>

    </div>

    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt>Indice</dt>
            <dd>{{ $pesi->indice }}</dd>
            <dt>Pesi</dt>
            <dd>{{ $pesi->peso }}</dd>
            <dt>Range</dt>
            <dd>{{ $pesi->range_id }}</dd>
            <dt>Created At</dt>
            <dd>{{ $pesi->created_at }}</dd>
            <dt>Updated At</dt>
            <dd>{{ $pesi->updated_at }}</dd>

        </dl>

    </div>
</div>

@endsection