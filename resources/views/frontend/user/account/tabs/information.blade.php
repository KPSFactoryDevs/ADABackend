<x-forms.patch :action="route('frontend.user.profile.update')">
    <div class="form-group row">
        <label for="name" class="col-md-3 col-form-label text-md-right">@lang('Nome')</label>

        <div class="col-md-9">
            <input type="text" name="name" class="form-control" placeholder="{{ __('Nome') }}" value="{{ old('name') ?? $logged_in_user->name }}" required autofocus autocomplete="name" />
        </div>
    </div><!--form-group-->

    @if ($logged_in_user->canChangeEmail())
        <div class="form-group row">
            <label for="email" class="col-md-3 col-form-label text-md-right">@lang('E-mail')</label>

            <div class="col-md-9">
                <x-utils.alert type="info" class="mb-3" :dismissable="false">
                    <i class="fas fa-info-circle"></i> @lang('Se cambi la tua email verrai disconnesso fino a quando non confermerai il tuo nuovo indirizzo email')
                </x-utils.alert>

                <input type="email" name="email" id="email" class="form-control" placeholder="{{ __('E-mail') }}" value="{{ old('email') ?? $logged_in_user->email }}" required autocomplete="email" />
            </div>
        </div><!--form-group-->
    @endif

    <div class="form-group row mb-0">
        <div class="col-md-12 text-right">
            <button class="btn btn-outline-primary float-right" type="submit">@lang('Aggiorna')</button>
        </div>
    </div><!--form-group-->
</x-forms.patch>
