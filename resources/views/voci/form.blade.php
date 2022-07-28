
<div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
    <label for="name" class="col-md-2 control-label">Nome</label>
    <div class="col-md-10">
        <input class="form-control" name="name" type="text" id="name" value="{{ old('name', optional($voci)->name) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
    </div>
</div>


<div class="form-group {{ $errors->has('extended_name') ? 'has-error' : '' }}">
    <label for="extended_name" class="col-md-2 control-label">Nome esteso</label>
    <div class="col-md-10">
        <input class="form-control" name="extended_name" type="text" id="extended_name" value="{{ old('extended_name', optional($voci)->extended_name) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('extended_name', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('voce_padre') ? 'has-error' : '' }}">
    <label for="extended_name" class="col-md-2 control-label">Voce Padre</label>
    <div class="col-md-10">
        <input class="form-control" name="voce_padre" type="text" id="voce_padre" value="{{ old('voce_padre', optional($voci)->voce_padre) }}" minlength="1" maxlength="255" placeholder="Enter name here...">
        {!! $errors->first('voce_padre', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('required') ? 'has-error' : '' }}">
    <label for="required" class="col-md-2 control-label">Obbligatorio</label>
    <div class="col-md-10">
        <input class="switch-input" name="required" type="checkbox" id="required" >
        {!! $errors->first('required', '<p class="help-block">:message</p>') !!}
    </div>
</div>


