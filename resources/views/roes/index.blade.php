@extends('backend.layouts.app')

@section('content')


    <div class="panel panel-default">

        <div class="panel-heading clearfix">

            <div class="pull-left">
                <h4 class="mt-5 mb-5">Roes</h4>
            </div>

            <div class="btn-group btn-group-sm pull-right" role="group">
                <a href="{{ route('admin.roes.roe.create') }}" class="btn btn-success" title="Create New Roe">
                    <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                </a>
            </div>

        </div>
        
        @if(count($roes) == 0)
            <div class="panel-body text-center">
                <h4>No Roes Available.</h4>
            </div>
        @else
        <div class="panel-body panel-body-with-table">
            <div class="table-responsive">

                <table class="table table-striped ">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Value</th>

                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($roes as $roe)
                        <tr>
                            <td>{{ $roe->year }}</td>
                            <td>{{ $roe->value }}</td>

                            <td>

                                <form method="POST" action="{!! route('admin.roes.roe.destroy', $roe->id) !!}" accept-charset="UTF-8">
                                <input name="_method" value="DELETE" type="hidden">
                                {{ csrf_field() }}

                                    <div class="btn-group btn-group-xs pull-right" role="group">
                                        <a href="{{ route('admin.roes.roe.show', $roe->id ) }}" class="btn btn-info" title="Show Roe">
                                            <span class="glyphicon glyphicon-open" aria-hidden="true"></span>
                                        </a>
                                        <a href="{{ route('admin.roes.roe.edit', $roe->id ) }}" class="btn btn-primary" title="Edit Roe">
                                            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                        </a>

                                        <button type="submit" class="btn btn-danger" title="Delete Roe" onclick="return confirm(&quot;Click Ok to delete Roe.&quot;)">
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
            {!! $roes->render() !!}
        </div>
        
        @endif
    
    </div>
@endsection