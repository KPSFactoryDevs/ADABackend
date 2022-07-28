@extends('backend.layouts.app')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading clearfix">

        <span class="pull-left">
            <h4 class="mt-5 mb-5">{{ isset($title) ? $title : 'Roe' }}</h4>
        </span>

        <div class="pull-right">

            <form method="POST" action="{!! route('admin.roes.roe.destroy', $roe->id) !!}" accept-charset="UTF-8">
            <input name="_method" value="DELETE" type="hidden">
            {{ csrf_field() }}
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.roes.roe.index') }}" class="btn btn-primary" title="Show All Roe">
                        <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
                    </a>

                    <a href="{{ route('admin.roes.roe.create') }}" class="btn btn-success" title="Create New Roe">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                    </a>
                    
                    <a href="{{ route('admin.roes.roe.edit', $roe->id ) }}" class="btn btn-primary" title="Edit Roe">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </a>

                    <button type="submit" class="btn btn-danger" title="Delete Roe" onclick="return confirm(&quot;Click Ok to delete Roe.?&quot;)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>

    </div>

    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt>Year</dt>
            <dd>{{ $roe->year }}</dd>
            <dt>Value</dt>
            <dd>{{ $roe->value }}</dd>

        </dl>

    </div>
</div>

@endsection