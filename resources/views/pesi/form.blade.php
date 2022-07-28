
<div class="form-group {{ $errors->has('indice') ? 'has-error' : '' }}">
    <label for="indice" class="col-md-2 control-label">indice</label>
    <div class="col-md-10">
        <input class="form-control" name="indice" type="text" id="indice" value="{{ old('indice', optional($pesi)->indice) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('indice', '<p class="help-block">:message</p>') !!}
    </div>
</div>


<div class="form-group {{ $errors->has('peso') ? 'has-error' : '' }}">
    <label for="peso" class="col-md-2 control-label">peso</label>
    <div class="col-md-10">
        <input class="form-control" name="peso" type="text" id="peso" value="{{ old('peso', optional($pesi)->peso) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('peso', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('range_id') ? 'has-error' : '' }}">
    <label for="range_id" class="col-md-2 control-label">Range</label>
    <div class="col-md-10">
        <select class="form-control" id="range_id" name="range_id">
        	    <option value=""  {{ old('range_id', optional($pesi)->range_id ?: '') == '' ? 'selected' : '' }}  selected>Seleziona un range</option>
        	@foreach ($pesi as $key => $pesi)
			    <option value="{{ $key }}" {{ old('range_id', optional($pesi)->range_id) == $key ? 'selected' : '' }}>
			    	{{ $pesi }}
			    </option>
			@endforeach
        </select>
        </div>
</div>



