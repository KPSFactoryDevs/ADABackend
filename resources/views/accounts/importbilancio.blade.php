@inject('model', '\App\Models\Account')

@extends('backend.layouts.app')

@section('title', __('Create User'))

@section('content')
    <x-forms.post :action="route('admin.accounts.account.recapbilancio')" enctype="multipart/form-data">
        <x-backend.card>
            <x-slot name="header">
                @lang('Importa Bilancio XBRL')
            </x-slot>

            <x-slot name="headerActions">

            </x-slot>

            <x-slot name="body">

                <div class="form-group {{ $errors->has('account_id') ? 'has-error' : '' }}">
                    <label for="account_id" class="col-md-2 control-label">Account</label>
                    <div class="col-md-10">
                        <select class="form-control" id="account_id" name="account_id" required="true">
                            <option value="" style="display: none;" disabled selected>Select account</option>
                                <option value="{{ $account->id }}">
                                    {{ $account->Name }}
                                </option>
                        </select>

                        {!! $errors->first('account_id', '<p class="help-block">:message</p>') !!}
                    </div>
                </div>
                <div class="col-md-10">
                    <input class="form-control" name="tipobilancio" type="text" id="tipobilancio" value="" minlength="1" maxlength="255" required="true" placeholder="Inserisci il tipo di bilancio">
                    {!! $errors->first('Name', '<p class="help-block">:message</p>') !!}
                </div>
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('File')</label>
                    <div class="flex flex-col justify-around h-full">
                        <div class="col-md-10">
                            <input type="file"  name="xbrl"   required />
                        </div>
                    </div>
                </div><!--form-group-->

            </x-slot>

            <x-slot name="footer">
                <button class="btn btn-sm btn-primary float-right" type="submit">Importa Bilancio</button>
            </x-slot>
        </x-backend.card>
    </x-forms.post>
@endsection
