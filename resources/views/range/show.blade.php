@extends('backend.layouts.app')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading clearfix">

        <span class="pull-left">
            <h4 class="mt-5 mb-5">{{ isset($range->name) ? $range->name : 'range' }}</h4>
        </span>

        <div class="pull-right">

            <form method="POST" action="{!! route('admin.ranges.range.destroy', $range->id) !!}" accept-charset="UTF-8">
            <input name="_method" value="DELETE" type="hidden">
            {{ csrf_field() }}
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.ranges.range.index') }}" class="btn btn-primary" title="Show All range">
                        <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('admin.ranges.range.create') }}" class="btn btn-success" title="Create New range">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                    </a>
                    
                    <a href="{{ route('admin.ranges.range.edit', $range->id ) }}" class="btn btn-primary" title="Edit range">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </a>

                    <button type="submit" class="btn btn-danger" title="Delete range" onclick="return confirm(&quot;Click Ok to delete range.?&quot;)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>

    </div>

    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt>pesi_id</dt>
            <dd>{{ $range->pesi_id }}</dd>
            <dt>indice</dt>
            <dd>{{ $range->indice }}</dd>
            <dt>Range_min</dt>
            <dd>{{ $range->Range_min }}</dd>
            <dt>Range_max</dt>
            <dd>{{ $range->Range_max }}</dd>
            <dt>Score</dt>
            <dd>{{ $range->Score }}</dd>
            <dt>Created At</dt>
            <dd>{{ $range->created_at }}</dd>
            <dt>Updated At</dt>
            <dd>{{ $range->updated_at }}</dd>

        </dl>

    </div>
</div>

@endsection