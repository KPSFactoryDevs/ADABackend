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
                <h4 class="mt-5 mb-5">Accounts</h4>
            </div>

            <div class="btn-group btn-group-sm pull-right" role="group">
                <a href="{{ route('admin.accounts.account.create') }}" class="btn btn-success" title="Create New Account">
                    <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                </a>
            </div>

        </div>

        @if(count($accounts) == 0)
            <div class="panel-body text-center">
                <h4>No Accounts Available.</h4>
            </div>
        @else
        <div class="panel-body panel-body-with-table">
            <div class="table-responsive">

                <table class="table table-striped ">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Importa Bilancio</th>
                            <th>Importa CR</th>

                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($accounts as $account)
                        <tr>
                            <td><a href="{{route('admin.accounts.account.show', $account->id)}}">{{ $account->Name }}</a></td>

                            <td>
                                <a href="{{ route('admin.accounts.account.bilancio', $account->id ) }}"  title="Show Account">
                                    <span class="btn btn-success" aria-hidden="true"></span>
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.accounts.account.cr', $account->id ) }}"  title="Show Account">
                                    <span class="btn btn-danger" aria-hidden="true"></span>
                                </a>
                            </td>

                            <td>

                                <form method="POST" action="{!! route('admin.accounts.account.destroy', $account->id) !!}" accept-charset="UTF-8">
                                <input name="_method" value="DELETE" type="hidden">
                                {{ csrf_field() }}

                                    <div class="btn-group btn-group-xs pull-right" role="group">
                                        <a href="{{ route('admin.accounts.account.show', $account->id ) }}" class="btn btn-info" title="Show Account">
                                            <span class="glyphicon glyphicon-open" aria-hidden="true"></span>
                                        </a>
                                        <a href="{{ route('admin.accounts.account.edit', $account->id ) }}" class="btn btn-primary" title="Edit Account">
                                            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                        </a>

                                        <button type="submit" class="btn btn-danger" title="Delete Account" onclick="return confirm(&quot;Click Ok to delete Account.&quot;)">
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
            {!! $accounts->render() !!}
        </div>

        @endif

    </div>
@endsection
