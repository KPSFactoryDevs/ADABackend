@extends('frontend.layouts.app')
<style>
.sticky-wrapper{
    display:none;
}
.search-element{
    display: none;
}
.dropdown {
.header-message{
    display:none;
}
}
</style>
@section('title', __('Registrazione'))

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <x-frontend.card>
                    <x-slot name="header">
                        @lang('Registrati')
                    </x-slot>

                    <x-slot name="body">
                        <x-forms.post :action="route('frontend.auth.register')">
                            <div class="form-group row">
                                <label for="name" class="col-md-4 col-form-label text-md-right">@lang('Nome')</label>

                                <div class="col-md-6">
                                    <input type="text" name="name" id="name" class="form-control" value="{{ old('nome') }}" placeholder="{{ __('Name') }}" maxlength="100" required autofocus autocomplete="name" />
                                </div>
                            </div><!--form-group-->

                            <div class="form-group row">
                                <label for="name" class="col-md-4 col-form-label text-md-right">@lang('E-mail')</label>

                                <div class="col-md-6">
                                    <input type="email" name="email" id="email" class="form-control" placeholder="{{ __('E-mail') }}" value="{{ old('email') }}" maxlength="255" required autocomplete="email" />
                                </div>
                            </div><!--form-group-->

                            <div class="form-group row">
                                <label for="name" class="col-md-4 col-form-label text-md-right">@lang('Password')</label>

                                <div class="col-md-6">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="{{ __('Password') }}" maxlength="100" required autocomplete="new-password" />
                                </div>
                            </div><!--form-group-->

                            <div class="form-group row">
                                <label for="name" class="col-md-4 col-form-label text-md-right">@lang('Conferma password')</label>

                                <div class="col-md-6">
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="{{ __('Conferma password') }}" maxlength="100" required autocomplete="new-password" />
                                </div>
                            </div><!--form-group-->

                            <div class="form-group row">
                                <div class="col-md-6 offset-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="terms" value="1" id="terms" class="form-check-input" required>
                                        <label class="form-check-label" for="terms">
                                            @lang('Accetto i') <a href="{{ route('frontend.pages.terms') }}" target="_blank">@lang('Termini e Condizioni')</a>
                                        </label>
                                    </div>
                                </div>
                            </div><!--form-group-->

                            @if(config('boilerplate.access.captcha.registration'))
                                <div class="row">
                                    <div class="col">
                                        @captcha
                                        <input type="hidden" name="captcha_status" value="true" />
                                    </div><!--col-->
                                </div><!--row-->
                            @endif

                            <div class="form-group row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button class="btn btn-primary" type="submit">@lang('Registrati')</button>
                                </div>
                            </div><!--form-group-->
                        </x-forms.post>
                    </x-slot>
                </x-frontend.card>
            </div><!--col-md-8-->
        </div><!--row-->
    </div><!--container-->
@endsection