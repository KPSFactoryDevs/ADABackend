<div class="form-group {{ $errors->has('pesi_id') ? 'has-error' : '' }}">
    <label for="pesi_id" class="col-md-2 control-label">pesi_id</label>
    <div class="col-md-10">
        <select class="form-control" id="pesi_id" name="pesi_id">
        	    <option value=""  {{ old('pesi_id', optional($range)->pesi_id ?: '') == '' ? 'selected' : '' }}  selected>Seleziona un peso_id</option>
        	@foreach ($ranges as $key => $range)
			    <option value="{{ $key }}" {{ old('pesi_id', optional($range)->pesi_id) == $key ? 'selected' : '' }}>
			    	{{ $range }}
			    </option>
			@endforeach
        </select>
        </div>
</div>


<div class="form-group {{ $errors->has('indice') ? 'has-error' : '' }}">
    <label for="indice" class="col-md-2 control-label">indice</label>
    <div class="col-md-10">
        <input class="form-control" name="indice" type="text" id="indice" value="{{ old('indice', optional($range)->indice) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('indice', '<p class="help-block">:message</p>') !!}
    </div>
</div>


<div class="form-group {{ $errors->has('range_min') ? 'has-error' : '' }}">
    <label for="range_min" class="col-md-2 control-label">range_min</label>
    <div class="col-md-10">
        <input class="form-control" name="range_min" type="float" id="range_min" value="{{ old('range_min', optional($range)->range_min) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('range_min', '<p class="help-block">:message</p>') !!}
    </div>
</div>


<div class="form-group {{ $errors->has('range_max') ? 'has-error' : '' }}">
    <label for="range_max" class="col-md-2 control-label">range_max</label>
    <div class="col-md-10">
        <input class="form-control" name="range_max" type="float" id="range_max" value="{{ old('range_max', optional($range)->range_max) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('range_max', '<p class="help-block">:message</p>') !!}
    </div>
</div>


<div class="form-group {{ $errors->has('score') ? 'has-error' : '' }}">
    <label for="score" class="col-md-2 control-label">score</label>
    <div class="col-md-10">
        <input class="form-control" name="score" type="float" id="score" value="{{ old('score', optional($range)->score) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('score', '<p class="help-block">:message</p>') !!}
    </div>
</div>
