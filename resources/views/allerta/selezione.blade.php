@inject('model', '\App\Models\Account')

@extends('backend.layouts.app')

@section('title', __('Create User'))

@section('content')

    <x-forms.post :action="route('admin.allerta.analisi')">
        <x-backend.card>
            <x-slot name="header">
                @lang('Seleziona gli anni da attenzionare')
            </x-slot>

            <x-slot name="headerActions">

            </x-slot>

            <x-slot name="body">

                <h1>{{$account->Name}}</h1>
                <input type="hidden" name="account_id" value="{{$account->id}}">
                <div class="form-group {{ $errors->has('account_id') ? 'has-error' : '' }}">
                    <label for="multiselectBilanci" class="col-md-12 control-label">Bilanci</label>
                    <div class="col-md-12">
                        <select id="multiselectBilanci" name="multiselectBilanci[]" multiple="multiple">
                            @foreach($bilanci as $data=>$value)
                                <?php
                                $anno = $value->current_year[0].$value->current_year[1].$value->current_year[2].$value->current_year[3];
                                ?>
                                <option value="{{$value->id}}">{{$anno}}</option>
                            @endforeach
                        </select>
                    </div>
                    <label for="multiselectCR" class="col-md-12 control-label">Centrali rischi</label>
                    <div class="col-md-12">
                        <select id="multiselectCR" name="multiselectCR[]" multiple="multiple">
                            @foreach($cr as $data=>$value)
                                <option value="{{$value->anno.'_'.$value->mese}}">{{$value->anno.' '.$value->mese}}</option>
                            @endforeach
                        </select>

                    </div>
                    <div class="form-group {{ $errors->has('account_id') ? 'has-error' : '' }}">
                        <div class="col-md-12"><br>
                            <label for="vehicle1"> Compilazione questionario</label><br>
                            <input type="checkbox" id="questionario" name="questionario" value="1">&nbsp;Si
                        </div>
                    </div>
                    </div>
            </x-slot>
            <x-slot name="footer">
                <button class="btn btn-sm btn-primary float-right" type="submit">Seleziona</button>
            </x-slot>
        </x-backend.card>
    </x-forms.post>
@endsection
