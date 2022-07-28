@extends('frontend.layouts.app')

@section('title', __('La tua password è scaduta.'))

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <x-frontend.card>
                    <x-slot name="header">
                        @lang('La tua password è scaduta.')
                    </x-slot>

                    <x-slot name="body">
                        <x-forms.patch :action="route('frontend.auth.password.expired.update')">
                            <div class="form-group row">
                                <label for="current_password" class="col-md-4 col-form-label text-md-right">@lang('Password attuale')</label>

                                <div class="col-md-6">
                                    <input type="password" name="current_password" class="form-control" placeholder="{{ __('Password attuale') }}" maxlength="100" required autofocus />
                                </div>
                            </div><!--form-group-->

                            <div class="form-group row">
                                <label for="password" class="col-md-4 col-form-label text-md-right">@lang('Nuova password')</label>

                                <div class="col-md-6">
                                    <input type="password" id="password" name="password" class="form-control" placeholder="{{ __('Nuova password') }}" maxlength="100" required autocomplete="password" />
                                </div>
                            </div><!--form-group-->

                            <div class="form-group row">
                                <label for="password_confirmation" class="col-md-4 col-form-label text-md-right">@lang('Conferma password')</label>

                                <div class="col-md-6">
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" maxlength="100" placeholder="{{ __('Conferma password') }}" required autocomplete="new-password" />
                                </div>
                            </div><!--form-group-->

                            <div class="form-group row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button class="btn btn-primary" type="submit">@lang('Aggiorna Password')</button>
                                </div>
                            </div><!--form-group-->
                        </x-forms.patch>
                    </x-slot>
                </x-frontend.card>
            </div><!--col-md-8-->
        </div><!--row-->
    </div><!--container-->
@endsection
