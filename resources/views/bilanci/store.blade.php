@inject('model', '\App\Domains\Auth\Models\User')

@extends('backend.layouts.app')

@section('title', __('Create User'))

@section('content')
    <x-forms.post :action="route('admin.analysis.bilanci.store')">
        <x-backend.card>
            <x-slot name="header">
                @lang('Create User')
            </x-slot>

            <x-slot name="headerActions">
                <x-utils.link class="card-header-action" :href="route('admin.auth.user.index')" :text="__('Cancel')" />
            </x-slot>

            <x-slot name="body">
                <div x-data="{userType : '{{ $model::TYPE_USER }}'}">
				 
            
								
			@foreach ($jsonData as $data => $value) 
				<div class="form-group row">
					<div class="col-md-6">
						{{ $data }}
					</div>
					@if(is_numeric($value))
					<div class="col-md-6">
						<input type="text"  class="form-control"   name="{{ $data }}" value="{{ $value }}"  required />
					</div>
					@else
						 <div class="col-md-6">
						{!! $value !!}
						  </div>
					@endif
				</div>
			@endforeach
 
                </div>
            </x-slot>

            <x-slot name="footer">
                <button class="btn btn-sm btn-primary float-right" type="submit">Modifica Bilancio</button>
            </x-slot>
        </x-backend.card>
    </x-forms.post>
@endsection
