
<div class="form-group {{ $errors->has('bilanci_id') ? 'has-error' : '' }}">
    <label for="bilanci_id" class="col-md-2 control-label">Bilancio</label>
    <div class="col-md-10">
        <select class="form-control" id="bilanci_id" name="bilanci_id" required="true">
        	    <option value="" style="display: none;" {{ old('bilanci_id', optional($analisi)->bilanci_id ?: '') == '' ? 'selected' : '' }} disabled selected>Select bilanci</option>
        	@foreach ($bilancis as $key => $bilanci)
			    <option value="{{ $key }}" {{ old('bilanci_id', optional($analisi)->bilanci_id) == $key ? 'selected' : '' }}>
			    	{{ $bilanci }}
			    </option>
			@endforeach
        </select>
		
        {!! $errors->first('bilanci_id', '<p class="help-block">:message</p>') !!}
    </div>
</div>



<div class="form-group {{ $errors->has('account_id') ? 'has-error' : '' }}">
    <label for="account_id" class="col-md-2 control-label">Account</label>
    <div class="col-md-10">
        <select class="form-control" id="account_id" name="account_id" required="true">
        	    <option value="" style="display: none;" {{ old('account_id', optional($analisi)->account_id ?: '') == '' ? 'selected' : '' }} disabled selected>Select account</option>
        	@foreach ($accounts as $key => $account)
			    <option value="{{ $key }}" {{ old('account_id', optional($analisi)->account_id) == $key ? 'selected' : '' }}>
			    	{{ $account }}
			    </option>
			@endforeach
        </select>
		
        {!! $errors->first('account_id', '<p class="help-block">:message</p>') !!}
    </div>
</div>


