
<div class="form-group {{ $errors->has('Name') ? 'has-error' : '' }}">
    <label for="Name" class="col-md-2 control-label">Name</label>
    <div class="col-md-10">
        <input class="form-control" name="Name" type="text" id="Name" value="{{ old('Name', optional($account)->Name) }}" minlength="1" maxlength="255" required="true" placeholder="Enter name here...">
        {!! $errors->first('Name', '<p class="help-block">:message</p>') !!}
    </div>
</div>

