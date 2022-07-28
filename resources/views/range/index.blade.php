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
                <h4 class="mt-5 mb-5">range</h4>
            </div>

            <div class="btn-group btn-group-sm pull-right" role="group">
                <a href="{{ route('admin.ranges.range.create') }}" class="btn btn-success" title="Create New range">
                    <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                </a>
            </div>

        </div>
        
        @if(count($ranges) == 0)
            <div class="panel-body text-center">
                <h4>No range Available.</h4>
            </div>
        @else
        <div class="panel-body panel-body-with-table">
            <div class="table-responsive">

                <table class="table table-striped ">
                    <thead>
                        <tr>
                            <th>Indice</th>
                            <th>Range_min</th>
                            <th>Range_max</th>
                            <th>Score</th>

                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($ranges as $range)
                        <tr>
                            <td>{{ $range->indice }}</td>
                            <td>{{ $range->range_min }}</td>
                            <td>{{ $range->range_max }}</td>
                            <td>{{ $range->score }}</td>

                            <td>

                                <form method="POST" action="{!! route('admin.ranges.range.destroy', $range->id) !!}" accept-charset="UTF-8">
                                <input name="_method" value="DELETE" type="hidden">
                                {{ csrf_field() }}

                                    <div class="btn-group btn-group-xs pull-right" role="group">
                                        <a href="{{ route('admin.ranges.range.show', $range->id ) }}" class="btn btn-info" title="Show range">
                                            <span class="glyphicon glyphicon-open" aria-hidden="true"></span>
                                        </a>
                                        <a href="{{ route('admin.ranges.range.edit', $range->id ) }}" class="btn btn-primary" title="Edit range">
                                            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                        </a>

                                        <button type="submit" class="btn btn-danger" title="Delete range" onclick="return confirm(&quot;Click Ok to delete range.&quot;)">
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
            {!! $ranges->render() !!}
        </div>
        
        @endif
    
    </div>
@endsection