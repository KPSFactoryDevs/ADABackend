
<div class="form-group {{ $errors->has('year') ? 'has-error' : '' }}">
    <label for="year" class="col-md-2 control-label">Year</label>
    <div class="col-md-10">
        <input class="form-control" name="year" type="number" id="year" value="{{ old('year', optional($roe)->year) }}" min="-2147483648" max="2147483647" required="true" placeholder="Enter year here...">
        {!! $errors->first('year', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
    <label for="value" class="col-md-2 control-label">Value</label>
    <div class="col-md-10">
        <input class="form-control" name="value" type="number" id="value" value="{{ old('value', optional($roe)->value) }}" min="-99999999" max="99999999" required="true" placeholder="Enter value here..." step="any">
        {!! $errors->first('value', '<p class="help-block">:message</p>') !!}
    </div>
</div>

