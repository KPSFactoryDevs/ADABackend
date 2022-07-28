
<div class="form-group {{ $errors->has('rischio_elevato') ? 'has-error' : '' }}">
    <label for="rischio_elevato" class="col-md-2 control-label">Rischio Elevato</label>
    <div class="col-md-10">
        <input class="form-control" name="rischio_elevato" type="number" id="rischio_elevato" value="{{ old('rischio_elevato', optional($soglie)->rischio_elevato) }}" min="-999999" max="999999" required="true" placeholder="Enter rischio elevato here..." step="any">
        {!! $errors->first('rischio_elevato', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('situazione_critica') ? 'has-error' : '' }}">
    <label for="situazione_critica" class="col-md-2 control-label">Situazione Critica</label>
    <div class="col-md-10">
        <input class="form-control" name="situazione_critica" type="number" id="situazione_critica" value="{{ old('situazione_critica', optional($soglie)->situazione_critica) }}" min="-999999" max="999999" required="true" placeholder="Enter situazione critica here..." step="any">
        {!! $errors->first('situazione_critica', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('buono') ? 'has-error' : '' }}">
    <label for="buono" class="col-md-2 control-label">Buono</label>
    <div class="col-md-10">
        <input class="form-control" name="buono" type="number" id="buono" value="{{ old('buono', optional($soglie)->buono) }}" min="-999999" max="999999" required="true" placeholder="Enter buono here..." step="any">
        {!! $errors->first('buono', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('ottimo') ? 'has-error' : '' }}">
    <label for="ottimo" class="col-md-2 control-label">Ottimo</label>
    <div class="col-md-10">
        <input class="form-control" name="ottimo" type="number" id="ottimo" value="{{ old('ottimo', optional($soglie)->ottimo) }}" min="-999999" max="999999" required="true" placeholder="Enter ottimo here..." step="any">
        {!! $errors->first('ottimo', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('indice_riferimento') ? 'has-error' : '' }}">
    <label for="indice_riferimento" class="col-md-2 control-label">Indice Riferimento</label>
    <div class="col-md-10">
        <input class="form-control" name="indice_riferimento" type="text" id="indice_riferimento" value="{{ old('indice_riferimento', optional($soglie)->indice_riferimento) }}" minlength="1" maxlength="191" required="true" placeholder="Enter indice riferimento here...">
        {!! $errors->first('indice_riferimento', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('tipo_azienda') ? 'has-error' : '' }}">
    <label for="tipo_azienda" class="col-md-2 control-label">Tipo Azienda</label>
    <div class="col-md-10">
        <select class="form-control" id="tipo_azienda" name="tipo_azienda" required="true">
            @if(isset($id))
                <option value="{{$soglie->tipo_azienda}}" style="display: none;" disabled selected>{{$soglie->tipo_azienda}}</option>
            @else
                <option value="" style="display: none;" disabled selected>Seleziona il tipo di azienda</option>
            @endif
                @foreach($tipiAziende as $tipo)
                <option value="{{$tipo}}">
                    {{$tipo}}
                </option>
            @endforeach
        </select>
    </div>
</div>

