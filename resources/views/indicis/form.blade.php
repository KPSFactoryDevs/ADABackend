
<div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
    <label for="name" class="col-md-2 control-label">Name</label>
    <div class="col-md-10">
        <input class="form-control" name="name" type="text" id="name" value="{{ old('name', optional($indici)->name) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('formula') ? 'has-error' : '' }}">
    <label for="formula" class="col-md-2 control-label">Formula</label>
    <div class="col-md-10">
        <textarea class="form-control" name="formula" cols="50" rows="10" id="formula" required="true" placeholder="Enter formula here...">{{ old('formula', optional($indici)->formula) }}</textarea>
        {!! $errors->first('formula', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('analisistype_id') ? 'has-error' : '' }}">
    <label for="analisistype_id" class="col-md-2 control-label">Analisistype</label>
    <div class="col-md-10">
        <select class="form-control" id="analisistype_id" name="analisistype_id" required="true">
        	    <option value="" style="display: none;" {{ old('analisistype_id', optional($indici)->analisistype_id ?: '') == '' ? 'selected' : '' }} disabled selected>Select analisistype</option>
        	@foreach ($Analisistypes as $key => $Analisistype)
			    <option value="{{ $key }}" {{ old('analisistype_id', optional($indici)->analisistype_id) == $key ? 'selected' : '' }}>
			    	{{ $Analisistype }}
			    </option>
			@endforeach
        </select>
        
        {!! $errors->first('analisistype_id', '<p class="help-block">:message</p>') !!}
    </div>
</div>

