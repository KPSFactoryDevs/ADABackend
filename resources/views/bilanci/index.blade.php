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
                <h4 class="mt-5 mb-5">Bilanci</h4>
            </div>

            <div class="btn-group btn-group-sm pull-right" role="group">
                <a href="{{ route('admin.bilanci.bilancio.create') }}" class="btn btn-success" title="Create New Vocibilancio">
                    <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                </a>
            </div>

        </div>
        
        @if(count($bilancis) == 0)
            <div class="panel-body text-center">
                <h4>No Vocibilancios Available.</h4>
            </div>
        @else
        <div class="panel-body panel-body-with-table">
            <div class="table-responsive">

                <table class="table table-striped ">
                    <thead>
                        <tr>

                            <th>Id</th>
							<th>Account</th>
							<th>Created At</th>
							<th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($bilancis as $bilancio)
                        <tr>
						<td>{{ $bilancio->id }}</td>   
							
							<td>{{ optional($bilancio->account)->Name }}</td>
							<td>{{ $bilancio->created_at }}</td> 
							<td>{{ $bilancio->updated_at }}</td> 
                            <td>

                                <form method="POST" action="{!! route('admin.bilanci.bilancio.destroy', $bilancio->id) !!}" accept-charset="UTF-8">
                                <input name="_method" value="DELETE" type="hidden">
                                {{ csrf_field() }}

                                    <div class="btn-group btn-group-xs pull-right" role="group">
                                        <a href="{{ route('admin.bilanci.bilancio.show', $bilancio->id ) }}" class="btn btn-info" title="Show Vocibilancio">
                                            <span class="glyphicon glyphicon-open" aria-hidden="true"></span>
                                        </a>
                            

                                        <button type="submit" class="btn btn-danger" title="Delete Vocibilancio" onclick="return confirm(&quot;Click Ok to delete Vocibilancio.&quot;)">
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
            {!! $bilancis->render() !!}
        </div>
        
        @endif
    
    </div>
@endsection