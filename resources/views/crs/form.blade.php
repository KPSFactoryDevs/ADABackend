
<div class="form-group {{ $errors->has('account_id') ? 'has-error' : '' }}">
    <label for="account_id" class="col-md-2 control-label">Account</label>
    <div class="col-md-10">
        <select class="form-control" id="account_id" name="account_id" required="true">
        	    <option value="" style="display: none;" {{ old('account_id', optional($cr)->account_id ?: '') == '' ? 'selected' : '' }} disabled selected>Enter account here...</option>
        	@foreach ($accounts as $key => $account)
			    <option value="{{ $key }}" {{ old('account_id', optional($cr)->account_id) == $key ? 'selected' : '' }}>
			    	{{ $account }}
			    </option>
			@endforeach
        </select>
        
        {!! $errors->first('account_id', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('anno') ? 'has-error' : '' }}">
    <label for="anno" class="col-md-2 control-label">Anno</label>
    <div class="col-md-10">
        <input class="form-control" name="anno" type="number" id="anno" value="{{ old('anno', optional($cr)->anno) }}" min="-2147483648" max="2147483647" required="true" placeholder="Enter anno here...">
        {!! $errors->first('anno', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('mese') ? 'has-error' : '' }}">
    <label for="mese" class="col-md-2 control-label">Mese</label>
    <div class="col-md-10">
        <input class="form-control" name="mese" type="text" id="mese" value="{{ old('mese', optional($cr)->mese) }}" minlength="1" maxlength="191" required="true" placeholder="Enter mese here...">
        {!! $errors->first('mese', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('nome_banca') ? 'has-error' : '' }}">
    <label for="nome_banca" class="col-md-2 control-label">Nome Banca</label>
    <div class="col-md-10">
        <input class="form-control" name="nome_banca" type="text" id="nome_banca" value="{{ old('nome_banca', optional($cr)->nome_banca) }}" minlength="1" maxlength="191" required="true" placeholder="Enter nome banca here...">
        {!! $errors->first('nome_banca', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('sezione') ? 'has-error' : '' }}">
    <label for="sezione" class="col-md-2 control-label">Sezione</label>
    <div class="col-md-10">
        <input class="form-control" name="sezione" type="text" id="sezione" value="{{ old('sezione', optional($cr)->sezione) }}" minlength="1" maxlength="191" required="true" placeholder="Enter sezione here...">
        {!! $errors->first('sezione', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('categoria') ? 'has-error' : '' }}">
    <label for="categoria" class="col-md-2 control-label">Categoria</label>
    <div class="col-md-10">
        <input class="form-control" name="categoria" type="text" id="categoria" value="{{ old('categoria', optional($cr)->categoria) }}" minlength="1" maxlength="191" required="true" placeholder="Enter categoria here...">
        {!! $errors->first('categoria', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('accordato') ? 'has-error' : '' }}">
    <label for="accordato" class="col-md-2 control-label">Accordato</label>
    <div class="col-md-10">
        <input class="form-control" name="accordato" type="number" id="accordato" value="{{ old('accordato', optional($cr)->accordato) }}" min="-999999" max="999999" required="true" placeholder="Enter accordato here..." step="any">
        {!! $errors->first('accordato', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('accordato_operativo') ? 'has-error' : '' }}">
    <label for="accordato_operativo" class="col-md-2 control-label">Accordato Operativo</label>
    <div class="col-md-10">
        <input class="form-control" name="accordato_operativo" type="number" id="accordato_operativo" value="{{ old('accordato_operativo', optional($cr)->accordato_operativo) }}" min="-999999" max="999999" required="true" placeholder="Enter accordato operativo here..." step="any">
        {!! $errors->first('accordato_operativo', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('utilizzato') ? 'has-error' : '' }}">
    <label for="utilizzato" class="col-md-2 control-label">Utilizzato</label>
    <div class="col-md-10">
        <input class="form-control" name="utilizzato" type="number" id="utilizzato" value="{{ old('utilizzato', optional($cr)->utilizzato) }}" min="-999999" max="999999" required="true" placeholder="Enter utilizzato here..." step="any">
        {!! $errors->first('utilizzato', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('durata_residua') ? 'has-error' : '' }}">
    <label for="durata_residua" class="col-md-2 control-label">Durata Residua</label>
    <div class="col-md-10">
        <input class="form-control" name="durata_residua" type="text" id="durata_residua" value="{{ old('durata_residua', optional($cr)->durata_residua) }}" minlength="1" maxlength="191" required="true" placeholder="Enter durata residua here...">
        {!! $errors->first('durata_residua', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('durata_originaria') ? 'has-error' : '' }}">
    <label for="durata_originaria" class="col-md-2 control-label">Durata Originaria</label>
    <div class="col-md-10">
        <input class="form-control" name="durata_originaria" type="text" id="durata_originaria" value="{{ old('durata_originaria', optional($cr)->durata_originaria) }}" minlength="1" maxlength="191" required="true" placeholder="Enter durata originaria here...">
        {!! $errors->first('durata_originaria', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('localizzazione') ? 'has-error' : '' }}">
    <label for="localizzazione" class="col-md-2 control-label">Localizzazione</label>
    <div class="col-md-10">
        <input class="form-control" name="localizzazione" type="text" id="localizzazione" value="{{ old('localizzazione', optional($cr)->localizzazione) }}" minlength="1" maxlength="191" required="true" placeholder="Enter localizzazione here...">
        {!! $errors->first('localizzazione', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('divisa') ? 'has-error' : '' }}">
    <label for="divisa" class="col-md-2 control-label">Divisa</label>
    <div class="col-md-10">
        <input class="form-control" name="divisa" type="text" id="divisa" value="{{ old('divisa', optional($cr)->divisa) }}" minlength="1" maxlength="191" required="true" placeholder="Enter divisa here...">
        {!! $errors->first('divisa', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('tipo_garanzia') ? 'has-error' : '' }}">
    <label for="tipo_garanzia" class="col-md-2 control-label">Tipo Garanzia</label>
    <div class="col-md-10">
        <input class="form-control" name="tipo_garanzia" type="text" id="tipo_garanzia" value="{{ old('tipo_garanzia', optional($cr)->tipo_garanzia) }}" minlength="1" maxlength="191" required="true" placeholder="Enter tipo garanzia here...">
        {!! $errors->first('tipo_garanzia', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('stato_rapporto') ? 'has-error' : '' }}">
    <label for="stato_rapporto" class="col-md-2 control-label">Stato Rapporto</label>
    <div class="col-md-10">
        <input class="form-control" name="stato_rapporto" type="text" id="stato_rapporto" value="{{ old('stato_rapporto', optional($cr)->stato_rapporto) }}" minlength="1" maxlength="191" required="true" placeholder="Enter stato rapporto here...">
        {!! $errors->first('stato_rapporto', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('tipo_attivita') ? 'has-error' : '' }}">
    <label for="tipo_attivita" class="col-md-2 control-label">Tipo Attivita</label>
    <div class="col-md-10">
        <input class="form-control" name="tipo_attivita" type="text" id="tipo_attivita" value="{{ old('tipo_attivita', optional($cr)->tipo_attivita) }}" minlength="1" maxlength="191" required="true" placeholder="Enter tipo attivita here...">
        {!! $errors->first('tipo_attivita', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('ruolo_affidato') ? 'has-error' : '' }}">
    <label for="ruolo_affidato" class="col-md-2 control-label">Ruolo Affidato</label>
    <div class="col-md-10">
        <input class="form-control" name="ruolo_affidato" type="text" id="ruolo_affidato" value="{{ old('ruolo_affidato', optional($cr)->ruolo_affidato) }}" minlength="1" maxlength="191" required="true" placeholder="Enter ruolo affidato here...">
        {!! $errors->first('ruolo_affidato', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('import_export') ? 'has-error' : '' }}">
    <label for="import_export" class="col-md-2 control-label">Import Export</label>
    <div class="col-md-10">
        <input class="form-control" name="import_export" type="text" id="import_export" value="{{ old('import_export', optional($cr)->import_export) }}" minlength="1" maxlength="191" required="true" placeholder="Enter import export here...">
        {!! $errors->first('import_export', '<p class="help-block">:message</p>') !!}
    </div>
</div>

